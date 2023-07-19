<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Sqlite;

trait SchemaTrait
{
    protected function checkAssignmentsTable(): void
    {
        parent::checkAssignmentsTable();

        $this->checkAssignmentsTableForeignKeys('auth_assignment_itemName_fk');
    }

    protected function checkItemsChildrenTable(): void
    {
        parent::checkItemsChildrenTable();

        $this->checkItemsChildrenTableForeignKeys(
            expectedParentForeignKeyName: 'auth_item_child_parent_fk',
            expectedChildForeignKeyName: 'auth_item_child_child_fk',
        );
    }
}
