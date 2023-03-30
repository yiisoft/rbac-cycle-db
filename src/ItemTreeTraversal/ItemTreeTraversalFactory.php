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
 * A factory for creating item tree traversal strategy depending on used RDBMS.
 *
 * @internal
 */
class ItemTreeTraversalFactory
{
    /**
     * Creates item tree traversal strategy depending on used RDBMS.
     *
     * @param DatabaseInterface $database Cycle database instance.
     *
     * @param string $tableName A name of the table for storing RBAC items.
     * @psalm-param non-empty-string $tableName
     *
     * @param string $childrenTableName A name of the table for storing relations between RBAC items.
     * @psalm-param non-empty-string $childrenTableName
     *
     * @return ItemTreeTraversalInterface Item tree traversal strategy.
     *
     * @throws RuntimeException When a database was configured with unknown driver, either not supported by Cycle out of
     * the box or newly added by Cycle and not supported / tested yet in this package.
     */
    public static function getItemTreeTraversal(
        DatabaseInterface $database,
        string $tableName,
        string $childrenTableName,
    ): ItemTreeTraversalInterface {
        $arguments = [$database, $tableName, $childrenTableName];
        $driver = $database->getDriver();

        if ($driver instanceof SQLiteDriver) {
            return new SqliteCteItemTreeTraversal(...$arguments);
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
            return new PostgresCteItemTreeTraversal(...$arguments);
        }

        if ($driver instanceof SQLServerDriver) {
            return new SqlserverCteItemTreeTraversal(...$arguments);
        }

        // Ignored due to a complexity of testing and preventing splitting of database argument.
        // @codeCoverageIgnoreStart
        throw new RuntimeException('Unknown database driver.');
        // @codeCoverageIgnoreEnd
    }
}
