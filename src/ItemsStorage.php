<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\Database;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Injection\Fragment;
use Yiisoft\Rbac\Cycle\ItemTreeTraversal\ItemTreeTraversalFactory;
use Yiisoft\Rbac\Cycle\ItemTreeTraversal\ItemTreeTraversalInterface;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;

/**
 * **Warning:** Do not use directly! Use with {@see Manager} instead.
 *
 * Storage for RBAC items (roles and permissions) and their relations in the form of database tables. Operations are
 * performed using Cycle ORM.
 *
 * @psalm-import-type ItemsIndexedByName from ItemsStorageInterface
 * @psalm-type RawItem = array{
 *     type: Item::TYPE_*,
 *     name: string,
 *     description: string|null,
 *     ruleName: string|null,
 *     createdAt: int|string,
 *     updatedAt: int|string
 * }
 * @psalm-type RawRole = array{
 *     type: Item::TYPE_ROLE,
 *     name: string,
 *     description: string|null,
 *     ruleName: string|null,
 *     createdAt: int|string,
 *     updatedAt: int|string
 *  }
 * @psalm-type RawPermission = array{
 *     type: Item::TYPE_PERMISSION,
 *     name: string,
 *     description: string|null,
 *     ruleName: string|null,
 *     createdAt: int|string,
 *     updatedAt: int|string
 * }
 */
final class ItemsStorage implements ItemsStorageInterface
{
    /**
     * @var ItemTreeTraversalInterface|null Lazily created RBAC item tree traversal strategy.
     */
    private ?ItemTreeTraversalInterface $treeTraversal = null;

    /**
     * @param DatabaseInterface $database Cycle database instance.
     *
     * @param string $tableName A name of the table for storing RBAC items.
     * @psalm-param non-empty-string $tableName
     *
     * @param string $childrenTableName A name of the table for storing relations between RBAC items. When set to
     * `null`, it will be automatically generated using {@see $tableName}.
     * @psalm-param non-empty-string $childrenTableName
     */
    public function __construct(
        private DatabaseInterface $database,
        private string $tableName = DbSchemaManager::ITEMS_TABLE,
        private string $childrenTableName = DbSchemaManager::ITEMS_CHILDREN_TABLE,
    ) {
    }

    public function clear(): void
    {
        $itemsStorage = $this;
        $this
            ->database
            ->transaction(static function (Database $database) use ($itemsStorage): void {
                $database
                    ->delete($itemsStorage->childrenTableName)
                    ->run();
                $database
                    ->delete($itemsStorage->tableName)
                    ->run();
            });
    }

    public function getAll(): array
    {
        /** @psalm-var RawItem[] $rows */
        $rows = $this
            ->database
            ->select()
            ->from($this->tableName)
            ->fetchAll();

        return array_map(
            fn(array $row): Item => $this->createItem(...$row),
            $rows,
        );
    }

    public function getByNames(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        /** @psalm-var RawItem[] $rawItems */
        $rawItems = $this
            ->database
            ->select()
            ->from($this->tableName)
            ->andWhere('name', 'IN', $names)
            ->fetchAll();

        return $this->getItemsIndexedByName($rawItems);
    }

    public function get(string $name): Permission|Role|null
    {
        /** @psalm-var RawItem|null $row */
        $row = $this
            ->database
            ->select()
            ->from($this->tableName)
            ->where(['name' => $name])
            ->run()
            ->fetch();

        return empty($row) ? null : $this->createItem(...$row);
    }

    public function exists(string $name): bool
    {
        /**
         * @psalm-var array<0, 1>|false $result
         * @infection-ignore-all
         * - ArrayItemRemoval, select.
         * - IncrementInteger, limit.
         */
        $result = $this
            ->database
            ->select([new Fragment('1 AS item_exists')])
            ->from($this->tableName)
            ->where(['name' => $name])
            ->limit(1)
            ->run()
            ->fetch();

        return $result !== false;
    }

    public function roleExists(string $name): bool
    {
        /**
         * @psalm-var array<0, 1>|false $result
         * @infection-ignore-all
         * - ArrayItemRemoval, select.
         * - IncrementInteger, limit.
         */
        $result = $this
            ->database
            ->select([new Fragment('1 AS role_exists')])
            ->from($this->tableName)
            ->where(['name' => $name, 'type' => Item::TYPE_ROLE])
            ->limit(1)
            ->run()
            ->fetch();

        return $result !== false;
    }

