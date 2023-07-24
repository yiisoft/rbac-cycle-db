<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\Cycle\Manager;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\RuleFactoryInterface;
use Yiisoft\Rbac\Tests\Common\ManagerTestTrait;
use Yiisoft\Rbac\Tests\Support\SimpleRuleFactory;

abstract class ManagerWithDbItemsTest extends TestCase
{
    use ManagerTestTrait;

    protected function setUp(): void
    {
        $this->createSchemaManager(assignmentsTable: null)->ensureTables();
    }

    protected function tearDown(): void
    {
        $this->createSchemaManager()->ensureNoTables();
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

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage(self::ITEMS_TABLE, $this->getDatabase(), self::ITEMS_CHILDREN_TABLE);
    }
}
