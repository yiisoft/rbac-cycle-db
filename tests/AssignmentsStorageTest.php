<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests;

use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\Cycle\AssignmentsStorage;
use Yiisoft\Rbac\Item;

class AssignmentsStorageTest extends TestCase
{
    public function testHasItem(): void
    {
        $storage = $this->getStorage();

        $this->assertTrue($storage->hasItem('Admin'));
    }

    public function testRenameItem(): void
    {
        $storage = $this->getStorage();
        $storage->renameItem('Admin', 'Tech Admin');

        $this->assertFalse($storage->hasItem('Admin'));
        $this->assertTrue($storage->hasItem('Tech Admin'));
    }

    public function testGetAll(): void
    {
        $storage = $this->getStorage();
        $all = $storage->getAll();

        $this->assertCount(2, $all);
        foreach ($all as $userId => $assignments) {
            foreach ($assignments as $name => $assignment) {
                $this->assertSame($userId, $assignment->getUserId());
                $this->assertSame($name, $assignment->getItemName());
            }
        }
    }

    public function testRemoveByItemName(): void
    {
        $storage = $this->getStorage();
        $storage->removeByItemName('Manager');

        $this->assertFalse($storage->hasItem('Manager'));
    }

    public function testGetByUserId(): void
    {
        $storage = $this->getStorage();
        $assignments = $storage->getByUserId('admin');

        $this->assertCount(3, $assignments);
        $this->assertSame('Admin', $assignments['Admin']->getItemName());
        foreach ($assignments as $name => $assignment) {
            $this->assertSame($name, $assignment->getItemName());
        }
    }

    public function testRemoveByUserId(): void
    {
        $storage = $this->getStorage();
        $storage->removeByUserId('manager');

        $this->assertFalse($storage->hasItem('Manager'));
        $this->assertEmpty($storage->getByUserId('manager'));
        $this->assertNotEmpty($storage->getByUserId('admin'));
    }

    public function testRemove(): void
    {
        $storage = $this->getStorage();
        $storage->remove('Admin', 'admin');

        $this->assertFalse($storage->hasItem('Admin'));
        $this->assertEmpty($storage->get('Admin', 'admin'));
        $this->assertNotEmpty($storage->getByUserId('admin'));
    }

    public function testClear(): void
    {
        $storage = $this->getStorage();
        $storage->clear();

        $this->assertEmpty($storage->getAll());
    }

    public function testGet(): void
    {
        $storage = $this->getStorage();
        $assignment = $storage->get('Manager', 'manager');

        $this->assertSame('Manager', $assignment->getItemName());
        $this->assertSame('manager', $assignment->getUserId());
        $this->assertIsInt($assignment->getCreatedAt());
    }

    public function testAdd(): void
    {
        $storage = $this->getStorage();
        $storage->add('Tech Admin', 'admin');

        $this->assertInstanceOf(Assignment::class, $storage->get('Tech Admin', 'admin'));
    }

    protected function populateDb(): void
    {
        $time = time();
        $items = [
            [
                'name' => 'Admin',
                'type' => Item::TYPE_ROLE,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
            [
                'name' => 'Tech Admin',
                'type' => Item::TYPE_ROLE,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
            [
                'name' => 'Manager',
                'type' => Item::TYPE_ROLE,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
            [
                'name' => 'Delete user',
                'type' => Item::TYPE_PERMISSION,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
        ];
        $assignments = [
            [
                'itemName' => 'Admin 0',
                'userId' => 'admin',
                'createdAt' => $time,
            ],
            [
                'itemName' => 'Admin',
                'userId' => 'admin',
                'createdAt' => $time,
            ],
            [
                'itemName' => 'Admin 1',
                'userId' => 'admin',
                'createdAt' => $time,
            ],
            [
                'itemName' => 'Manager 0',
                'userId' => 'manager',
                'createdAt' => $time,
            ],
            [
                'itemName' => 'Manager',
                'userId' => 'manager',
                'createdAt' => $time,
            ],
            [
                'itemName' => 'Manager 2',
                'userId' => 'manager',
                'createdAt' => $time,
            ],
        ];

        foreach ($items as $item) {
            $this->getDbal()
                ->database()
                ->insert(self::ITEMS_TABLE)
                ->values($item)
                ->run();
        }

        foreach ($assignments as $assignment) {
            $this->getDbal()
                ->database()
                ->insert(self::ASSIGNMENTS_TABLE)
                ->values($assignment)
                ->run();
        }
    }

    private function getStorage(): AssignmentsStorage
    {
        return new AssignmentsStorage(self::ASSIGNMENTS_TABLE, $this->getDbal());
    }
}
