<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests;

class RbacCycleInitTest extends TestCase
{
    protected function setUp(): void
    {
        // Skip
    }

    public function testExecute(): void
    {
        $this->createDbTables();

        $this->assertTrue($this
            ->getDbal()
            ->database()
            ->hasTable('auth_item'));
        $this->assertTrue($this
            ->getDbal()
            ->database()
            ->hasTable('auth_item_child'));
        $this->assertTrue($this
            ->getDbal()
            ->database()
            ->hasTable('auth_assignment'));
    }

    protected function populateDb(): void
    {
    }
}
