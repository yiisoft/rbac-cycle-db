<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Yiisoft\Rbac\Cycle\ItemsStorage;

/**
 * @internal
 *
 * @psalm-import-type RawItem from ItemsStorage
 */
interface ItemTreeTraversalInterface
{
    /**
     * @psalm-return RawItem[]
     */
    public function getParentRows(string $name): array;

    /**
     * @psalm-return RawItem[]
     */
    public function getChildrenRows(string $name): array;
}
