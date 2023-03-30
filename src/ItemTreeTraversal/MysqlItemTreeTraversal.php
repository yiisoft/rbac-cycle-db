<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Yiisoft\Rbac\Cycle\ItemsStorage;

/**
 * A RBAC item tree traversal strategy based on specific functionality for MySQL 5, without support for CTE (Common
 * Table Expressions).
 *
 * @internal
 *
 * @psalm-import-type RawItem from ItemsStorage
 */
final class MysqlItemTreeTraversal extends BaseItemTreeTraversal implements ItemTreeTraversalInterface
{
    public function getParentRows(string $name): array
    {
        $sql = "SELECT DISTINCT item.* FROM (
            SELECT @r AS child_name,
            (SELECT @r := parent FROM $this->childrenTableName WHERE child = child_name) AS parent,
            @l := @l + 1 AS level
            FROM (SELECT @r := :name, @l := 0) val, $this->childrenTableName
        ) s
        LEFT JOIN $this->tableName AS item ON item.name = s.child_name
        WHERE item.name != :name";

        /** @psalm-var RawItem[] */
        return $this
            ->database
            ->query($sql, [':name' => $name])
            ->fetchAll();
    }

    public function getChildrenRows(string $name): array
    {
        $sql = "SELECT DISTINCT item.* FROM (
            SELECT DISTINCT child
            FROM (SELECT * FROM auth_item_child ORDER by parent) item_child_sorted,
            (SELECT @pv := :name) init
            WHERE find_in_set(parent, @pv) AND length(@pv := concat(@pv, ',', child))
        ) s
        LEFT JOIN $this->tableName AS item ON item.name = s.child";

        /** @psalm-var RawItem[] */
        return $this
            ->database
            ->query($sql, [':name' => $name])
            ->fetchAll();
    }
}
