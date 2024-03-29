<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\AssignmentsStorage;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Tests\Common\AssignmentsStorageTestTrait;

abstract class AssignmentsStorageTest extends TestCase
{
    use AssignmentsStorageTestTrait {
        setUp as protected traitSetUp;
        tearDown as protected traitTearDown;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->traitSetUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->traitTearDown();
    }

    protected function populateItemsStorage(): void
    {
        $this->getDatabase()
            ->insert(self::$itemsTable)
            ->columns(['name', 'type', 'created_at', 'updated_at'])
            ->values($this->getFixtures()['items'])
            ->run();
    }

    protected function populateAssignmentsStorage(): void
    {
        $this->getDatabase()
            ->insert(self::$assignmentsTable)
            ->columns(['item_name', 'user_id', 'created_at'])
            ->values($this->getFixtures()['assignments'])
            ->run();
    }

    protected function populateDatabase(): void
    {
        // Skip
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDatabase());
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage($this->getDatabase());
    }
}
