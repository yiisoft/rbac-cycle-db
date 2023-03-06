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

        $this->assertTrue($storage->hasItem('Accountant'));
    }

    public function testRenameItem(): void
    {
        $storage = $this->getStorage();
        $storage->renameItem('Accountant', 'Senior accountant');

        $this->assertFalse($storage->hasItem('Accountant'));
        $this->assertTrue($storage->hasItem('Senior accountant'));
    }

    public function testRenameItemToSameName(): void
    {
        $storage = $this->getStorage();
        $name = 'Accountant';
        $storage->renameItem($name, $name);

        $this->assertTrue($storage->hasItem($name));
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
        $this->assertCount(2, $storage->getByUserId('jack'));
        $this->assertCount(3, $storage->getByUserId('john'));
    }

    public function testGetByUserId(): void
    {
        $storage = $this->getStorage();
        $assignments = $storage->getByUserId('john');

        $this->assertCount(3, $assignments);

        foreach ($assignments as $name => $assignment) {
            $this->assertSame($name, $assignment->getItemName());
        }
    }

    public function testRemoveByUserId(): void
    {
        $storage = $this->getStorage();
        $storage->removeByUserId('jack');

        $this->assertEmpty($storage->getByUserId('jack'));
        $this->assertNotEmpty($storage->getByUserId('john'));
    }

    public function testRemove(): void
    {
        $storage = $this->getStorage();
        $storage->remove('Accountant', 'john');

        $this->assertEmpty($storage->get('Accountant', 'john'));
        $this->assertNotEmpty($storage->getByUserId('john'));
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
        $assignment = $storage->get('Manager', 'jack');

        $this->assertSame('Manager', $assignment->getItemName());
        $this->assertSame('jack', $assignment->getUserId());
        $this->assertIsInt($assignment->getCreatedAt());
    }

    public function testAdd(): void
    {
        $storage = $this->getStorage();
        $storage->add('Operator', 'john');

        $this->assertInstanceOf(Assignment::class, $storage->get('Operator', 'john'));
    }

    protected function populateDb(): void
    {
        $time = time();
        $items = [
            [
                'name' => 'Researcher',
                'type' => Item::TYPE_ROLE,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
            [
                'name' => 'Accountant',
                'type' => Item::TYPE_ROLE,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
            [
                'name' => 'Quality control specialist',
                'type' => Item::TYPE_ROLE,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
            [
                'name' => 'Operator',
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
                'name' => 'Support specialist',
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
                'itemName' => 'Researcher',
                'userId' => 'john',
                'createdAt' => $time,
            ],
            [
                'itemName' => 'Accountant',
                'userId' => 'john',
                'createdAt' => $time,
            ],
            [
                'itemName' => 'Quality control specialist',
                'userId' => 'john',
                'createdAt' => $time,
            ],
            [
                'itemName' => 'Operator',
                'userId' => 'jack',
                'createdAt' => $time,
            ],
            [
                'itemName' => 'Manager',
                'userId' => 'jack',
                'createdAt' => $time,
            ],
            [
                'itemName' => 'Support specialist',
                'userId' => 'jack',
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
