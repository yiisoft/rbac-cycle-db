<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\AssignmentsStorage;
use Yiisoft\Rbac\Cycle\DbSchemaManager;
use Yiisoft\Rbac\Tests\Common\AssignmentsStorageTestTrait;

abstract class AssignmentsStorageTest extends TestCase
{
    use AssignmentsStorageTestTrait;

    protected function populateDatabase(): void
    {
        $fixtures = $this->getFixtures();

        $this->getDatabase()
            ->insert(DbSchemaManager::ITEMS_TABLE)
            ->columns(['name', 'type', 'createdAt', 'updatedAt'])
            ->values($fixtures['items'])
            ->run();
        $this->getDatabase()
            ->insert(DbSchemaManager::ASSIGNMENTS_TABLE)
            ->columns(['itemName', 'userId', 'createdAt'])
            ->values($fixtures['assignments'])
            ->run();
    }

    private function getStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage($this->getDatabase());
    }
}
