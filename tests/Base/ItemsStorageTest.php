<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\Injection\Fragment;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;

abstract class ItemsStorageTest extends TestCase
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

        $item = $storage->get('Super Admin');
        $this->assertNotNull($item);

        $this->assertSame('Super Admin', $item->getName());
        $this->assertSame('super admin', $item->getRuleName());

        $this->assertTrue($storage->hasChildren('Super Admin'));
    }

    public function testUpdateWithNoChildren(): void
    {
        // TODO: Use data provider.
        $storage = $this->getStorage();

        $item = $storage->get('Parent 3');
        $this->assertNull($item->getRuleName());

        $item = $item
            ->withName('Super Admin')
            ->withRuleName('super admin');
        $storage->update('Parent 3', $item);

        $this->assertSame('Super Admin', $item->getName());
        $this->assertSame('super admin', $item->getRuleName());

        $this->assertFalse($storage->hasChildren('Super Admin'));
    }

    public function testGet(): void
    {
        $storage = $this->getStorage();
        $item = $storage->get('Parent 3');

        $this->assertInstanceOf(Permission::class, $item);
        $this->assertSame(Item::TYPE_PERMISSION, $item->getType());
        $this->assertSame('Parent 3', $item->getName());
    }

    public function testGetWithNonExistingName(): void
    {
        $storage = $this->getStorage();
        $this->assertNull($storage->get('Non-existing name'));
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
        $storage->clear();

        $this->assertEmpty($storage->getAll());

        /** @psalm-var array<0, 1>|false $itemsChildrenExist */
        $itemsChildrenExist = $this
            ->getDbal()
            ->database()
            ->select([new Fragment('1 as item_exists')])
            ->from(self::ITEMS_CHILDREN_TABLE)
            ->limit(1)
            ->run()
            ->fetch();
        $this->assertSame(false, $itemsChildrenExist);
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

        $this->assertCount(5, $permissions);
        $this->assertContainsOnlyInstancesOf(Permission::class, $permissions);
    }

    public function testRemove(): void
    {
        $storage = $this->getStorage();
        $storage->remove('Parent 2');

        $this->assertNull($storage->get('Parent 2'));
        $this->assertNotEmpty($storage->getAll());
        $this->assertFalse($storage->hasChildren('Parent 2'));

        $itemsChildren = $this
            ->getDbal()
            ->database()
            ->select()
            ->from(self::ITEMS_CHILDREN_TABLE)
            ->fetchAll();
        $expectedItemsChildren = [
            ['parent' => 'Parent 1', 'child' => 'Child 1'],
            ['parent' => 'Parent 4', 'child' => 'Child 4'],
            ['parent' => 'Parent 5', 'child' => 'Child 5'],
        ];
        $this->assertSame($expectedItemsChildren, $itemsChildren);
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
        $storage->addChild('Parent 2', 'Child 1');
        $storage->removeChild('Parent 2', 'Child 1');

        $children = $storage->getChildren('Parent 2');
        $this->assertNotEmpty($children);
        $this->assertArrayNotHasKey('Child 1', $children);

        $this->assertArrayHasKey('Child 1', $storage->getChildren('Parent 1'));
    }

    public function testGetAll(): void
    {
        $storage = $this->getStorage();
        $this->assertCount(10, $storage->getAll());
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

        $itemsChildren = $this
            ->getDbal()
            ->database()
            ->select()
            ->from(self::ITEMS_CHILDREN_TABLE)
            ->fetchAll();
        $expectedItemsChildren = [
            ['parent' => 'Parent 2', 'child' => 'Child 2'],
            ['parent' => 'Parent 2', 'child' => 'Child 3'],
        ];
        $this->assertSame($expectedItemsChildren, $itemsChildren);
    }

    public function testClearRoles(): void
    {
        $storage = $this->getStorage();
        $storage->clearRoles();

        $all = $storage->getAll();
        $this->assertNotEmpty($all);
        $this->assertContainsOnlyInstancesOf(Permission::class, $storage->getAll());

        $this->assertTrue($storage->hasChildren('Parent 5'));
        $itemsChildrenCount = $this
            ->getDbal()
            ->database()
            ->select([new Fragment('1 as item_exists')])
            ->from(self::ITEMS_CHILDREN_TABLE)
            ->count();
        $this->assertSame(1, $itemsChildrenCount);
    }

    protected function populateDb(): void
    {
        $time = time();
        $items = [
            ['Parent 1', Item::TYPE_ROLE],
            ['Parent 2', Item::TYPE_ROLE],
            // Parent without children
            ['Parent 3', Item::TYPE_PERMISSION],
            ['Parent 4', Item::TYPE_PERMISSION],
            ['Parent 5', Item::TYPE_PERMISSION],
            ['Child 1', Item::TYPE_PERMISSION],
            ['Child 2', Item::TYPE_ROLE],
            ['Child 3', Item::TYPE_ROLE],
            ['Child 4', Item::TYPE_ROLE],
            ['Child 5', Item::TYPE_PERMISSION],
        ];
        $items = array_map(
            static function (array $item) use ($time): array {
                $item[] = $time;
                $item[] = $time;

                return $item;
            },
            $items,
        );
        $itemsChildren = [
            // Parent: role, child: permission
            ['parent' => 'Parent 1', 'child' => 'Child 1'],
            // Parent: role, child: role
            ['parent' => 'Parent 2', 'child' => 'Child 2'],
            ['parent' => 'Parent 2', 'child' => 'Child 3'],
            // Parent: permission, child: role
            ['parent' => 'Parent 4', 'child' => 'Child 4'],
            // Parent: permission, child: permission
            ['parent' => 'Parent 5', 'child' => 'Child 5'],
        ];

        $this->getDbal()
            ->database()
            ->insert(self::ITEMS_TABLE)
            ->columns(['name', 'type', 'createdAt', 'updatedAt'])
            ->values($items)
            ->run();

        $this->getDbal()
            ->database()
            ->insert(self::ITEMS_CHILDREN_TABLE)
            ->columns(['parent', 'child'])
            ->values($itemsChildren)
            ->run();
    }

    private function getStorage(): ItemsStorage
    {
        return new ItemsStorage(self::ITEMS_TABLE, $this->getDbal()->database());
    }
}
