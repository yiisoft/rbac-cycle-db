<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests;

use Cycle\Database\ForeignKeyInterface;
use Cycle\Database\Schema\AbstractForeignKey;
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
        $this->checkAssignmentsTable();
        $this->checkItemsChildrenTable();

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
        $this->assertTrue($database->hasTable(self::ITEMS_TABLE));

        $table = $database->table(self::ITEMS_TABLE);
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

    private function checkAssignmentsTable(): void
    {
        $database = $this->getDbal()->database();
        $this->assertTrue($database->hasTable(self::ASSIGNMENTS_TABLE));

        $table = $database->table(self::ASSIGNMENTS_TABLE);
        $columns = $table->getColumns();

        $this->assertArrayHasKey('itemName', $columns);
        $itemName = $columns['itemName'];
        $this->assertSame('string', $itemName->getType());
        $this->assertSame(128, $itemName->getSize());
        $this->assertFalse($itemName->isNullable());

        $this->assertArrayHasKey('userId', $columns);
        $userId = $columns['userId'];
        $this->assertSame('string', $userId->getType());
        $this->assertSame(128, $userId->getSize());
        $this->assertFalse($userId->isNullable());

        $this->assertArrayHasKey('createdAt', $columns);
        $createdAt = $columns['createdAt'];
        $this->assertSame('string', $createdAt->getType());
        $this->assertFalse($createdAt->isNullable());

        $this->assertSame(['itemName', 'userId'], $table->getPrimaryKeys());

        $this->assertCount(1, $table->getForeignKeys());
        /** @var AbstractForeignKey $foreignKey */
        $foreignKey = array_values($table->getForeignKeys())[0];
        $this->assertSame(['itemName'], $foreignKey->getColumns());
        $this->assertSame(self::ITEMS_TABLE, $foreignKey->getForeignTable());
        $this->assertSame(['name'], $foreignKey->getForeignKeys());
        $this->assertSame(ForeignKeyInterface::CASCADE, $foreignKey->getUpdateRule());
        $this->assertSame(ForeignKeyInterface::CASCADE, $foreignKey->getDeleteRule());
    }

    private function checkItemsChildrenTable(): void
    {
        $database = $this->getDbal()->database();
        $this->assertTrue($database->hasTable(self::ITEMS_CHILDREN_TABLE));

        $table = $database->table(self::ITEMS_CHILDREN_TABLE);
        $columns = $table->getColumns();

        $this->assertArrayHasKey('parent', $columns);
        $parent = $columns['parent'];
        $this->assertSame('string', $parent->getType());
        $this->assertSame(128, $parent->getSize());
        $this->assertFalse($parent->isNullable());

        $this->assertArrayHasKey('child', $columns);
        $child = $columns['child'];
        $this->assertSame('string', $child->getType());
        $this->assertSame(128, $child->getSize());
        $this->assertFalse($child->isNullable());

        $this->assertCount(2, $table->getForeignKeys());
        foreach ($table->getForeignKeys() as $foreignKey) {
            $columns = $foreignKey->getColumns();
            $this->assertCount(1, $columns);
            $column = $columns[0];
            $this->assertContains($column, ['parent', 'child']);

            $this->assertSame(self::ITEMS_TABLE, $foreignKey->getForeignTable());
            $this->assertSame(['name'], $foreignKey->getForeignKeys());
            $this->assertSame(ForeignKeyInterface::CASCADE, $foreignKey->getUpdateRule());
            $this->assertSame(ForeignKeyInterface::CASCADE, $foreignKey->getDeleteRule());
        }
    }

    protected function populateDb(): void
    {
    }
}
