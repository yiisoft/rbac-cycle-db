<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\StatementInterface;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\Item;

/**
 * A RBAC item tree traversal strategy based on specific functionality for MySQL 5, without support for CTE (Common
 * Table Expressions).
 *
 * @internal
 *
 * @psalm-import-type RawItem from ItemsStorage
 */
final class MysqlItemTreeTraversal implements ItemTreeTraversalInterface
{
    /**
     * @param DatabaseInterface $database Cycle database instance.
     *
     * @param string $tableName A name of the table for storing RBAC items.
     * @psalm-param non-empty-string $tableName
     *
     * @param string $childrenTableName A name of the table for storing relations between RBAC items.
     * @psalm-param non-empty-string $childrenTableName
     */
    public function __construct(
        protected DatabaseInterface $database,
        protected string $tableName,
        protected string $childrenTableName,
    ) {
    }

    public function getParentRows(string $name): array
    {
        $sql = "SELECT DISTINCT item.* FROM (
            SELECT @r AS child_name,
            (SELECT @r := parent FROM $this->childrenTableName WHERE child = child_name LIMIT 1) AS parent,
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
        $baseOuterQuery = $this->database->select([new Fragment('item.*')])->distinct();

        /** @psalm-var RawItem[] */
        return $this->getChildrenRowsStatement($name, baseOuterQuery: $baseOuterQuery)->fetchAll();
    }

    public function getChildPermissionRows(string $name): array
    {
        $baseOuterQuery = $this
            ->database
            ->select([new Fragment('item.*')])
            ->distinct()
            ->where(['item.type' => Item::TYPE_PERMISSION]);

        /** @psalm-var RawItem[] */
        return $this->getChildrenRowsStatement($name, baseOuterQuery: $baseOuterQuery)->fetchAll();
    }

    public function getChildRoleRows(string $name): array
    {
        $baseOuterQuery = $this
            ->database
            ->select([new Fragment('item.*')])
            ->distinct()
            ->where(['item.type' => Item::TYPE_ROLE]);

        /** @psalm-var RawItem[] */
        return $this->getChildrenRowsStatement($name, baseOuterQuery: $baseOuterQuery)->fetchAll();
    }

    public function hasChild(string $parentName, string $childName): bool
    {
        $baseOuterQuery = $this
            ->database
            ->select([new Fragment('1 AS item_child_exists')])
            ->andWhere(['item.name' => $childName]);
        /** @psalm-var array<0, 1>|false $result */
        $result = $this->getChildrenRowsStatement($parentName, baseOuterQuery: $baseOuterQuery)->fetch();

        return $result !== false;
    }

    private function getChildrenRowsStatement(string $name, SelectQuery $baseOuterQuery): StatementInterface
    {
        $fromSql = "SELECT DISTINCT child
        FROM (SELECT * FROM $this->childrenTableName ORDER by parent) item_child_sorted,
        (SELECT @pv := :name) init
        WHERE find_in_set(parent, @pv) AND length(@pv := concat(@pv, ',', child))";
        $outerQuery = $baseOuterQuery
            ->from(new Fragment("($fromSql) s"))
            ->join('LEFT', $this->tableName . ' AS item')
            ->on(new Fragment('item.name'), new Fragment('s.child'));
        /** @psalm-var non-empty-string $outerQuerySql */
        $outerQuerySql = (string) $outerQuery;

        var_dump((string) $this->database->query($outerQuerySql, [':name' => $name]));

        return $this->database->query($outerQuerySql, [':name' => $name]);
    }
}
