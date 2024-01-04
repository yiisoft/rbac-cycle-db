<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\AssignmentsStorage;
use Yiisoft\Rbac\Cycle\DbSchemaManager;
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
        $this->traitTearDown();
        parent::tearDown();
    }

    protected function populateItemsStorage(): void
    {
        $this->getDatabase()
            ->insert(self::$itemsTable)
            ->columns(['name', 'type', 'createdAt', 'updatedAt'])
            ->values($this->getFixtures()['items'])
            ->run();
    }

    protected function populateAssignmentsStorage(): void
    {
        $this->getDatabase()
            ->insert(self::$assignmentsTable)
            ->columns(['itemName', 'userId', 'createdAt'])
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