    public function add(Permission|Role $item): void
    {
        $time = time();

        if (!$item->hasCreatedAt()) {
            $item = $item->withCreatedAt($time);
        }

        if (!$item->hasUpdatedAt()) {
            $item = $item->withUpdatedAt($time);
        }

        $this
            ->database
            ->insert($this->tableName)
            ->values($item->getAttributes())
            ->run();
    }

    public function update(string $name, Permission|Role $item): void
    {
        $itemsStorage = $this;
        $this
            ->database
            ->transaction(static function (Database $database) use ($itemsStorage, $name, $item): void {
                $itemsChildren = $database
                    ->select()
                    ->from($itemsStorage->childrenTableName)
                    ->where(['parent' => $name])
                    ->orWhere(['child' => $name])
                    ->fetchAll();
                if ($itemsChildren !== []) {
                    $itemsStorage->removeRelatedItemsChildren($database, $name);
                }

                $database
                    ->update($itemsStorage->tableName, $item->getAttributes(), ['name' => $name])
                    ->run();

                if ($itemsChildren !== []) {
                    $itemsChildren = array_map(
                        static function (array $itemChild) use ($name, $item): array {
                            if ($itemChild['parent'] === $name) {
                                $itemChild['parent'] = $item->getName();
                            }

                            if ($itemChild['child'] === $name) {
                                $itemChild['child'] = $item->getName();
                            }

                            return [$itemChild['parent'], $itemChild['child']];
                        },
                        $itemsChildren,
                    );
                    $database
                        ->insert($itemsStorage->childrenTableName)
                        ->columns(['parent', 'child'])
                        ->values($itemsChildren)
                        ->run();
                }
            });
    }

    public function remove(string $name): void
    {
        $itemsStorage = $this;
        $this
            ->database
            ->transaction(static function (Database $database) use ($itemsStorage, $name): void {
                $itemsStorage->removeRelatedItemsChildren($database, $name);
                $database
                    ->delete($itemsStorage->tableName, ['name' => $name])
                    ->run();
            });
    }

    public function getRoles(): array
    {
        return $this->getItemsByType(Item::TYPE_ROLE);
    }

    public function getRolesByNames(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        /** @psalm-var RawRole[] $rawItems */
        $rawItems = $this
            ->database
            ->select()
            ->from($this->tableName)
            ->where(['type' => Item::TYPE_ROLE])
            ->andWhere('name', 'IN', $names)
            ->fetchAll();

        /** @psalm-var array<string, Role> */
        return $this->getItemsIndexedByName($rawItems);
    }

    public function getRole(string $name): ?Role
    {
        return $this->getItemByTypeAndName(Item::TYPE_ROLE, $name);
    }

    public function clearRoles(): void
    {
        $this->clearItemsByType(Item::TYPE_ROLE);
    }

    public function getPermissions(): array
    {
        return $this->getItemsByType(Item::TYPE_PERMISSION);
    }

    public function getPermissionsByNames(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        /** @psalm-var RawPermission[] $rawItems */
        $rawItems = $this
            ->database
            ->select()
            ->from($this->tableName)
            ->where(['type' => Item::TYPE_PERMISSION])
            ->andWhere('name', 'IN', $names)
            ->fetchAll();

        /** @psalm-var array<string, Permission> */
        return $this->getItemsIndexedByName($rawItems);
    }

    public function getPermission(string $name): ?Permission
    {
        return $this->getItemByTypeAndName(Item::TYPE_PERMISSION, $name);
    }

    public function clearPermissions(): void
    {
        $this->clearItemsByType(Item::TYPE_PERMISSION);
    }

    public function getParents(string $name): array
    {
        $rawItems = $this->getTreeTraversal()->getParentRows($name);

        return $this->getItemsIndexedByName($rawItems);
    }

    public function getAccessTree(string $name): array
    {
        $tree = [];
        foreach ($this->getTreeTraversal()->getAccessTree($name) as $data) {
            $childrenNames = $data['children'] !== '' ? explode(',', $data['children']) : [];
            unset($data['children']);

            $tree[$data['name']] = ['item' => $this->createItem(...$data), 'childrenNames' => $childrenNames];
        }

        foreach ($tree as $index => $data) {
            $children = [];
            foreach ($data['childrenNames'] as $childrenName) {
                $children[$childrenName] = $tree[$childrenName]['item'];
            }

            unset($tree[$index]['childrenNames']);
            $tree[$index]['children'] = $children;
        }

        return $tree;
    }

