<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Cycle\Database\ColumnInterface;

/**
 * @internal
 */
final class MysqlCteItemTreeTraversal extends CteItemTreeTraversal
{
    protected function getCastedColumnType(ColumnInterface $column): string
    {
        return 'char';
    }
}
