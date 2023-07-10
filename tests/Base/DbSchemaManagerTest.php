<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\ForeignKeyInterface;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use InvalidArgumentException;
use Yiisoft\Rbac\Cycle\DbSchemaManager;

abstract class DbSchemaManagerTest extends TestCase
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

    protected function populateDatabase(): void
    {
        // Skip
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
        $arguments = ['database' => $this->getDatabase()];
        $arguments = array_merge($tableNameArguments, $arguments);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("$expectedWrongTableName table name can't be empty.");
        new DbSchemaManager(...$arguments);
    }

    public function dataCreateTablesSeparately(): array
    {
        return [
            [self::ITEMS_CHILDREN_TABLE],
            [null],
        ];
    }

    /**
     * @dataProvider dataCreateTablesSeparately
     */
    public function testCreateTablesSeparately(string|null $itemsChildrenTable): void
    {
        $schemaManager = $this->createSchemaManager($itemsChildrenTable);
        $schemaManager->createItemsTable();
        $schemaManager->createItemsChildrenTable();
        $schemaManager->createAssignmentsTable();

        $this->checkTables();
    }

    public function testEnsureTablesMultiple(): void
    {
        $schemaManager = $this->createSchemaManager();
        $schemaManager->ensureTables();
        $schemaManager->ensureTables();

        $this->checkTables();
    }

    private function checkTables(): void
    {
        $this->checkItemsTable();
        $this->checkAssignmentsTable();
        $this->checkItemsChildrenTable();
    }

    private function checkItemsTable(): void
    {
        $database = $this->getDatabase();
        $this->assertTrue($database->hasTable(self::ITEMS_TABLE));

        $table = $database->table(self::ITEMS_TABLE);
        $columns = $table->getColumns();

        $this->assertArrayHasKey('name', $columns);
        $name = $columns['name'];
        $this->assertSame('string', $name->getType());
        $this->assertSame(128, $name->getSize());
        $this->assertFalse($name->isNullable());

        $this->assertArrayHasKey('type', $columns);
        $type = $columns['type'];
        $this->assertSame('string', $type->getType());
        $this->assertSame(10, $type->getSize());
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
        $this->assertSame('int', $createdAt->getType());
        $this->assertFalse($createdAt->isNullable());

        $this->assertArrayHasKey('updatedAt', $columns);
        $updatedAt = $columns['updatedAt'];
        $this->assertSame('int', $updatedAt->getType());
        $this->assertFalse($updatedAt->isNullable());

        $this->assertCount(1, $table->getIndexes());
        /** @var AbstractIndex $index */
        $index = array_values($table->getIndexes())[0];
        $this->assertSame(['type'], $index->getColumns());

        $this->assertSame(['name'], $table->getPrimaryKeys());
    }

    private function checkAssignmentsTable(): void
    {
        $database = $this->getDatabase();
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
        $this->assertSame('int', $createdAt->getType());
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
        $database = $this->getDatabase();
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

        $this->assertSame(['parent', 'child'], $table->getPrimaryKeys());

        $this->assertCount(2, $table->getForeignKeys());
        foreach ($table->getForeignKeys() as $foreignKey) {
            $columns = $foreignKey->getColumns();
            $this->assertCount(1, $columns);
            $column = $columns[0];
            $this->assertContains($column, ['parent', 'child']);

            $this->assertSame(self::ITEMS_TABLE, $foreignKey->getForeignTable());
            $this->assertSame(['name'], $foreignKey->getForeignKeys());
            $this->assertSame(ForeignKeyInterface::NO_ACTION, $foreignKey->getUpdateRule());
            $this->assertSame(ForeignKeyInterface::NO_ACTION, $foreignKey->getDeleteRule());
        }
    }
}
