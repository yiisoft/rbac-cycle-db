<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Table;
use Cycle\Database\TableInterface;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;

class ItemsStorage implements ItemsStorageInterface
{
    private DatabaseInterface $database;
    /**
     * @var Table
     */
    private TableInterface $table;
    /**
     * @var Table
     */
    private TableInterface $childrenTable;

    public function __construct(string $tableName, DatabaseInterface $database, ?string $childrenTable)
    {
        $this->database = $database;
        $this->table = $database->table($tableName);
        $this->childrenTable = $database->table($childrenTable ?? $tableName . '_children');
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
        return $this->populateItem($item);
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

        if ($role !== []) {
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
        $permission = $this->table->select()->where(['name' => $name, 'type' => Item::TYPE_PERMISSION])->run()->fetch();

        if ($permission !== []) {
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
        // TODO: Implement getParents() method.
    }

    /**
     * @inheritDoc
     */
    public function getChildren(string $name): array
    {
        // TODO: Implement getChildren() method.
    }

    /**
     * @inheritDoc
     */
    public function hasChildren(string $name): bool
    {
        // TODO: Implement hasChildren() method.
    }

    /**
     * @inheritDoc
     */
    public function addChild(string $parentName, string $childName): void
    {
        // TODO: Implement addChild() method.
    }

    /**
     * @inheritDoc
     */
    public function removeChild(string $parentName, string $childName): void
    {
        // TODO: Implement removeChild() method.
    }

    /**
     * @inheritDoc
     */
    public function removeChildren(string $parentName): void
    {
        // TODO: Implement removeChildren() method.
    }

    /**
     * @param array $item
     *
     * @return Permission|Role
     */
    private function populateItem(array $item): Item
    {
        return $this->createItemByTypeAndName($item['type'], $item['name'])
            ->withDescription($item['description'] ?? '')
            ->withRuleName($item['rule_name'] ?? '')
            ->withCreatedAt($item['created_at'])
            ->withUpdatedAt($item['updated_at']);
    }

    private function createItemByTypeAndName(string $type, string $name): Item
    {
        return $type === Item::TYPE_PERMISSION ? new Permission($name) : new Role($name);
    }
}
