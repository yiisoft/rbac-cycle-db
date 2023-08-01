<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

/**
 * A RBAC item tree traversal strategy based on CTE (common table expression) for SQL Server.
 *
 * @internal
 */
final class SqlServerCteItemTreeTraversal extends CteItemTreeTraversal
{
    protected function getWithExpression(): string
    {
        return 'WITH';
    }
}
