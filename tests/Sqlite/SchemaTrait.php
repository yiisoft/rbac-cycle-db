<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Sqlite;

trait SchemaTrait
{
    protected function checkItemsChildrenTable(): void
    {
        parent::checkItemsChildrenTable();

        $this->checkItemsChildrenTableForeignKeys(
            expectedParentForeignKeyName: 'yii_rbac_cycle_db_auth_item_child_parent_fk',
            expectedChildForeignKeyName: 'yii_rbac_cycle_db_auth_item_child_child_fk',
        );
    }
}
