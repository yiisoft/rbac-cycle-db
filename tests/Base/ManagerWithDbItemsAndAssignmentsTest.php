<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\AssignmentsStorage;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Tests\Common\ManagerTestLogicTrait;

abstract class ManagerWithDbItemsAndAssignmentsTest extends ManagerTest
{
    use ManagerTestLogicTrait {
        setUp as protected traitSetUp;
        tearDown as protected traitTearDown;
    }

    protected function setUp(): void
    {
        $this->createSchemaManager()->ensureTables();
        $this->traitSetUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->traitTearDown();
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage(self::ITEMS_TABLE, $this->getDatabase(), self::ITEMS_CHILDREN_TABLE);
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage(self::ASSIGNMENTS_TABLE, $this->getDatabase());
    }
}
