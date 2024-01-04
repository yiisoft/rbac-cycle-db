<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

abstract class SchemaWithAssignments extends TestCase
{
    use SchemaTrait;

    protected static array $migrationsSubfolders = ['assignments'];

    public function testCreateAssignmentsTable(): void
    {
        $this->runMigrations();

        $this->checkAssignmentsTable();

        $this->assertFalse(self::$itemsTable);
        $this->assertFalse(self::$itemsChildrenTable);
    }
}
