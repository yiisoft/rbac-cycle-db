<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests;

use InvalidArgumentException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
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
            [['itemsTable' => '', 'assignmentsTable' => 'assignments'], 'Items'],
            [['itemsTable' => 'items', 'assignmentsTable' => ''], 'Assignments'],
            [['itemsTable' => '', 'assignmentsTable' => ''], 'Items'],
            [
                ['itemsTable' => 'items', 'assignmentsTable' => 'assignments', 'itemsChildrenTable' => ''],
                'Items children',
            ],
            [['itemsTable' => '', 'assignmentsTable' => '', 'itemsChildrenTable' => ''], 'Items'],
        ];
    }

    /**
     * @dataProvider dataInitWithEmptyTableNames
     */
    public function testInitWithEmptyTableNames(array $tableNameArguments, $expectedWrongTableName): void
    {
        $arguments = ['dbal' => $this->getDbal()];
        $arguments = array_merge($tableNameArguments, $arguments);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("$expectedWrongTableName table name can't be empty.");
        new RbacCycleInit(...$arguments);
    }

    public function testExecute(): void
    {
        $app = new Application();
        $command = $this->createCommand();
        $app->add($command);

        $output = new BufferedOutput(decorated: true);
        $app->find('rbac/cycle/init')->run(new ArrayInput([]), $output);

        $this->assertTrue($this->getDbal()->database()->hasTable('auth_item'));
        $this->assertTrue($this->getDbal()->database()->hasTable('auth_item_child'));
        $this->assertTrue($this->getDbal()->database()->hasTable('auth_assignment'));

        $expectedOutput = "\033[34mCreating `auth_item` table...\033[39m\n" .
        "\033[42mTable `auth_item` created successfully\033[49m\n" .
        "\033[34mCreating `auth_item_child` table...\033[39m\n" .
        "\033[42mTable `auth_item_child` created successfully\033[49m\n" .
        "\033[34mCreating `auth_assignment` table...\033[39m\n" .
        "\033[42mTable `auth_assignment` created successfully\033[49m\n" .
        "\033[32mDONE\033[39m\n";
        $this->assertSame($expectedOutput, $output->fetch());
    }

    protected function populateDb(): void
    {
    }
}
