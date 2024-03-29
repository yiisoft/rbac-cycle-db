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
 * An RBAC item tree traversal strategy based on specific functionality for MySQL 5, without support for CTE (Common
 * Table Expressions).
 *
 * @internal
 *
 * @psalm-import-type RawItem from ItemsStorage
 * @psalm-import-type Hierarchy from ItemTreeTraversalInterface
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
     *
     * @param string $namesSeparator Separator used for joining item names.
     * @psalm-param non-empty-string $namesSeparator
     */
    public function __construct(
        protected DatabaseInterface $database,
        protected string $tableName,
        protected string $childrenTableName,
        protected string $namesSeparator,
    ) {
    }

    public function getParentRows(string $name): array
    {
        $sql = "SELECT DISTINCT item.* FROM (
            SELECT @r AS child_name,
            (SELECT @r := parent FROM $this->childrenTableName WHERE child = child_name LIMIT 1) AS parent
            FROM (SELECT @r := :name) val, $this->childrenTableName
        ) s
        LEFT JOIN $this->tableName AS item ON item.name = s.child_name
        WHERE item.name != :name";

        /** @psalm-var RawItem[] */
        return $this
            ->database
            ->query($sql, [':name' => $name])
            ->fetchAll();
    }

    public function getHierarchy(string $name): array
    {
        $sql = "SELECT item.*, hierarchy_base.children FROM (
            SELECT
                child_name,
                MIN(TRIM(BOTH '$this->namesSeparator' FROM TRIM(BOTH child_name FROM raw_children))) as children
            FROM (
                SELECT @r AS child_name, @path := concat(@path, '$this->namesSeparator', @r) as raw_children,
                (SELECT @r := parent FROM $this->childrenTableName WHERE child = child_name LIMIT 1) AS parent
                FROM (SELECT @r := :name, @path := '') val, $this->childrenTableName
            ) raw_hierarchy_base
            GROUP BY child_name
        ) hierarchy_base
        LEFT JOIN $this->tableName AS item ON item.name = hierarchy_base.child_name
        WHERE item.name IS NOT NULL";

        /** @psalm-var Hierarchy */
        return $this
            ->database
            ->query($sql, [':name' => $name])
            ->fetchAll();
    }

    public function getChildrenRows(string|array $names): array
    {
        /** @psalm-var RawItem[] */
        return $this->getChildrenRowsStatement($names, baseOuterQuery: $this->getChildrenBaseOuterQuery())->fetchAll();
    }

    public function getChildPermissionRows(string|array $names): array
    {
        $baseOuterQuery = $this->getChildrenBaseOuterQuery()->where(['item.type' => Item::TYPE_PERMISSION]);

        /** @psalm-var RawItem[] */
        return $this->getChildrenRowsStatement($names, baseOuterQuery: $baseOuterQuery)->fetchAll();
    }

    public function getChildRoleRows(string|array $names): array
    {
        $baseOuterQuery = $this->getChildrenBaseOuterQuery()->where(['item.type' => Item::TYPE_ROLE]);

        /** @psalm-var RawItem[] */
        return $this->getChildrenRowsStatement($names, baseOuterQuery: $baseOuterQuery)->fetchAll();
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

    /**
     * @param string|string[] $names
     */
    private function getChildrenRowsStatement(string|array $names, SelectQuery $baseOuterQuery): StatementInterface
    {
        $names = (array) $names;
        $fromSql = "SELECT DISTINCT child
        FROM (SELECT * FROM $this->childrenTableName ORDER by parent) item_child_sorted,\n";
        $where = '';
        $excludedNamesStr = '';
        $parameters = [];
        $lastNameIndex = array_key_last($names);

        foreach ($names as $index => $name) {
            $fromSql .= "(SELECT @pv$index := :name$index) init$index";
            $excludedNamesStr .= "@pv$index";

            if ($index !== $lastNameIndex) {
                $fromSql .= ',';
                $excludedNamesStr .= ', ';
            }

            $fromSql .= "\n";

            if ($index !== 0) {
                $where .= ' OR ';
            }

            $where .= "(find_in_set(parent, @pv$index) AND length(@pv$index := concat(@pv$index, ',', child)))";

            $parameters[":name$index"] = $name;
        }

        $where = "($where) AND child NOT IN ($excludedNamesStr)";
        $fromSql .= "WHERE $where";
        $outerQuery = $baseOuterQuery
            ->from(new Fragment("($fromSql) s"))
            ->leftJoin($this->tableName, 'item')
            ->on('item.name', 's.child');
        /** @psalm-var non-empty-string $outerQuerySql */
        $outerQuerySql = (string) $outerQuery;

        return $this->database->query($outerQuerySql, $parameters);
    }

    private function getChildrenBaseOuterQuery(): SelectQuery
    {
        return  $this->database->select('item.*')->distinct();
    }
}
