<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use RuntimeException;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\Cycle\Manager;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\RuleFactoryInterface;
use Yiisoft\Rbac\Tests\Common\ManagerTestConfigurationTrait;
use Yiisoft\Rbac\Tests\Support\FakeAssignmentsStorage;
use Yiisoft\Rbac\Tests\Support\SimpleRuleFactory;

abstract class ManagerTransactionTest extends TestCase
{
    use ManagerTestConfigurationTrait;

    protected function setUp(): void
    {
        $this->createSchemaManager()->ensureTables();
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

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new class () extends FakeAssignmentsStorage {
            public function renameItem(string $oldName, string $newName): void
            {
                throw new RuntimeException('Failed to rename item.');
            }
        };
    }

    public function testUpdateRoleTransaction(): void
    {
        $manager = $this->createFilledManager();
        $role = $this->itemsStorage->getRole('reader')->withName('new reader');

        try {
            $manager->updateRole('reader', $role);
        } catch (RuntimeException) {
            $this->assertNotNull($this->itemsStorage->getRole('reader'));
            $this->assertNull($this->itemsStorage->getRole('new reader'));
        }
    }

    public function testUpdatePermissionTransaction(): void
    {
        $manager = $this->createFilledManager();
        $permission = $this->itemsStorage->getPermission('updatePost')->withName('newUpdatePost');

        try {
            $manager->updatePermission('updatePost', $permission);
        } catch (RuntimeException) {
            $this->assertNotNull($this->itemsStorage->getPermission('updatePost'));
            $this->assertNull($this->itemsStorage->getPermission('newUpdatePost'));
        }
    }
}
