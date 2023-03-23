<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

/**
 * @internal
 */
final class SqlserverCteItemTreeTraversal extends CteItemTreeTraversal
{
    public function getWithExpression(): string
    {
        return 'WITH';
    }
}
