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
    public static function getItemTreeTraversal(
        DatabaseInterface $database,
        string $tableName,
        string $childrenTableName,
    ): ItemTreeTraversalInterface
    {
        $arguments = [$database, $tableName, $childrenTableName];
        $driver = $database->getDriver();

        if ($driver instanceof SQLiteDriver) {
            return new SqliteCteItemTreeTraversal(...$arguments);
        }

        if ($driver instanceof MySQLDriver) {
            $version = $database->query('SELECT VERSION() AS version')->fetch()['version'];

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