    public function getDirectChildren(string $name): array
    {
        /** @psalm-var RawItem[] $rawItems */
        $rawItems = $this
            ->database
            ->select($this->tableName . '.*')
            ->from($this->tableName)
            ->leftJoin($this->childrenTableName)
            ->on($this->childrenTableName . '.child', $this->tableName . '.name')
            ->where(['parent' => $name])
            ->fetchAll();

        return $this->getItemsIndexedByName($rawItems);
    }

    public function getAllChildren(string|array $names): array
    {
        $rawItems = $this->getTreeTraversal()->getChildrenRows($names);

        return $this->getItemsIndexedByName($rawItems);
    }

    public function getAllChildPermissions(string|array $names): array
    {
        $rawItems = $this->getTreeTraversal()->getChildPermissionRows($names);

        /** @psalm-var array<string, Permission> */
        return $this->getItemsIndexedByName($rawItems);
    }

    public function getAllChildRoles(string|array $names): array
    {
        $rawItems = $this->getTreeTraversal()->getChildRoleRows($names);

        /** @psalm-var array<string, Role> */
        return $this->getItemsIndexedByName($rawItems);
    }

    public function hasChildren(string $name): bool
    {
        /**
         * @psalm-var array<0, 1>|false $result
         * @infection-ignore-all
         * - ArrayItemRemoval, select.
         * - IncrementInteger, limit.
         */
        $result = $this
            ->database
            ->select([new Fragment('1 AS item_child_exists')])
            ->from($this->childrenTableName)
            ->where(['parent' => $name])
            ->limit(1)
            ->run()
            ->fetch();

        return $result !== false;
    }

    public function hasChild(string $parentName, string $childName): bool
    {
        return $this->getTreeTraversal()->hasChild($parentName, $childName);
    }

    public function hasDirectChild(string $parentName, string $childName): bool
    {
        /**
         * @psalm-var array<0, 1>|false $result
         * @infection-ignore-all
         * - ArrayItemRemoval, select.
         * - IncrementInteger, limit.
         */
        $result = $this
            ->database
            ->select([new Fragment('1 AS item_child_exists')])
            ->from($this->childrenTableName)
            ->where(['parent' => $parentName, 'child' => $childName])
            ->limit(1)
            ->run()
            ->fetch();

        return $result !== false;
    }

    public function addChild(string $parentName, string $childName): void
    {
        $this
            ->database
            ->insert($this->childrenTableName)
            ->values(['parent' => $parentName, 'child' => $childName])
            ->run();
    }

    public function removeChild(string $parentName, string $childName): void
    {
        $this
            ->database
            ->delete($this->childrenTableName, ['parent' => $parentName, 'child' => $childName])
            ->run();
    }

    public function removeChildren(string $parentName): void
    {
        $this
            ->database
            ->delete($this->childrenTableName, ['parent' => $parentName])
            ->run();
    }

    /**
     * Gets either all existing roles or permissions, depending on specified type.
     *
     * @param string $type Either {@see Item::TYPE_ROLE} or {@see Item::TYPE_PERMISSION}.
     * @psalm-param Item::TYPE_* $type
     *
     * @return array A list of roles / permissions.
     * @psalm-return ($type is Item::TYPE_PERMISSION ? array<string, Permission> : array<string, Role>)
     */
    private function getItemsByType(string $type): array
    {
        /** @psalm-var RawItem[] $rawItems */
        $rawItems = $this
            ->database
            ->select()
            ->from($this->tableName)
            ->where(['type' => $type])
            ->fetchAll();

        return $this->getItemsIndexedByName($rawItems);
    }

    /**
     * Gets single item by its type and name.
     *
     * @param string $type Either {@see Item::TYPE_ROLE} or {@see Item::TYPE_PERMISSION}.
     * @psalm-param Item::TYPE_* $type
     *
     * @return Permission|Role|null Either role or permission, depending on initial type specified. `null` is returned
     * when no item was found by given condition.
     * @psalm-return ($type is Item::TYPE_PERMISSION ? Permission : Role)|null
     */
    private function getItemByTypeAndName(string $type, string $name): Permission|Role|null
    {
        /**
         * @psalm-var RawItem|null $row
         * @infection-ignore-all
         * - ArrayItemRemoval, where, type.
         */
        $row = $this
            ->database
            ->select()
            ->from($this->tableName)
            ->where(['type' => $type, 'name' => $name])
            ->run()
            ->fetch();

        return empty($row) ? null : $this->createItem(...$row);
    }

