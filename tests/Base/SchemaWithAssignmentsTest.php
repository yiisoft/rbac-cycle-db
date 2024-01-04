<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

abstract class SchemaWithAssignmentsTest extends TestCase
{
    use SchemaTrait;

    protected static array $migrationsSubfolders = ['assignments'];

    protected function checkTables(): void
    {
        $this->checkAssignmentsTable();
        $this->assertFalse($this->getDatabase()->hasTable(self::$itemsTable));
        $this->assertFalse($this->getDatabase()->hasTable(self::$itemsChildrenTable));
    }
}
