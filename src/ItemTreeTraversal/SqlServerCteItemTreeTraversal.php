<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

/**
 * An RBAC item tree traversal strategy based on CTE (common table expression) for SQL Server.
 *
 * @internal
 */
final class SqlServerCteItemTreeTraversal extends CteItemTreeTraversal
{
    protected function getWithExpression(): string
    {
        return 'WITH';
    }

    protected function getEmptyChildrenExpression(): string
    {
        return "CAST('' AS NVARCHAR(MAX))";
    }
}
