<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\Injection\Fragment;
use DateTime;
use InvalidArgumentException;
use SlopeIt\ClockMock\ClockMock;
use Yiisoft\Rbac\Cycle\Exception\SeparatorCollisionException;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\Tests\Common\ItemsStorageTestTrait;

abstract class ItemsStorageTest extends TestCase
{
    use ItemsStorageTestTrait {
        setUp as protected traitSetUp;
        tearDown as protected traitTearDown;
        testClear as protected traitTestClear;
        dataRemove as public traitDataRemove;
        testRemove as protected traitTestRemove;
        testClearPermissions as protected traitTestClearPermissions;
        testClearRoles as protected traitTestClearRoles;
    }

    protected static array $migrationsSubfolders = ['items'];

    protected function setUp(): void
    {
        if ($this->name() === 'testGetHierarchyWithCustomSeparator') {
            ClockMock::freeze(new DateTime('2023-12-24 17:51:18'));
        }

        parent::setUp();
        $this->traitSetUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->traitTearDown();

        if ($this->name() === 'testGetHierarchyWithCustomSeparator') {
            ClockMock::reset();
        }
    }

    public function testClear(): void
    {
        $this->traitTestClear();

        /** @psalm-var array<0, 1>|false $itemsChildrenExist */
        $itemsChildrenExist = $this
            ->getDatabase()
            ->select([new Fragment('1 AS item_exists')])
            ->from(self::$itemsChildrenTable)
            ->run()
            ->fetch();
        $this->assertFalse($itemsChildrenExist);
    }

    /**
     * @dataProvider traitDataRemove
     */
    public function testRemove(string $name): void
    {
        $storage = $this->getItemsStorage();
        $initialItemChildrenCount = count($storage->getAllChildren($name));

        $this->traitTestRemove($name);

        $itemsChildrenCount = $this
            ->getDatabase()
            ->select()
            ->from(self::$itemsChildrenTable)
            ->count();
        $this->assertSame($this->initialItemsChildrenCount - $initialItemChildrenCount, $itemsChildrenCount);
    }

    public function testClearPermissions(): void
    {
        $this->traitTestClearPermissions();

        $itemsChildrenCount = $this
            ->getDatabase()
            ->select()
            ->from(self::$itemsChildrenTable)
            ->count();
        $this->assertSame($this->initialBothRolesChildrenCount, $itemsChildrenCount);
    }

    public function testClearRoles(): void
    {
        $this->traitTestClearRoles();

        $itemsChildrenCount = $this
            ->getDatabase()
            ->select([new Fragment('1 AS item_exists')])
            ->from(self::$itemsChildrenTable)
            ->count();
        $this->assertSame($this->initialBothPermissionsChildrenCount, $itemsChildrenCount);
    }

    public function testGetHierarchySeparatorCollision(): void
    {
        $this->expectException(SeparatorCollisionException::class);
        $this->expectExceptionMessage('Separator collision has been detected.');
        $this->getItemsStorage()->GetHierarchy('posts.view');
    }

    public function testGetHierarchyWithCustomSeparator(): void
    {
        $createdAt = (new DateTime('2023-12-24 17:51:18'))->getTimestamp();
        $postsViewPermission = (new Permission('posts.view'))->withCreatedAt($createdAt)->withUpdatedAt($createdAt);
        $postsViewerRole = (new Role('posts.viewer'))->withCreatedAt($createdAt)->withUpdatedAt($createdAt);
        $postsRedactorRole = (new Role('posts.redactor'))->withCreatedAt($createdAt)->withUpdatedAt($createdAt);
        $postsAdminRole = (new Role('posts.admin'))->withCreatedAt($createdAt)->withUpdatedAt($createdAt);

        $this->assertEquals(
            [
                'posts.view' => ['item' => $postsViewPermission, 'children' => []],
                'posts.viewer' => ['item' => $postsViewerRole, 'children' => ['posts.view' => $postsViewPermission]],
                'posts.redactor' => [
                    'item' => $postsRedactorRole,
                    'children' => ['posts.view' => $postsViewPermission, 'posts.viewer' => $postsViewerRole],
                ],
                'posts.admin' => [
                    'item' => $postsAdminRole,
                    'children' => [
                        'posts.view' => $postsViewPermission,
                        'posts.viewer' => $postsViewerRole,
                        'posts.redactor' => $postsRedactorRole,
                    ],
                ],
            ],
            $this->getItemsStorage()->GetHierarchy('posts.view')
        );
    }

    public static function dataInvalidConfiguration(): array
    {
        $exceptionMessage = 'Names separator must be exactly 1 character long.';

        return [
            [['namesSeparator' => ',,'], $exceptionMessage],
            [['namesSeparator' => ''], $exceptionMessage],
            [['namesSeparator' => ' ,'], $exceptionMessage],
            [['namesSeparator' => ', '], $exceptionMessage],
            [['namesSeparator' => ' , '], $exceptionMessage],
        ];
    }

    /**
     * @dataProvider dataInvalidConfiguration
     */
    public function testInvalidConfiguration(array $arguments, string $exceptionMessage): void
    {
        $arguments = array_merge(['database' => $this->getDatabase()], $arguments);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        new ItemsStorage(...$arguments);
    }

    protected function populateItemsStorage(): void
    {
        $fixtures = $this->getFixtures();

        $this
            ->getDatabase()
            ->insert(self::$itemsTable)
            ->columns(['name', 'type', 'created_at', 'updated_at'])
            ->values($fixtures['items'])
            ->run();
        $this
            ->getDatabase()
            ->insert(self::$itemsChildrenTable)
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
        return $this->createItemsStorage();
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return match ($this->name()) {
            'testGetHierarchySeparatorCollision' => new ItemsStorage($this->getDatabase(), namesSeparator: '.'),
            'testGetHierarchyWithCustomSeparator' => new ItemsStorage($this->getDatabase(), namesSeparator: '|'),
            default => new ItemsStorage($this->getDatabase()),
        };
    }
}
