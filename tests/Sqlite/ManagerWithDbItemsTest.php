<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Sqlite;

use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\Cycle\Manager;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\RuleFactoryInterface;
use Yiisoft\Rbac\Tests\Support\SimpleRuleFactory;

class ManagerWithDbItemsTest extends \Yiisoft\Rbac\Cycle\Tests\Base\ManagerWithDbItemsTest
{
    use DatabaseTrait;

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
        return new ItemsStorage(self::ITEMS_TABLE, $this->getDatabase());
    }
}
