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
                $assignments[$item['userId']][$item['itemName']] = new Assignment(
                    $item['userId'],
                    $item['itemName'],
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
        $assignments = $this->table->select()->where(['userId' => $userId])->fetchAll();

        return array_map(
            static fn (array $item) => new Assignment($userId, $item['itemName'], $item['created_at']),
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
            ->where(['itemName' => $itemName, 'userId' => $userId])
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
                'itemName' => $itemName,
                'userId' => $userId,
                'created_at' => time(),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function hasItem(string $name): bool
    {
        return $this->table->select('itemName')->where(['itemName' => $name])->count() > 0;
    }

    /**
     * @inheritDoc
     */
    public function renameItem(string $oldName, string $newName): void
    {
        if ($oldName === $newName) {
            return;
        }
        $this->table->update(['itemName' => $newName], ['itemName' => $oldName])->run();
    }

    /**
     * @inheritDoc
     */
    public function remove(string $itemName, string $userId): void
    {
        $this->table->delete(['itemName' => $itemName, 'userId' => $userId])->run();
    }

    /**
     * @inheritDoc
     */
    public function removeByUserId(string $userId): void
    {
        $this->table->delete(['userId' => $userId])->run();
    }

    /**
     * @inheritDoc
     */
    public function removeByItemName(string $itemName): void
    {
        $this->table->delete(['itemName' => $itemName])->run();
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->table->eraseData();
    }
}
