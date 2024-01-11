<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Injection\Fragment;
use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\AssignmentsStorageInterface;

/**
 * **Warning:** Do not use directly! Use with {@see Manager} instead.
 *
 * Storage for RBAC assignments in the form of database table. Operations are performed using Cycle ORM.
 *
 * @psalm-type RawAssignment = array{
 *     item_name: string,
 *     user_id: string,
 *     created_at: int|string,
 * }
 */
final class AssignmentsStorage implements AssignmentsStorageInterface
{
    /**
     * @param DatabaseInterface $database Cycle database instance.
     *
     * @param string $tableName A name of the table for storing RBAC assignments.
     * @psalm-param non-empty-string $tableName
     */
    public function __construct(
        private DatabaseInterface $database,
        private string $tableName = 'yii_rbac_assignment',
    ) {
    }

    public function getAll(): array
    {
        /** @psalm-var RawAssignment[] $rows */
        $rows = $this
            ->database
            ->select()
            ->from($this->tableName)
            ->fetchAll();

        $assignments = [];
        foreach ($rows as $row) {
            $assignments[$row['user_id']][$row['item_name']] = new Assignment(
                $row['user_id'],
                $row['item_name'],
                (int) $row['created_at'],
            );
        }

        return $assignments;
    }

    public function getByUserId(string $userId): array
    {
        /** @psalm-var RawAssignment[] $rawAssignments */
        $rawAssignments = $this->database
            ->select(['item_name', 'created_at'])
            ->from($this->tableName)
            ->where(['user_id' => $userId])
            ->fetchAll();
        $assignments = [];
        foreach ($rawAssignments as $rawAssignment) {
            $assignments[$rawAssignment['item_name']] = new Assignment(
                $userId,
                $rawAssignment['item_name'],
                (int) $rawAssignment['created_at'],
            );
        }

        return $assignments;
    }

    public function getByItemNames(array $itemNames): array
    {
        if (empty($itemNames)) {
            return [];
        }

        /** @psalm-var RawAssignment[] $rawAssignments */
        $rawAssignments = $this->database
            ->select()
            ->from($this->tableName)
            ->where('item_name', 'IN', $itemNames)
            ->fetchAll();
        $assignments = [];
        foreach ($rawAssignments as $rawAssignment) {
            $assignments[] = new Assignment(
                $rawAssignment['user_id'],
                $rawAssignment['item_name'],
                (int) $rawAssignment['created_at'],
            );
        }

        return $assignments;
    }

    public function get(string $itemName, string $userId): ?Assignment
    {
        /**
         * @psalm-var RawAssignment|false $row
         * @infection-ignore-all
         *  - ArrayItemRemoval, select.
         */
        $row = $this
            ->database
            ->select(['created_at'])
            ->from($this->tableName)
            ->where(['item_name' => $itemName, 'user_id' => $userId])
            ->run()
            ->fetch();

        return $row === false ? null : new Assignment($userId, $itemName, (int) $row['created_at']);
    }

    public function exists(string $itemName, string $userId): bool
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
            ->where(['item_name' => $itemName, 'user_id' => $userId])
            ->run()
            ->fetch();

        return $result !== false;
    }

    public function userHasItem(string $userId, array $itemNames): bool
    {
        if (empty($itemNames)) {
            return false;
        }

        /**
         * @psalm-var array<0, 1>|false $result
         * @infection-ignore-all
         * - ArrayItemRemoval, select.
         */
        $result = $this
            ->database
            ->select([new Fragment('1 AS assignment_exists')])
            ->from($this->tableName)
            ->where(['user_id' => $userId])
            ->andWhere('item_name', 'IN', $itemNames)
            ->run()
            ->fetch();

        return $result !== false;
    }

    public function filterUserItemNames(string $userId, array $itemNames): array
    {
        /** @var array{itemName: string} $rows */
        $rows = $this
            ->database
            ->select('item_name')
            ->from($this->tableName)
            ->where(['user_id' => $userId])
            ->andWhere('item_name', 'IN', $itemNames)
            ->fetchAll();

        return array_column($rows, 'item_name');
    }

    public function add(Assignment $assignment): void
    {
        $this
            ->database
            ->insert($this->tableName)
            ->values([
                'item_name' => $assignment->getItemName(),
                'user_id' => $assignment->getUserId(),
                'created_at' => $assignment->getCreatedAt(),
            ])
            ->run();
    }

    public function hasItem(string $name): bool
    {
        /**
         * @psalm-var array<0, 1>|false $result
         * @infection-ignore-all
         * - ArrayItemRemoval, select.
         */
        $result = $this
            ->database
            ->select([new Fragment('1 AS assignment_exists')])
            ->from($this->tableName)
            ->where(['item_name' => $name])
            ->run()
            ->fetch();

        return $result !== false;
    }

    public function renameItem(string $oldName, string $newName): void
    {
        $this
            ->database
            ->update($this->tableName, values: ['item_name' => $newName], where: ['item_name' => $oldName])
            ->run();
    }

    public function remove(string $itemName, string $userId): void
    {
        $this
            ->database
            ->delete($this->tableName, ['item_name' => $itemName, 'user_id' => $userId])
            ->run();
    }

    public function removeByUserId(string $userId): void
    {
        $this
            ->database
            ->delete($this->tableName, ['user_id' => $userId])
            ->run();
    }

    public function removeByItemName(string $itemName): void
    {
        $this
            ->database
            ->delete($this->tableName, ['item_name' => $itemName])
            ->run();
    }

    public function clear(): void
    {
        $this
            ->database
            ->delete($this->tableName)
            ->run();
    }
}
