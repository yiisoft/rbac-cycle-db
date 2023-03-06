<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests;

use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;

class ItemsStorageTest extends TestCase
{
    public function testUpdate(): void
    {
        $storage = $this->getStorage();
        $item = $storage->get('Parent 1');
        $this->assertNull($item->getRuleName());

        $item = $item
            ->withName('Super Admin')
            ->withRuleName('super admin');
        $storage->update('Parent 1', $item);
        $this->assertSame('Super Admin', $storage->get('Super Admin')?->getName());
        $this->assertSame('super admin', $storage->get('Super Admin')?->getRuleName());
    }

    public function testGet(): void
    {
        $storage = $this->getStorage();
        $item = $storage->get('Parent 3');

        $this->assertInstanceOf(Permission::class, $item);
        $this->assertSame(Item::TYPE_PERMISSION, $item->getType());
        $this->assertSame('Parent 3', $item->getName());
    }

    public function existsProvider(): array
    {
        return [
            ['Parent 1', true],
            ['Parent 2', true],
            ['Parent 3', true],
            ['Parent 100', false],
            ['Child 1', true],
            ['Child 2', true],
            ['Child 100', false],
        ];
    }

    /**
     * @dataProvider existsProvider
     */
    public function testExists(string $name, bool $exists): void
    {
        $storage = $this->getStorage();
        $this->assertSame($storage->exists($name), $exists);
    }

    public function testGetPermission(): void
    {
        $storage = $this->getStorage();
        $permission = $storage->getPermission('Child 1');

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertSame('Child 1', $permission->getName());
    }

    public function testAddChild(): void
    {
        $storage = $this->getStorage();
        $storage->addChild('Parent 2', 'Child 1');

        $children = $storage->getChildren('Parent 2');
        $this->assertCount(3, $children);

        foreach ($children as $name => $item) {
            $this->assertSame($name, $item->getName());
        }
    }

    public function testClear(): void
    {
        $storage = $this->getStorage();
        $this->clear();

        $this->assertEmpty($storage->getAll());
        $this->assertEmpty($storage->getChildren('Parent 2'));
    }

    public function testGetChildren(): void
    {
        $storage = $this->getStorage();

        $children = $storage->getChildren('Parent 1');

        $this->assertCount(1, $children);
        $this->assertContainsOnlyInstancesOf(Item::class, $children);
    }

    public function testGetRoles(): void
    {
        $storage = $this->getStorage();
        $roles = $storage->getRoles();

        $this->assertNotEmpty($roles);
        $this->assertContainsOnlyInstancesOf(Role::class, $roles);
    }

    public function testGetPermissions(): void
    {
        $storage = $this->getStorage();
        $permissions = $storage->getPermissions();

        $this->assertCount(2, $permissions);
        $this->assertContainsOnlyInstancesOf(Permission::class, $permissions);
    }

    public function testRemove(): void
    {
        $storage = $this->getStorage();
        $storage->remove('Parent 3');

        $this->assertEmpty($storage->get('Parent 3'));
        $this->assertNotEmpty($storage->getAll());
    }

    public function getParentsProvider(): array
    {
        return [
            ['Child 1', ['Parent 1']],
            ['Child 2', ['Parent 2']],
        ];
    }

    /**
     * @dataProvider getParentsProvider
     */
    public function testGetParents(string $childName, array $expectedParents): void
    {
        $storage = $this->getStorage();
        $parents = $storage->getParents($childName);

        $this->assertCount(count($expectedParents), $parents);
        foreach ($parents as $parentName => $parent) {
            $this->assertContains($parentName, $expectedParents);
            $this->assertSame($parentName, $parent->getName());
        }
    }

    public function testRemoveChildren(): void
    {
        $storage = $this->getStorage();
        $storage->removeChildren('Parent 2');

        $this->assertFalse($storage->hasChildren('Parent 2'));
        $this->assertTrue($storage->hasChildren('Parent 1'));
    }

    public function testGetRole(): void
    {
        $storage = $this->getStorage();
        $role = $storage->getRole('Parent 1');

        $this->assertNotEmpty($role);
        $this->assertInstanceOf(Role::class, $role);
        $this->assertSame('Parent 1', $role->getName());
    }

    public function testAdd(): void
    {
        $storage = $this->getStorage();
        $newItem = new Permission('Delete post');
        $storage->add($newItem);

        $this->assertInstanceOf(Permission::class, $storage->get('Delete post'));
    }

    public function testRemoveChild(): void
    {
        $storage = $this->getStorage();
        $storage->addChild('Parant 2', 'Child 1');
        $storage->removeChild('Parent 2', 'Child 2');

        $children = $storage->getChildren('Parent 2');
        $this->assertNotEmpty($children);
        $this->assertArrayNotHasKey('Child 1', $children);

        $this->assertArrayHasKey('Child 1', $storage->getChildren('Parent 1'));
    }

    public function testGetAll(): void
    {
        $storage = $this->getStorage();
        $this->assertCount(6, $storage->getAll());
    }

    public function testHasChildren(): void
    {
        $storage = $this->getStorage();

        $this->assertTrue($storage->hasChildren('Parent 1'));
        $this->assertFalse($storage->hasChildren('Parent 3'));
    }

    public function testClearPermissions(): void
    {
        $storage = $this->getStorage();
        $storage->clearPermissions();

        $all = $storage->getAll();
        $this->assertNotEmpty($all);
        $this->assertContainsOnlyInstancesOf(Role::class, $all);
    }

    public function testClearRoles(): void
    {
        $storage = $this->getStorage();
        $storage->clearRoles();

        $all = $storage->getAll();
        $this->assertNotEmpty($all);
        $this->assertContainsOnlyInstancesOf(Permission::class, $storage->getAll());
    }

    protected function populateDb(): void
    {
        $time = time();
        $items = [
            [
                'name' => 'Parent 1',
                'type' => Item::TYPE_ROLE,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
            [
                'name' => 'Parent 2',
                'type' => Item::TYPE_ROLE,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
            [
                'name' => 'Parent 3',
                'type' => Item::TYPE_PERMISSION,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
            [
                'name' => 'Child 1',
                'type' => Item::TYPE_PERMISSION,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
            [
                'name' => 'Child 2',
                'type' => Item::TYPE_ROLE,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
            [
                'name' => 'Child 3',
                'type' => Item::TYPE_ROLE,
                'createdAt' => $time,
                'updatedAt' => $time,
            ],
        ];
        $itemsChildren = [
            [
                'parent' => 'Parent 1',
                'child' => 'Child 1',
            ],
            [
                'parent' => 'Parent 2',
                'child' => 'Child 2',
            ],
            [
                'parent' => 'Parent 2',
                'child' => 'Child 3',
            ],
        ];

        foreach ($items as $item) {
            $this->getDbal()
                ->database()
                ->insert(self::ITEMS_TABLE)
                ->values($item)
                ->run();
        }

        foreach ($itemsChildren as $itemChild) {
            $this->getDbal()
                ->database()
                ->insert(self::ITEMS_CHILDREN_TABLE)
                ->values($itemChild)
                ->run();
        }
    }

    private function getStorage(): ItemsStorage
    {
        return new ItemsStorage(self::ITEMS_TABLE, $this->getDbal());
    }
}
