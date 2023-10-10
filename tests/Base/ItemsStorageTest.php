<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\Injection\Fragment;
use Yiisoft\Rbac\Cycle\DbSchemaManager;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Tests\Common\ItemsStorageTestTrait;

abstract class ItemsStorageTest extends TestCase
{
    use ItemsStorageTestTrait {
        setUp as protected traitSetUp;
        tearDown as protected traitTearDown;
        testClear as protected traitTestClear;
        testRemove as protected traitTestRemove;
        testClearPermissions as protected traitTestClearPermissions;
        testClearRoles as protected traitTestClearRoles;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->traitSetUp();
    }

    protected function tearDown(): void
    {
        $this->traitTearDown();
        parent::tearDown();
    }

    public function testClear(): void
    {
        $this->traitTestClear();

        /** @psalm-var array<0, 1>|false $itemsChildrenExist */
        $itemsChildrenExist = $this
            ->getDatabase()
            ->select([new Fragment('1 AS item_exists')])
            ->from(DbSchemaManager::ITEMS_CHILDREN_TABLE)
            ->limit(1)
            ->run()
            ->fetch();
        $this->assertFalse($itemsChildrenExist);
    }

    public function testRemove(): void
    {
        $storage = $this->getItemsStorage();
        $initialItemChildrenCount = count($storage->getAllChildren('Parent 2'));

        $this->traitTestRemove();

        $itemsChildren = $this
            ->getDatabase()
            ->select()
            ->from(DbSchemaManager::ITEMS_CHILDREN_TABLE)
            ->count();
        $this->assertSame($this->initialItemsChildrenCount - $initialItemChildrenCount, $itemsChildren);
    }

    public function testClearPermissions(): void
    {
        $this->traitTestClearPermissions();

        $itemsChildrenCount = $this
            ->getDatabase()
            ->select()
            ->from(DbSchemaManager::ITEMS_CHILDREN_TABLE)
            ->count();
        $this->assertSame($this->initialBothRolesChildrenCount, $itemsChildrenCount);
    }

    public function testClearRoles(): void
    {
        $this->traitTestClearRoles();

        $itemsChildrenCount = $this
            ->getDatabase()
            ->select([new Fragment('1 AS item_exists')])
            ->from(DbSchemaManager::ITEMS_CHILDREN_TABLE)
            ->count();
        $this->assertSame($this->initialBothPermissionsChildrenCount, $itemsChildrenCount);
    }

    protected function populateItemsStorage(): void
    {
        $fixtures = $this->getFixtures();

        $this
            ->getDatabase()
            ->insert(DbSchemaManager::ITEMS_TABLE)
            ->columns(['name', 'type', 'createdAt', 'updatedAt'])
            ->values($fixtures['items'])
            ->run();
        $this
            ->getDatabase()
            ->insert(DbSchemaManager::ITEMS_CHILDREN_TABLE)
            ->columns(['parent', 'child'])
            ->values($fixtures['itemsChildren'])
            ->run();
    }

    protected function populateDatabase(): void
    {
        // Skip
    }

    protected function getItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDatabase());
    }
}
