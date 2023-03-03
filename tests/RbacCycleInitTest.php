<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests;

use Cycle\Database\Schema\AbstractIndex;
use InvalidArgumentException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Yiisoft\Rbac\Cycle\Command\RbacCycleInit;
use Yiisoft\Rbac\Item;

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

        $this->checkItemsTable();

        $this->assertTrue($this->getDbal()->database()->hasTable('auth_item_child'));
        $this->assertTrue($this->getDbal()->database()->hasTable('auth_assignment'));

        $newLine = PHP_EOL;
        $expectedOutput = "\033[34mCreating `auth_item` table...\033[39m$newLine" .
        "\033[42mTable `auth_item` created successfully\033[49m$newLine" .
        "\033[34mCreating `auth_item_child` table...\033[39m$newLine" .
        "\033[42mTable `auth_item_child` created successfully\033[49m$newLine" .
        "\033[34mCreating `auth_assignment` table...\033[39m$newLine" .
        "\033[42mTable `auth_assignment` created successfully\033[49m$newLine" .
        "\033[32mDONE\033[39m$newLine";
        $this->assertSame($expectedOutput, $output->fetch());
    }

    private function checkItemsTable(): void
    {
        $database = $this->getDbal()->database();
        $this->assertTrue($database->hasTable('auth_item'));

        $table = $database->table('auth_item');
        $columns = $table->getColumns();

        $this->assertArrayHasKey('name', $columns);
        $name = $columns['name'];
        $this->assertSame('string', $name->getType());
        $this->assertSame(128, $name->getSize());
        $this->assertTrue($name->isNullable());

        $this->assertArrayHasKey('type', $columns);
        $type = $columns['type'];
        $this->assertSame('string', $type->getType());
        $this->assertSame([Item::TYPE_ROLE, Item::TYPE_PERMISSION], $type->getEnumValues());
        $this->assertFalse($type->isNullable());

        $this->assertArrayHasKey('description', $columns);
        $description = $columns['description'];
        $this->assertSame('string', $description->getType());
        $this->assertSame(191, $description->getSize());
        $this->assertTrue($description->isNullable());

        $this->assertArrayHasKey('ruleName', $columns);
        $ruleName = $columns['ruleName'];
        $this->assertSame('string', $ruleName->getType());
        $this->assertSame(64, $ruleName->getSize());
        $this->assertTrue($ruleName->isNullable());

        $this->assertArrayHasKey('createdAt', $columns);
        $createdAt = $columns['createdAt'];
        $this->assertSame('string', $createdAt->getType());
        $this->assertFalse($createdAt->isNullable());

        $this->assertArrayHasKey('updatedAt', $columns);
        $updatedAt = $columns['updatedAt'];
        $this->assertSame('string', $updatedAt->getType());
        $this->assertFalse($updatedAt->isNullable());

        $this->assertCount(1, $table->getIndexes());
        /** @var AbstractIndex $index */
        $index = array_values($table->getIndexes())[0];
        $this->assertSame(['type'], $index->getColumns());

        $this->assertSame(['name'], $table->getPrimaryKeys());
    }

    protected function populateDb(): void
    {
    }
}
