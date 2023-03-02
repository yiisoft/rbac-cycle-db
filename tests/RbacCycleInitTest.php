<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests;

use InvalidArgumentException;
use Yiisoft\Rbac\Cycle\Command\RbacCycleInit;

class RbacCycleInitTest extends TestCase
{
    protected function setUp(): void
    {
        // Skip
    }

    protected function tearDown(): void
    {
        if (!str_starts_with($this->getName(), 'testInitWithEmptyTableNames')) {
            parent::tearDown();
        }
    }

    public function dataInitWithEmptyTableNames(): array
    {
        return [
            [['itemsTable' => '', 'assignmentsTable' => 'assignments', 'dbal' => $this->getDbal()], 'Items'],
            [['itemsTable' => 'items', 'assignmentsTable' => '', 'dbal' => $this->getDbal()], 'Assignments'],
            [['itemsTable' => '', 'assignmentsTable' => '', 'dbal' => $this->getDbal()], 'Items'],
            [
                [
                    'itemsTable' => 'items',
                    'assignmentsTable' => 'assignments',
                    'itemsChildrenTable' => '',
                    'dbal' => $this->getDbal(),
                ],
                'Items children',
            ],
            [
                ['itemsTable' => '', 'assignmentsTable' => '', 'itemsChildrenTable' => '', 'dbal' => $this->getDbal()],
                'Items',
            ],
        ];
    }

    /**
     * @dataProvider dataInitWithEmptyTableNames
     */
    public function testInitWithEmptyTableNames(array $arguments, $expectedWrongTableName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("$expectedWrongTableName table name can't be empty.");
        new RbacCycleInit(...$arguments);
    }

    public function testExecute(): void
    {
        $this->createDbTables();

        $this->assertTrue($this->getDbal()->database()->hasTable('auth_item'));
        $this->assertTrue($this->getDbal()->database()->hasTable('auth_item_child'));
        $this->assertTrue($this->getDbal()->database()->hasTable('auth_assignment'));
    }

    protected function populateDb(): void
    {
    }
}
