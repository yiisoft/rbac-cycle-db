<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\AssignmentsStorage;

abstract class ManagerWithDbAssignmentsTest extends ManagerTest
{
    protected function setUp(): void
    {
        $this->createSchemaManager(itemsTable: null, itemsChildrenTable: null)->ensureTables();
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage($this->getDatabase());
    }
}
