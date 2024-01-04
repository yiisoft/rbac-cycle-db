<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

abstract class SchemaWithItems extends TestCase
{
    use SchemaTrait;

    protected static array $migrationsSubfolders = ['items'];

    public function testCreateItemTables(): void
    {
        $this->runMigrations();;

        $this->checkItemsTable();
        $this->checkItemsChildrenTable();

        $this->assertFalse($this->getDatabase()->hasTable(self::$assignmentsTable));
    }
}
