<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Cycle\Database\Injection\Expression;
use Yiisoft\Rbac\Cycle\ItemsStorage;

/**
 * @internal
 *
 * @psalm-import-type RawItem from ItemsStorage
 */
final class SqliteItemTreeTraversal extends BaseItemTreeTraversal implements ItemTreeTraversalInterface
{
    public function getParentRows(string $name): array
    {
        /** @psalm-var RawItem[] $levelParentRows */
        $parentRows = [];
        $childrenNames = [$name];

        while ($childrenNames !== []) {
            /** @psalm-var RawItem[] $levelParentRows */
            $levelParentRows = $this
                ->database
                ->select('items.*')
                ->from($this->tableName . ' AS items')
                ->leftJoin($this->childrenTableName, 'items_children')
                ->on('items_children.parent', 'items.name')
                ->where('items_children.child', 'IN', $childrenNames)
                ->andWhere(['items_children.parent' => new Expression('parent')])
                ->fetchAll();
            $parentRows = array_merge($parentRows, $levelParentRows);
            $childrenNames = array_map(static fn (array $row): string => $row['name'], $levelParentRows);
        }


        return $parentRows;
    }

    public function getChildrenRows(string $name): array
    {
        /** @psalm-var RawItem[] $levelChildrenRows */
        $childrenRows = [];
        $parentNames = [$name];

        while ($parentNames !== []) {
            /** @psalm-var RawItem[] $levelChildrenRows */
            $levelChildrenRows = $this
                ->database
                ->select('items.*')
                ->from($this->tableName . ' AS items')
                ->leftJoin($this->childrenTableName, 'items_children')
                ->on('items_children.child', 'items.name')
                ->where('items_children.parent', 'IN', $parentNames)
                ->andWhere(['items_children.child' => new Expression('child')])
                ->fetchAll();
            $childrenRows = array_merge($childrenRows, $levelChildrenRows);
            $parentNames = array_map(static fn (array $row): string => $row['name'], $levelChildrenRows);
        }

        return $childrenRows;
    }
}
