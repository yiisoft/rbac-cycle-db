<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Cycle\Database\ColumnInterface;

/**
 * A RBAC item tree traversal strategy based on CTE (common table expression) for MySQL 8 and above (lower versions
 * don't support this functionality).
 *
 * @internal
 */
final class MysqlCteItemTreeTraversal extends CteItemTreeTraversal
{
    protected function getCastedColumnType(ColumnInterface $column): string
    {
        return 'char';
    }
}
