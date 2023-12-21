<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\AssignmentsStorage;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\ItemsStorageInterface;

abstract class ManagerTransactionSuccessTest extends ManagerTest
{
    protected function setUp(): void
    {
        $this->createSchemaManager()->ensureTables();
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDatabase());
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage($this->getDatabase());
    }

    public function testUpdateRoleTransactionError(): void
    {
        $manager = $this->createFilledManager();
        $role = $manager->getRole('reader')->withName('new reader');
        $manager->updateRole('reader', $role);

        $this->assertContains('Commit transaction', $this->getLogger()->getMessages());
    }

    public function testUpdatePermissionTransactionError(): void
    {
        $manager = $this->createFilledManager();
        $permission = $manager->getPermission('updatePost')->withName('newUpdatePost');
        $manager->updatePermission('updatePost', $permission);

        $this->assertContains('Commit transaction', $this->getLogger()->getMessages());
    }
}
