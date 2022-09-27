<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Table;
use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\AssignmentsStorageInterface;

final class AssignmentsStorage implements AssignmentsStorageInterface
{
    private DatabaseInterface $database;

    /**
     * @param non-empty-string $tableName
     */
    public function __construct(/**
     * @psalm-var non-empty-string
     */
    private string $tableName, DatabaseProviderInterface $dbal)
    {
        $this->database = $dbal->database();
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        $assignments = [];
        foreach ($this->database->select()->from($this->tableName)->fetchAll() as $item) {
            $assignments[$item['userId']][$item['itemName']] = new Assignment(
                $item['userId'],
                $item['itemName'],
                (int)$item['createdAt']
            );
        }

        return $assignments;
    }

    /**
     * @inheritDoc
     */
    public function getByUserId(string $userId): array
    {
        $assignments = $this->database->select()->from($this->tableName)->where(['userId' => $userId])->fetchAll();

        return array_combine(
            array_column($assignments, 'itemName'),
            array_map(
                static fn (array $item) => new Assignment($userId, $item['itemName'], (int)$item['createdAt']),
                $assignments
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function get(string $itemName, string $userId): ?Assignment
    {
        $assignment = $this->database
            ->select()
            ->from($this->tableName)
            ->where(['itemName' => $itemName, 'userId' => $userId])
            ->run()
            ->fetch();

        return empty($assignment) ? null : new Assignment($userId, $itemName, (int)$assignment['createdAt']);
    }

    /**
     * @inheritDoc
     */
    public function add(string $itemName, string $userId): void
    {
        $this->database
            ->insert($this->tableName)
            ->values(
                [
                    'itemName' => $itemName,
                    'userId' => $userId,
                    'createdAt' => time(),
                ],
            )
            ->run();
    }

    /**
     * @inheritDoc
     */
    public function hasItem(string $name): bool
    {
        $result = $this
            ->database
            ->select([new Fragment('1')])
            ->from($this->tableName)
            ->where(['itemName' => $name])
            ->limit(1)
            ->run()
            ->fetch();

        return $result !== false;
    }

    /**
     * @inheritDoc
     */
    public function renameItem(string $oldName, string $newName): void
    {
        if ($oldName === $newName) {
            return;
        }
        $this->database->update($this->tableName, ['itemName' => $newName], ['itemName' => $oldName])->run();
    }

    /**
     * @inheritDoc
     */
    public function remove(string $itemName, string $userId): void
    {
        $this->database->delete($this->tableName, ['itemName' => $itemName, 'userId' => $userId])->run();
    }

    /**
     * @inheritDoc
     */
    public function removeByUserId(string $userId): void
    {
        $this->database->delete($this->tableName, ['userId' => $userId])->run();
    }

    /**
     * @inheritDoc
     */
    public function removeByItemName(string $itemName): void
    {
        $this->database->delete($this->tableName, ['itemName' => $itemName])->run();
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        /** @var Table $table */
        $table = $this->database->table($this->tableName);
        $table->eraseData();
    }
}
