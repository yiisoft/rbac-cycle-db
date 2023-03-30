<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Cycle\Database\DatabaseInterface;

/**
 * Base class for all RBAC item tree traversal strategies containing required dependencies.
 *
 * @internal
 */
abstract class BaseItemTreeTraversal
{
    /**
     * @param DatabaseInterface $database Cycle database instance.
     *
     * @param string $tableName A name of the table for storing RBAC items.
     * @psalm-param non-empty-string $tableName
     *
     * @param string $childrenTableName A name of the table for storing relations between RBAC items.
     * @psalm-param non-empty-string $childrenTableName
     */
    public function __construct(
        protected DatabaseInterface $database,
        protected string $tableName,
        protected string $childrenTableName,
    ) {
    }
}
