<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Psr\Log\NullLogger;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\AssignmentsStorage;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\ItemsStorageInterface;

abstract class ManagerTransactionSuccessTest extends ManagerTest
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->getDatabaseManager()->setLogger(new NullLogger());
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDatabase());
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage($this->getDatabase());
    }

    public function testUpdateRoleTransactionSuccess(): void
    {
        $manager = $this->createFilledManager();
        $role = $manager->getRole('reader')->withName('new reader');

        $logger = new Logger();
        $this->getDatabaseManager()->setLogger($logger);

        $manager->updateRole('reader', $role);
        $this->assertContains('Commit transaction', $logger->getMessages());
    }

    public function testUpdatePermissionTransactionSuccess(): void
    {
        $manager = $this->createFilledManager();
        $permission = $manager->getPermission('updatePost')->withName('newUpdatePost');

        $logger = new Logger();
        $this->getDatabaseManager()->setLogger($logger);

        $manager->updatePermission('updatePost', $permission);
        $this->assertContains('Commit transaction', $logger->getMessages());
    }
}
