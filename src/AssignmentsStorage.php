<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Table;
use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\AssignmentsStorageInterface;

/**
 * @psalm-type RawAssignment = array{
 *     itemName: string,
 *     userId: string,
 *     createdAt: int|string,
 * }
 */
final class AssignmentsStorage implements AssignmentsStorageInterface
{
    private DatabaseInterface $database;

    public function __construct(
        /**
         * @psalm-var non-empty-string
         */
        private string $tableName,
        DatabaseProviderInterface $dbal
    ) {
        $this->database = $dbal->database();
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        /** @psalm-var RawAssignment[] $rows */
        $rows = $this->database
            ->select()
            ->from($this->tableName)
            ->fetchAll();

        $assignments = [];
        foreach ($rows as $row) {
            $assignments[$row['userId']][$row['itemName']] = new Assignment(
                $row['userId'],
                $row['itemName'],
                (int) $row['createdAt']
            );
        }

        return $assignments;
    }

    /**
     * @inheritDoc
     */
    public function getByUserId(string $userId): array
    {
        /** @psalm-var RawAssignment[] $rows */
        $rows = $this->database
            ->select()
            ->from($this->tableName)
            ->where(['userId' => $userId])
            ->fetchAll();

        return array_combine(
            array_column($rows, 'itemName'),
            array_map(
                static fn(array $row) => new Assignment($userId, $row['itemName'], (int) $row['createdAt']),
                $rows
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function get(string $itemName, string $userId): ?Assignment
    {
        /** @psalm-var RawAssignment|null $row */
        $row = $this->database
            ->select()
            ->from($this->tableName)
            ->where(['itemName' => $itemName, 'userId' => $userId])
            ->run()
            ->fetch();

        return empty($row) ? null : new Assignment($userId, $itemName, (int) $row['createdAt']);
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
        /** @var mixed $result */
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
        $this->database
            ->update($this->tableName, ['itemName' => $newName], ['itemName' => $oldName])
            ->run();
    }

    /**
     * @inheritDoc
     */
    public function remove(string $itemName, string $userId): void
    {
        $this->database
            ->delete($this->tableName, ['itemName' => $itemName, 'userId' => $userId])
            ->run();
    }

    /**
     * @inheritDoc
     */
    public function removeByUserId(string $userId): void
    {
        $this->database
            ->delete($this->tableName, ['userId' => $userId])
            ->run();
    }

    /**
     * @inheritDoc
     */
    public function removeByItemName(string $itemName): void
    {
        $this->database
            ->delete($this->tableName, ['itemName' => $itemName])
            ->run();
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
