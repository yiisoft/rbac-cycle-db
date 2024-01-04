<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

abstract class SchemaTest extends TestCase
{
    use SchemaTrait;

    protected function setUp(): void
    {
        // Skip
    }

    protected function populateDatabase(): void
    {
        // Skip
    }


    public function testCreateSchema(): void
    {
        $this->runMigrations();
        $this->checkTables();
    }

    public function testDropSchema(): void
    {
        $this->runMigrations();
        $this->rollbackMigrations();
        $this->checkNoTables();
    }
}
