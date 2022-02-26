<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Table;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;

final class ItemsStorage implements ItemsStorageInterface
{
    private DatabaseInterface $database;
    private Table $table;
    private Table $childrenTable;

    public function __construct(string $tableName, DatabaseProviderInterface $dbal, ?string $childrenTable = null)
    {
        $this->database = $dbal->database();
        $this->table = $this->database->table($tableName);
        $this->childrenTable = $this->database->table($childrenTable ?? $tableName . '_child');
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
        return array_map(fn (array $item) => $this->populateItem($item), $this->table->fetchAll());
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
        $roles = $this->table->select()->where(['type' => Item::TYPE_ROLE])->fetchAll();
        return array_map(fn (array $item) => $this->populateItem($item), $roles);
    }

    /**
     * @inheritDoc
     */
    public function getRole(string $name): ?Role
    {
        $role = $this->table->select()->where(['name' => $name, 'type' => Item::TYPE_ROLE])->run()->fetch();

        if (!empty($role)) {
            return $this->populateItem($role);
        }
        return null;
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
        $permissions = $this->table->select()->where(['type' => Item::TYPE_PERMISSION])->fetchAll();
        return array_map(fn (array $item) => $this->populateItem($item), $permissions);
    }

    /**
     * @inheritDoc
     */
    public function getPermission(string $name): ?Permission
    {
        $permission = $this->table
            ->select()
            ->where(['name' => $name, 'type' => Item::TYPE_PERMISSION])
            ->run()
            ->fetch();

        if (!empty($permission)) {
            return $this->populateItem($permission);
        }
        return null;
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
        return array_map(fn (array $item) => $this->populateItem($item), $parents);
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

        return array_map(fn (array $item) => $this->populateItem($item), $children);
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
     * @psalm-param array{type: string, name: string, description?: string, ruleName?: string, createdAt: int, updatedAt: int} $attributes
     * @psalm-return ($attributes['type'] is Item::TYPE_PERMISSION ? Permission : ($attributes['type'] is Item::TYPE_ROLE ? Role : Item))
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
