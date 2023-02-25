<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Table;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;

/**
 * @psalm-type RawItem = array{
 *     type: Item::TYPE_*,
 *     name: string,
 *     description: string|null,
 *     ruleName: string|null,
 *     createdAt: int|string,
 *     updatedAt: int|string
 * }
 */
final class ItemsStorage implements ItemsStorageInterface
{
    private DatabaseInterface $database;
    /**
     * @psalm-var non-empty-string
     */
    private string $childrenTableName;

    /**
     * @param non-empty-string|null $childrenTableName
     */
    public function __construct(
        /**
         * @psalm-var non-empty-string
         */
        private string $tableName,
        DatabaseProviderInterface $dbal,
        ?string $childrenTableName = null
    ) {
        $this->database = $dbal->database();
        $this->childrenTableName = $childrenTableName ?? $tableName . '_child';
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        if ($this->database->hasTable($this->tableName)) {
            /** @var Table $table */
            $table = $this->database->table($this->tableName);
            $table->eraseData();
        }
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        /** @psalm-var RawItem[] $rows */
        $rows = $this->database->select()->from($this->tableName)->fetchAll();

        return array_map(
            fn (array $row): Item => $this->createItem($row),
            $rows
        );
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): ?Item
    {
        /** @psalm-var RawItem|null $row */
        $row = $this->database
            ->select()
            ->from($this->tableName)
            ->where(['name' => $name])
            ->run()
            ->fetch();

        return empty($row) ? null : $this->createItem($row);
    }

    public function exists(string $name): bool
    {
        /** @var mixed $result */
        $result = $this
            ->database
            ->select([new Fragment('1')])
            ->from($this->tableName)
            ->where(['name' => $name])
            ->limit(1)
            ->run()
            ->fetch();

        return $result !== false;
    }

    /**
     * @inheritDoc
     */
    public function add(Item $item): void
    {
        $time = time();
        if (!$item->hasCreatedAt()) {
            $item = $item->withCreatedAt($time);
        }
        if (!$item->hasUpdatedAt()) {
            $item = $item->withUpdatedAt($time);
        }
        $this->database
            ->insert($this->tableName)
            ->values($item->getAttributes())
            ->run();
    }

    /**
     * @inheritDoc
     */
    public function update(string $name, Item $item): void
    {
        $this->database
            ->update($this->tableName, $item->getAttributes(), ['name' => $name])
            ->run();
    }

    /**
     * @inheritDoc
     */
    public function remove(string $name): void
    {
        $this->database
            ->delete($this->tableName, ['name' => $name])
            ->run();
    }

    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        return $this->getItemsByType(Item::TYPE_ROLE);
    }

    /**
     * @inheritDoc
     */
    public function getRole(string $name): ?Role
    {
        return $this->getItemByTypeAndName(Item::TYPE_ROLE, $name);
    }

    /**
     * @inheritDoc
     */
    public function clearRoles(): void
    {
        $this->database->delete($this->tableName, ['type' => Item::TYPE_ROLE])->run();
    }

    /**
     * @inheritDoc
     */
    public function getPermissions(): array
    {
        return $this->getItemsByType(Item::TYPE_PERMISSION);
    }

    /**
     * @inheritDoc
     */
    public function getPermission(string $name): ?Permission
    {
        return $this->getItemByTypeAndName(Item::TYPE_PERMISSION, $name);
    }

    /**
     * @inheritDoc
     */
    public function clearPermissions(): void
    {
        $this->database
            ->delete($this->tableName, ['type' => Item::TYPE_PERMISSION])
            ->run();
    }

    /**
     * @inheritDoc
     */
    public function getParents(string $name): array
    {
        /** @psalm-var RawItem[] $parentRows */
        $parentRows = $this->database
            ->select()
            ->from([$this->tableName, $this->childrenTableName])
            ->where(['child' => $name, 'name' => new Expression('parent')])
            ->fetchAll();

        return array_combine(
            array_column($parentRows, 'name'),
            array_map(
                fn (array $row): Item => $this->createItem($row),
                $parentRows
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function getChildren(string $name): array
    {
        /** @psalm-var RawItem[] $childrenRows */
        $childrenRows = $this->database
            ->select()
            ->from([$this->tableName, $this->childrenTableName])
            ->where(['parent' => $name, 'name' => new Expression('child')])
            ->fetchAll();

        $keys = array_column($childrenRows, 'name');
        return array_combine(
            $keys,
            array_map(
                fn (array $row): Item => $this->createItem($row),
                $childrenRows
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function hasChildren(string $name): bool
    {
        /** @var mixed $result */
        $result = $this
            ->database
            ->select([new Fragment('1')])
            ->from($this->childrenTableName)
            ->where(['parent' => $name])
            ->limit(1)
            ->run()
            ->fetch();

        return $result !== false;
    }

    /**
     * @inheritDoc
     */
    public function addChild(string $parentName, string $childName): void
    {
        $this->database
            ->insert($this->childrenTableName)
            ->values(['parent' => $parentName, 'child' => $childName])
            ->run();
    }

    /**
     * @inheritDoc
     */
    public function removeChild(string $parentName, string $childName): void
    {
        $this->database
            ->delete($this->childrenTableName, ['parent' => $parentName, 'child' => $childName])
            ->run();
    }

    /**
     * @inheritDoc
     */
    public function removeChildren(string $parentName): void
    {
        $this->database
            ->delete($this->childrenTableName, ['parent' => $parentName])
            ->run();
    }

    /**
     * @psalm-return ($type is Item::TYPE_PERMISSION ? Permission[] : ($type is Item::TYPE_ROLE ? Role[] : Item[]))
     */
    private function getItemsByType(string $type): array
    {
        /** @psalm-var RawItem[] $rows */
        $rows = $this->database
            ->select()
            ->from($this->tableName)
            ->where(['type' => $type])
            ->fetchAll();

        return array_map(
            fn (array $row): Item => $this->createItem($row),
            $rows
        );
    }

    /**
     * @psalm-param Item::TYPE_* $type
     * @psalm-return ($type is Item::TYPE_PERMISSION ? Permission : Role)|null
     */
    private function getItemByTypeAndName(string $type, string $name): Permission|Role|null
    {
        /** @psalm-var RawItem|null $row */
        $row = $this->database
            ->select()
            ->from($this->tableName)
            ->where(['type' => $type, 'name' => $name])
            ->run()
            ->fetch();

        return empty($row) ? null : $this->createItem($row);
    }

    /**
     * @psalm-param RawItem $attributes
     */
    private function createItem(array $attributes): Permission|Role
    {
        return $this->createItemByTypeAndName($attributes['type'], $attributes['name'])
            ->withDescription($attributes['description'] ?? '')
            ->withRuleName($attributes['ruleName'] ?? null)
            ->withCreatedAt((int) $attributes['createdAt'])
            ->withUpdatedAt((int) $attributes['updatedAt']);
    }

    /**
     * @psalm-param Item::TYPE_* $type
     * @psalm-return ($type is Item::TYPE_PERMISSION ? Permission : Role)
     */
    private function createItemByTypeAndName(string $type, string $name): Permission|Role
    {
        return $type === Item::TYPE_PERMISSION ? new Permission($name) : new Role($name);
    }
}
