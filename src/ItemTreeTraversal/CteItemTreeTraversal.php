<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Cycle\Database\ColumnInterface;

/**
 * @internal
 */
abstract class CteItemTreeTraversal extends BaseItemTreeTraversal implements ItemTreeTraversalInterface
{
    public function getParentRows(string $name): array
    {
        $itemNameColumn = $this->database->table($this->tableName)->getColumns()['name'];
        $itemNameColumnType = $this->getCastedColumnType($itemNameColumn);
        $sql = "{$this->getWithExpression()} parent_of(child_name) AS (
            SELECT CAST(:name_for_recursion AS $itemNameColumnType({$itemNameColumn->getSize()}))
            UNION ALL
            SELECT parent FROM $this->childrenTableName AS item_child_recursive, parent_of
            WHERE item_child_recursive.child = parent_of.child_name
        )
        SELECT item.* FROM parent_of
        LEFT JOIN $this->tableName AS item ON item.name = parent_of.child_name
        WHERE item.name != :excluded_name";

        return $this
            ->database
            ->query($sql, [':name_for_recursion' => $name, ':excluded_name' => $name])
            ->fetchAll();
    }

    protected function getWithExpression(): string
    {
        return 'WITH RECURSIVE';
    }

    protected function getCastedColumnType(ColumnInterface $column): string
    {
        return $column->getInternalType();
    }
}
