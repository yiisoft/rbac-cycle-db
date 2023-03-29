<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\MySQL\MySQLDriver;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Driver\SQLServer\SQLServerDriver;
use RuntimeException;

/**
 * @internal
 */
class ItemTreeTraversalFactory
{
    /**
     * @psalm-param non-empty-string $tableName
     * @psalm-param non-empty-string $childrenTableName
     */
    public static function getItemTreeTraversal(
        DatabaseInterface $database,
        string $tableName,
        string $childrenTableName,
    ): ItemTreeTraversalInterface {
        $arguments = [$database, $tableName, $childrenTableName];
        $driver = $database->getDriver();

        if ($driver instanceof SQLiteDriver) {
            /** @psalm-var array{version: string} $row */
            $row = $database->query('SELECT sqlite_version() AS version')->fetch();
            $version = $row['version'];

            return version_compare($version, '3.8.3', '>=')
                ? new SqliteCteItemTreeTraversal(...$arguments)
                : new SqliteItemTreeTraversal(...$arguments);
        }

        if ($driver instanceof MySQLDriver) {
            /** @psalm-var array{version: string} $row */
            $row = $database->query('SELECT VERSION() AS version')->fetch();
            $version = $row['version'];

            return str_starts_with($version, '5')
                ? new MysqlItemTreeTraversal(...$arguments)
                : new MysqlCteItemTreeTraversal(...$arguments);
        }

        if ($driver instanceof PostgresDriver) {
            return new PostgresCteItemTreeTraversal($database, $tableName, $childrenTableName);
        }

        if ($database->getDriver() instanceof SQLServerDriver) {
            return new SqlserverCteItemTreeTraversal(...$arguments);
        }

        throw new RuntimeException('Unknown database driver.');
    }
}