    /**
     * A factory method for creating single item with all attributes filled.
     *
     * @param string $type Either {@see Item::TYPE_ROLE} or {@see Item::TYPE_PERMISSION}.
     * @psalm-param Item::TYPE_* $type
     *
     * @param string $name Unique name.
     * @param int|string $createdAt UNIX timestamp for creation time.
     * @param int|string $updatedAt UNIX timestamp for updating time.
     * @param string|null $description Optional description.
     * @param string|null $ruleName Optional associated rule name.
     *
     * @return Permission|Role Either role or permission, depending on initial type specified.
     * @psalm-return ($type is Item::TYPE_PERMISSION ? Permission : Role)
     */
    private function createItem(
        string $type,
        string $name,
        int|string $createdAt,
        int|string $updatedAt,
        string|null $description = null,
        string|null $ruleName = null,
    ): Permission|Role {
        return $this
            ->createItemByTypeAndName($type, $name)
            ->withDescription($description ?? '')
            ->withRuleName($ruleName ?? null)
            ->withCreatedAt((int) $createdAt)
            ->withUpdatedAt((int) $updatedAt);
    }

    /**
     * A basic factory method for creating single item with name only.
     *
     * @param string $type Either {@see Item::TYPE_ROLE} or {@see Item::TYPE_PERMISSION}.
     * @psalm-param Item::TYPE_* $type
     *
     * @return Permission|Role Either role or permission, depending on initial type specified.
     * @psalm-return ($type is Item::TYPE_PERMISSION ? Permission : Role)
     */
    private function createItemByTypeAndName(string $type, string $name): Permission|Role
    {
        return $type === Item::TYPE_PERMISSION ? new Permission($name) : new Role($name);
    }

    /**
     * Removes all related records in items children table for a given item name.
     *
     * @param Database $database Cycle database instance.
     * @param string $name Item name.
     */
    private function removeRelatedItemsChildren(Database $database, string $name): void
    {
        $database
            ->delete()
            ->from($this->childrenTableName)
            ->where(['parent' => $name])
            ->orWhere(['child' => $name])
            ->run();
    }

    /**
     * Removes all existing items of specified type.
     *
     * @param string $type Either {@see Item::TYPE_ROLE} or {@see Item::TYPE_PERMISSION}.
     * @psalm-param Item::TYPE_* $type
     */
    private function clearItemsByType(string $type): void
    {
        $itemsStorage = $this;
        $this
            ->database
            ->transaction(static function (Database $database) use ($itemsStorage, $type): void {
                $parentsSubQuery = $database
                    ->select('parents.parent')
                    ->from(
                        new Fragment(
                            '(' .
                            $database
                                ->select('parent')
                                ->distinct()
                                ->from($itemsStorage->childrenTableName) .
                            ') AS parents',
                        ),
                    )
                    ->leftJoin($itemsStorage->tableName, 'parent_items')
                    ->on('parent_items.name', 'parents.parent')
                    ->where(['parent_items.type' => $type]);
                $childrenSubQuery = $database
                    ->select('children.child')
                    ->from(
                        new Fragment(
                            '(' .
                            $database
                                ->select('child')
                                ->distinct()
                                ->from($itemsStorage->childrenTableName) .
                            ') AS children',
                        ),
                    )
                    ->leftJoin($itemsStorage->tableName, 'child_items')
                    ->on('child_items.name', 'children.child')
                    ->where(['child_items.type' => $type]);
                $database
                    ->delete()
                    ->from($itemsStorage->childrenTableName)
                    ->where('parent', 'IN', $parentsSubQuery)
                    ->orWhere('child', 'IN', $childrenSubQuery)
                    ->run();
                $database
                    ->delete($itemsStorage->tableName, ['type' => $type])
                    ->run();
            });
    }

    /**
     * Creates RBAC item tree traversal strategy and returns it. In case it was already created, just retrieves
     * previously saved instance.
     */
    private function getTreeTraversal(): ItemTreeTraversalInterface
    {
        if ($this->treeTraversal === null) {
            $this->treeTraversal = ItemTreeTraversalFactory::getItemTreeTraversal(
                $this->database,
                $this->tableName,
                $this->childrenTableName,
            );
        }

        return $this->treeTraversal;
    }

    /**
     * @psalm-param RawItem[] $rawItems
     * @psalm-return ItemsIndexedByName
     */
    private function getItemsIndexedByName(array $rawItems): array
    {
        $items = [];

        foreach ($rawItems as $rawItem) {
            $items[$rawItem['name']] = $this->createItem(...$rawItem);
        }

        return $items;
    }
}
