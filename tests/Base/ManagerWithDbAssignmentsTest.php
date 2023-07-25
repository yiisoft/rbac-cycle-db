<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\AssignmentsStorage;
use Yiisoft\Rbac\Tests\Common\ManagerTestLogicTrait;

abstract class ManagerWithDbAssignmentsTest extends ManagerTest
{
    use ManagerTestLogicTrait {
        setUp as protected traitSetUp;
        tearDown as protected traitTearDown;
    }

    protected function setUp(): void
    {
        $this->createSchemaManager(itemsTable: null, itemsChildrenTable: null)->ensureTables();
        $this->traitSetUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->traitTearDown();
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage(self::ASSIGNMENTS_TABLE, $this->getDatabase());
    }
}
