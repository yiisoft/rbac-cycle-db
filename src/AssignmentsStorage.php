<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Table;
use Cycle\Database\TableInterface;
use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\AssignmentsStorageInterface;

final class AssignmentsStorage implements AssignmentsStorageInterface
{
    private Table|TableInterface $table;

    public function __construct(string $tableName, DatabaseProviderInterface $dbal)
    {
        $this->table = $dbal->database()->table($tableName);
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        $assignments = [];
        foreach ($this->table->select()->fetchAll() as $items) {
            foreach ($items as $item) {
                $assignments[$item['user_id']][$item['item_name']] = new Assignment(
                    $item['user_id'],
                    $item['item_name'],
                    $item['created_at']
                );
            }
        }

        return $assignments;
    }

    /**
     * @inheritDoc
     */
    public function getByUserId(string $userId): array
    {
        $assignments = $this->table->select()->where(['user_id' => $userId])->fetchAll();

        return array_map(
            static fn (array $item) => new Assignment($userId, $item['item_name'], $item['created_at']),
            $assignments
        );
    }

    /**
     * @inheritDoc
     */
    public function get(string $itemName, string $userId): ?Assignment
    {
        $assignment = $this->table
            ->select()
            ->where(['item_name' => $itemName, 'user_id' => $userId])
            ->run()
            ->fetch();
        if (!empty($assignment)) {
            return new Assignment($userId, $itemName, $assignment['created_at']);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function add(string $itemName, string $userId): void
    {
        $this->table->insertOne(
            [
                'item_name' => $itemName,
                'user_id' => $userId,
                'created_at' => time(),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function hasItem(string $name): bool
    {
        return $this->table->select('item_name')->where(['item_name' => $name])->count() > 0;
    }

    /**
     * @inheritDoc
     */
    public function renameItem(string $oldName, string $newName): void
    {
        if ($oldName === $newName) {
            return;
        }
        $this->table->update(['item_name' => $newName], ['item_name' => $oldName])->run();
    }

    /**
     * @inheritDoc
     */
    public function remove(string $itemName, string $userId): void
    {
        $this->table->delete(['item_name' => $itemName, 'user_id' => $userId])->run();
    }

    /**
     * @inheritDoc
     */
    public function removeByUserId(string $userId): void
    {
        $this->table->delete(['user_id' => $userId])->run();
    }

    /**
     * @inheritDoc
     */
    public function removeByItemName(string $itemName): void
    {
        $this->table->delete(['item_name' => $itemName])->run();
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->table->eraseData();
    }
}
