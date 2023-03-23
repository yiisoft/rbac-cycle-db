<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Cycle\Database\DatabaseInterface;

/**
 * @internal
 */
class BaseItemTreeTraversal
{
    /**
     * @psalm-param non-empty-string $tableName
     * @psalm-param non-empty-string $childrenTableName
     */
    public function __construct(
        protected DatabaseInterface $database,
        protected string $tableName,
        protected string $childrenTableName,
    ) {
    }
}
