<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\ItemTreeTraversal;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Injection\Fragment;
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
abstract class CteItemTreeTraversal implements ItemTreeTraversalInterface
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
        /** @psalm-var RawItem[] */
        return $this->getRows($name);
    }

    public function getChildrenRows(string $name): array
    {
        /** @psalm-var RawItem[] */
        return $this->getRows($name, areParents: false);
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

    private function getRows(string $name, bool $areParents = true): array
    {
        if ($areParents) {
            $cteSelectRelationName = 'parent';
            $cteConditionRelationName = 'child';
            $cteName = 'parent_of';
            $cteParameterName = 'child_name';
        } else {
            $cteSelectRelationName = 'child';
            $cteConditionRelationName = 'parent';
            $cteName = 'child_of';
            $cteParameterName = 'parent_name';
        }

        $compiler = $this->database->getDriver()->getQueryCompiler();
        $cteSelectItemQuery = $this
            ->database
            ->select('name')
            ->from($this->tableName)
            ->where(['name' => $name]);
        $cteSelectRelationQuery = $this
            ->database
            ->select($cteSelectRelationName)
            ->from([
                new Fragment($compiler->quoteIdentifier($this->childrenTableName) . ' item_child_recursive'),
                new Fragment($cteName),
            ])
            ->where(
                new Fragment('item_child_recursive.' . $compiler->quoteIdentifier($cteConditionRelationName)),
                new Fragment("$cteName.$cteParameterName"),
            );
        $outerQuery = $this
            ->database
            ->select('item.*')
            ->from(new Fragment($cteName))
            ->join('LEFT', $this->tableName . ' item')
            ->on('item.name', new Fragment("$cteName.$cteParameterName"))
            ->where('item.name', '!=', $name);
        $sql = "{$this->getWithExpression()} $cteName($cteParameterName) AS (
            $cteSelectItemQuery
            UNION ALL
            $cteSelectRelationQuery
        )
        $outerQuery";

        /** @psalm-var RawItem[] */
        return $this
            ->database
            ->query($sql)
            ->fetchAll();
    }
}
