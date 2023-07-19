<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Yiisoft\Rbac\Tests\Common\ManagerTestTrait;

abstract class ManagerWithDbItemsTest extends TestCase
{
    use ManagerTestTrait {
        setUp as protected traitSetUp;
    }

    protected function setUp(): void
    {
        $this->createSchemaManager(assignmentsTable: null)->ensureTables();
        $this->traitSetUp();
    }

    protected function tearDown(): void
    {
        $this->createSchemaManager()->ensureNoTables();
    }

    protected function populateDatabase(): void
    {
        // Skip
    }
}
