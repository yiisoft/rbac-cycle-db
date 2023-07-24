<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\AssignmentsStorage;
use Yiisoft\Rbac\Cycle\Manager;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\RuleFactoryInterface;
use Yiisoft\Rbac\Tests\Common\ManagerTestTrait;
use Yiisoft\Rbac\Tests\Support\SimpleRuleFactory;

abstract class ManagerWithDbAssignmentsTest extends TestCase
{
    use ManagerTestTrait {
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
        $this->createSchemaManager()->ensureNoTables();
        $this->traitTearDown();
    }

    protected function populateDatabase(): void
    {
        // Skip
    }

    protected function createManager(
        ?ItemsStorageInterface $itemsStorage = null,
        ?AssignmentsStorageInterface $assignmentsStorage = null,
        ?RuleFactoryInterface $ruleFactory = null,
        bool $enableDirectPermissions = false
    ): Manager {
        return new Manager(
            $itemsStorage ?? $this->createItemsStorage(),
            $assignmentsStorage ?? $this->createAssignmentsStorage(),
            $ruleFactory ?? new SimpleRuleFactory(),
            $this->getDatabase(),
            $enableDirectPermissions,
        );
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage(self::ASSIGNMENTS_TABLE, $this->getDatabase());
    }
}
