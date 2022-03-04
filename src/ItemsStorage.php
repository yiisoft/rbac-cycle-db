<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Table;
use Cycle\Database\TableInterface;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;

final class ItemsStorage implements ItemsStorageInterface
{
    private DatabaseInterface $database;
    private Table|TableInterface $table;
    private Table|TableInterface $childrenTable;

    /**
     * @param non-empty-string $tableName
     * @param DatabaseProviderInterface $dbal
     * @param non-empty-string|null $childrenTableName
     */
    public function __construct(string $tableName, DatabaseProviderInterface $dbal, ?string $childrenTableName = null)
    {
        $this->database = $dbal->database();
        $this->table = $this->database->table($tableName);
        $this->childrenTable = $this->database->table($childrenTableName ?? $tableName . '_child');
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        if ($this->database->hasTable($this->table->getName())) {
            $this->table->eraseData();
        }
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return array_map(fn (array $item): Item => $this->populateItem($item), $this->table->fetchAll());
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): ?Item
    {
        $item = $this->table->select()->where(['name' => $name])->run()->fetch();
        if (!empty($item)) {
            return $this->populateItem($item);
        }
        return null;
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
        $this->table->insertOne($item->getAttributes());
    }

    /**
     * @inheritDoc
     */
    public function update(string $name, Item $item): void
    {
        $this->table->update($item->getAttributes(), ['name' => $name])->run();
    }

    /**
     * @inheritDoc
     */
    public function remove(string $name): void
    {
        $this->table->delete(['name' => $name])->run();
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
        $this->table->delete(['type' => Item::TYPE_ROLE])->run();
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
        $this->table->delete(['type' => Item::TYPE_PERMISSION])->run();
    }

    /**
     * @inheritDoc
     */
    public function getParents(string $name): array
    {
        $parents = $this->database
            ->select()
            ->from([$this->table->getName(), $this->childrenTable->getName()])
            ->where(['child' => $name, 'name' => new Expression('parent')])
            ->fetchAll();
        return array_map(fn (array $item): Item => $this->populateItem($item), $parents);
    }

    /**
     * @inheritDoc
     */
    public function getChildren(string $name): array
    {
        $children = $this->database
            ->select()
            ->from([$this->table->getName(), $this->childrenTable->getName()])
            ->where(['parent' => $name, 'name' => new Expression('child')])
            ->fetchAll();

        return array_map(fn (array $item): Item => $this->populateItem($item), $children);
    }

    /**
     * @inheritDoc
     */
    public function hasChildren(string $name): bool
    {
        return $this->childrenTable->select('parent')->where(['parent' => $name])->count() > 0;
    }

    /**
     * @inheritDoc
     */
    public function addChild(string $parentName, string $childName): void
    {
        $this->childrenTable->insertOne(['parent' => $parentName, 'child' => $childName]);
    }

    /**
     * @inheritDoc
     */
    public function removeChild(string $parentName, string $childName): void
    {
        $this->childrenTable
            ->delete(['parent' => $parentName, 'child' => $childName])
            ->run();
    }

    /**
     * @inheritDoc
     */
    public function removeChildren(string $parentName): void
    {
        $this->childrenTable->delete(['parent' => $parentName])->run();
    }

    /**
     * @psalm-return ($type is Item::TYPE_PERMISSION ? Permission[] : ($type is Item::TYPE_ROLE ? Role[] : Item[]))
     */
    private function getItemsByType(string $type): array
    {
        $items = $this->table->select()->where(['type' => $type])->fetchAll();

        return array_map(fn (array $item): Item => $this->populateItem($item), $items);
    }

    /**
     * @psalm-return ($type is Item::TYPE_PERMISSION ? Permission : ($type is Item::TYPE_ROLE ? Role : Item))|null
     */
    private function getItemByTypeAndName(string $type, string $name): ?Item
    {
        $item = $this->table->select()->where(['type' => $type, 'name' => $name])->run()->fetch();

        if (empty($item)) {
            return null;
        }

        return $this->populateItem($item);
    }

    /**
     * @psalm-param array{type: string, name: string, description?: string, ruleName?: string, createdAt: int|string, updatedAt: int|string} $attributes
     */
    private function populateItem(array $attributes): Item
    {
        return $this->createItemByTypeAndName($attributes['type'], $attributes['name'])
            ->withDescription($attributes['description'] ?? '')
            ->withRuleName($attributes['ruleName'] ?? '')
            ->withCreatedAt((int)$attributes['createdAt'])
            ->withUpdatedAt((int)$attributes['updatedAt']);
    }

    /**
     * @psalm-return ($type is Item::TYPE_PERMISSION ? Permission : ($type is Item::TYPE_ROLE ? Role : Item))
     */
    private function createItemByTypeAndName(string $type, string $name): Item
    {
        return $type === Item::TYPE_PERMISSION ? new Permission($name) : new Role($name);
    }
}
