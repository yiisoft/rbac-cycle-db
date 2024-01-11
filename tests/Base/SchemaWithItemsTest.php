<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

abstract class SchemaWithItemsTest extends TestCase
{
    use SchemaTrait;

    protected static array $migrationsSubfolders = ['items'];

    protected function checkTables(): void
    {
        $this->checkItemsTable();
        $this->checkItemsChildrenTable();
        $this->assertFalse($this->getDatabase()->hasTable(self::$assignmentsTable));
    }
}
