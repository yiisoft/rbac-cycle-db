<?php

namespace Yiisoft\Rbac\Cycle\Tests;

use Cycle\Database\Query\InsertQuery;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\Permission;

class ItemsStorageTest extends TestCase
{
    public function testUpdate(): void
    {
        $storage = $this->getStorage();
        $item = $storage->get('Parent 1');
        $this->assertEmpty($item->getRuleName());

        $item = $item
            ->withName('Super Admin')
            ->withRuleName('super admin');
        $storage->update('Parent 1', $item);
        $this->assertSame('Super Admin', $storage->get('Super Admin')->getName());
        $this->assertSame('super admin', $storage->get('Super Admin')->getRuleName());
    }


    public function testGet(): void
    {
        $storage = $this->getStorage();
        $item = $storage->get('Parent 3');

        $this->assertInstanceOf(Permission::class, $item);
        $this->assertSame(Item::TYPE_PERMISSION, $item->getType());
    }

    public function testGetPermission(): void
    {
        $storage = $this->getStorage();
        $permission = $storage->getPermission('Child 1');

        $this->assertInstanceOf(Permission::class, $permission);
    }

    public function testAddChild()
    {
    }

    public function testClear()
    {
    }

    public function testGetChildren()
    {
    }

    public function testGetRoles()
    {
    }

    public function testGetPermissions()
    {
    }

    public function testRemove()
    {
    }

    public function testGetParents()
    {
    }

    public function testRemoveChildren()
    {
    }

    public function testGetRole()
    {
    }

    public function testAdd()
    {
    }

    public function testRemoveChild()
    {
    }

    public function testGetAll()
    {
    }

    public function testHasChildren()
    {
    }

    public function testClearPermissions()
    {
    }

    public function testClearRoles()
    {
    }

    protected function populateDb(): void
    {
        $items = [
            [
                'name' => 'Parent 1',
                'type' => Item::TYPE_ROLE,
                'createdAt' => time(),
                'updatedAt' => time(),
            ],
            [
                'name' => 'Parent 2',
                'type' => Item::TYPE_ROLE,
                'createdAt' => time(),
                'updatedAt' => time(),
            ],
            [
                'name' => 'Parent 3',
                'type' => Item::TYPE_PERMISSION,
                'createdAt' => time(),
                'updatedAt' => time(),
            ],
            [
                'name' => 'Child 1',
                'type' => Item::TYPE_PERMISSION,
                'createdAt' => time(),
                'updatedAt' => time(),
            ],
            [
                'name' => 'Child 2',
                'type' => Item::TYPE_ROLE,
                'createdAt' => time(),
                'updatedAt' => time(),
            ],
        ];
        $items_child = [
            [
                'parent' => 'Parent 1',
                'child' => 'Child 1',
            ],
            [
                'parent' => 'Parent 2',
                'child' => 'Child 2',
            ],
        ];

        foreach ($items as $item) {
            $this->getDbal()
                ->database()
                ->table('auth_item')
                ->insertOne($item);
        }

        foreach ($items_child as $item) {
            $this->getDbal()
                ->database()
                ->table('auth_item_child')
                ->insertOne($item);
        }

    }

    private function getStorage()
    {
        return new ItemsStorage('auth_item', $this->getDbal());
    }
}
