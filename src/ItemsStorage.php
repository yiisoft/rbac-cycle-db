<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\Database;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Injection\Fragment;
use InvalidArgumentException;
use Yiisoft\Rbac\Cycle\Exception\SeparatorCollisionException;
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
 *     rule_name: string|null,
 *     created_at: int|string,
 *     updated_at: int|string
 * }
 * @psalm-type RawRole = array{
 *     type: Item::TYPE_ROLE,
 *     name: string,
 *     description: string|null,
 *     rule_name: string|null,
 *     created_at: int|string,
 *     updated_at: int|string
 *  }
 * @psalm-type RawPermission = array{
 *     type: Item::TYPE_PERMISSION,
 *     name: string,
 *     description: string|null,
 *     rule_name: string|null,
 *     created_at: int|string,
 *     updated_at: int|string
 * }
 */
final class ItemsStorage implements ItemsStorageInterface
{
    /**
     * @var string Separator used for joining and splitting item names.
     * @psalm-var non-empty-string
     */
    private string $namesSeparator;
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
     *
     * @param string $namesSeparator Separator used for joining and splitting item names.
     */
    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly string $tableName = 'yii_rbac_item',
        private readonly string $childrenTableName = 'yii_rbac_item_child',
        string $namesSeparator = ',',
    ) {
        $this->assertNamesSeparator($namesSeparator);
        $this->namesSeparator = $namesSeparator;
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

        return $this->getItemsIndexedByName($rows);
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
            ->where('name', 'IN', $names)
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

        return empty($row) ? null : $this->createItem($row);
    }

    public function exists(string $name): bool
    {
        /**
         * @psalm-var array<0, 1>|false $result
         * @infection-ignore-all
         * - ArrayItemRemoval, select.
         */
        $result = $this
            ->database
            ->select([new Fragment('1 AS item_exists')])
            ->from($this->tableName)
            ->where(['name' => $name])
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
         */
        $result = $this
            ->database
            ->select([new Fragment('1 AS role_exists')])
            ->from($this->tableName)
            ->where(['name' => $name, 'type' => Item::TYPE_ROLE])
            ->run()
            ->fetch();

        return $result !== false;
    }

    public function add(Permission|Role $item): void
    {
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

    public function getHierarchy(string $name): array
    {
        $tree = [];
        $childrenNamesMap = [];

        foreach ($this->getTreeTraversal()->getHierarchy($name) as $data) {
            $childrenNamesMap[$data['name']] = $data['children'] !== ''
                ? explode($this->namesSeparator, $data['children'])
                : [];
            unset($data['children']);
            $tree[$data['name']] = ['item' => $this->createItem($data)];
        }

        foreach ($tree as $index => $_item) {
            $children = [];
            foreach ($childrenNamesMap[$index] as $childrenName) {
                if (!isset($tree[$childrenName])) {
                    throw new SeparatorCollisionException();
                }

                $children[$childrenName] = $tree[$childrenName]['item'];
            }

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
        if (is_array($names) && empty($names)) {
            return [];
        }

        $rawItems = $this->getTreeTraversal()->getChildrenRows($names);

        return $this->getItemsIndexedByName($rawItems);
    }

    public function getAllChildPermissions(string|array $names): array
    {
        if (is_array($names) && empty($names)) {
            return [];
        }

        $rawItems = $this->getTreeTraversal()->getChildPermissionRows($names);

        /** @psalm-var array<string, Permission> */
        return $this->getItemsIndexedByName($rawItems);
    }

    public function getAllChildRoles(string|array $names): array
    {
        if (is_array($names) && empty($names)) {
            return [];
        }

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
         */
        $result = $this
            ->database
            ->select([new Fragment('1 AS item_child_exists')])
            ->from($this->childrenTableName)
            ->where(['parent' => $name])
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
         */
        $result = $this
            ->database
            ->select([new Fragment('1 AS item_child_exists')])
            ->from($this->childrenTableName)
            ->where(['parent' => $parentName, 'child' => $childName])
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
     * Gets either all existing roles or permissions, depending on a specified type.
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
     * Gets a single item by its type and name.
     *
     * @param string $type Either {@see Item::TYPE_ROLE} or {@see Item::TYPE_PERMISSION}.
     * @psalm-param Item::TYPE_* $type
     *
     * @return Permission|Role|null Either role or permission, depending on an initial type specified. `null` is returned
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

        return empty($row) ? null : $this->createItem($row);
    }

    /**
     * A factory method for creating single item with all attributes filled.
     *
     * @psalm-param RawPermission|RawRole $rawItem
     *
     * @return Permission|Role Either role or permission, depending on an initial type specified.
     */
    private function createItem(array $rawItem): Permission|Role
    {
        return $this
            ->createItemByTypeAndName($rawItem['type'], $rawItem['name'])
            ->withDescription($rawItem['description'] ?? '')
            ->withRuleName($rawItem['rule_name'] ?? null)
            ->withCreatedAt((int) $rawItem['created_at'])
            ->withUpdatedAt((int) $rawItem['updated_at']);
    }

    /**
     * A basic factory method for creating a single item with name only.
     *
     * @param string $type Either {@see Item::TYPE_ROLE} or {@see Item::TYPE_PERMISSION}.
     * @psalm-param Item::TYPE_* $type
     *
     * @return Permission|Role Either role or permission, depending on an initial type specified.
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
     * Removes all existing items of a specified type.
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
     * Creates RBAC item tree traversal strategy and returns it.
     * In case it was already created, it just retrieves previously saved instance.
     */
    private function getTreeTraversal(): ItemTreeTraversalInterface
    {
        if ($this->treeTraversal === null) {
            $this->treeTraversal = ItemTreeTraversalFactory::getItemTreeTraversal(
                $this->database,
                $this->tableName,
                $this->childrenTableName,
                $this->namesSeparator,
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
            $items[$rawItem['name']] = $this->createItem($rawItem);
        }

        return $items;
    }

    /**
     * @psalm-assert non-empty-string $namesSeparator
     */
    private function assertNamesSeparator(string $namesSeparator): void
    {
        if (strlen($namesSeparator) !== 1) {
            throw new InvalidArgumentException('Names separator must be exactly 1 character long.');
        }
    }
}
