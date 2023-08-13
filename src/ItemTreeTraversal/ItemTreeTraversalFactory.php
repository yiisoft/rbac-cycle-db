<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Cycle\Database\DatabaseInterface;
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
     * @throws RuntimeException When a database was configured with unknown driver, either because it is not supported
     * by Cycle out of the box or newly added by Cycle and not supported / tested yet in this package.
     * @return ItemTreeTraversalInterface Item tree traversal strategy.
     */
    public static function getItemTreeTraversal(
        DatabaseInterface $database,
        string $tableName,
        string $childrenTableName,
    ): ItemTreeTraversalInterface {
        $arguments = [$database, $tableName, $childrenTableName];
        $driverType = $database->getDriver()->getType();

        // default - ignored due to a complexity of testing and preventing splitting of database argument.
        // @codeCoverageIgnoreStart
        return match ($driverType) {
            'SQLite' => new SqliteCteItemTreeTraversal(...$arguments),
            'MySQL' => self::getMysqlItemTreeTraversal($database, $tableName, $childrenTableName),
            'Postgres' => new PostgresCteItemTreeTraversal(...$arguments),
            'SQLServer' => new SqlServerCteItemTreeTraversal(...$arguments),
            default => throw new RuntimeException("$driverType database driver is not supported."),
        };
        // @codeCoverageIgnoreEnd
    }

    /**
     * Creates item tree traversal strategy for MySQL depending on its version.
     *
     * @param DatabaseInterface $database Cucle database instance.
     *
     * @param string $tableName A name of the table for storing RBAC items.
     * @psalm-param non-empty-string $tableName
     *
     * @param string $childrenTableName A name of the table for storing relations between RBAC items.
     * @psalm-param non-empty-string $childrenTableName
     *
     * @return MysqlCteItemTreeTraversal|MysqlItemTreeTraversal Item tree traversal strategy.
     */
    private static function getMysqlItemTreeTraversal($database, $tableName, $childrenTableName): MysqlCteItemTreeTraversal|MysqlItemTreeTraversal
    {
        /** @psalm-var array{version: string} $row */
        $row = $database->query('SELECT VERSION() AS version')->fetch();
        $version = $row['version'];
        $arguments = [$database, $tableName, $childrenTableName];

        return str_starts_with($version, '5')
            ? new MysqlItemTreeTraversal(...$arguments)
            : new MysqlCteItemTreeTraversal(...$arguments);
    }
}
