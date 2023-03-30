<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Cycle\Database\ColumnInterface;
use Yiisoft\Rbac\Cycle\ItemsStorage;

/**
 * A RBAC item tree traversal strategy based on CTE (common table expression). Uses `WITH` expression to form a
 * recursive query. The base queries are unified as much possible to work for all RDBMS supported by Cycle with minimal
 * differences.
 *
 * @internal
 *
 * @psalm-import-type RawItem from ItemsStorage
 */
abstract class CteItemTreeTraversal extends BaseItemTreeTraversal implements ItemTreeTraversalInterface
{
    public function getParentRows(string $name): array
    {
        $itemNameColumn = $this->database->table($this->tableName)->getColumns()['name'];
        $itemNameColumnType = $this->getCastedNameType($itemNameColumn);
        $sql = "{$this->getWithExpression()} parent_of(child_name) AS (
            SELECT CAST(:name_for_recursion AS $itemNameColumnType({$itemNameColumn->getSize()}))
            UNION ALL
            SELECT parent FROM $this->childrenTableName AS item_child_recursive, parent_of
            WHERE item_child_recursive.child = parent_of.child_name
        )
        SELECT item.* FROM parent_of
        LEFT JOIN $this->tableName AS item ON item.name = parent_of.child_name
        WHERE item.name != :excluded_name";

        /** @psalm-var RawItem[] */
        return $this
            ->database
            ->query($sql, [':name_for_recursion' => $name, ':excluded_name' => $name])
            ->fetchAll();
    }

    public function getChildrenRows(string $name): array
    {
        $itemNameColumn = $this->database->table($this->tableName)->getColumns()['name'];
        $itemNameColumnType = $this->getCastedNameType($itemNameColumn);
        $sql = "{$this->getWithExpression()} child_of(parent_name) AS (
            SELECT CAST(:name_for_recursion AS $itemNameColumnType({$itemNameColumn->getSize()}))
            UNION ALL
            SELECT child FROM $this->childrenTableName AS item_child_recursive, child_of
            WHERE item_child_recursive.parent = child_of.parent_name
        )
        SELECT item.* FROM child_of
        LEFT JOIN $this->tableName AS item ON item.name = child_of.parent_name
        WHERE item.name != :excluded_name";

        /** @psalm-var RawItem[] */
        return $this
            ->database
            ->query($sql, [':name_for_recursion' => $name, ':excluded_name' => $name])
            ->fetchAll();
    }

    /**
     * Gets `WITH` expression used in DB query.
     *
     * @infection-ignore-all
     * - ProtectedVisibility.
     *
     * @return string `WITH` expression.
     */
    protected function getWithExpression(): string
    {
        return 'WITH RECURSIVE';
    }

    /**
     * Gets type for casting "name" column's value. Defaults to a type initially defined in schema.
     *
     * @param ColumnInterface $column Cycle column instance.
     *
     * @return string Type for casting "name" column's value.
     *
     * @infection-ignore-all
     * - ProtectedVisibility.
     */
    protected function getCastedNameType(ColumnInterface $column): string
    {
        return $column->getInternalType();
    }
}
