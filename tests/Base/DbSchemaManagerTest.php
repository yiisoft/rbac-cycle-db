<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use InvalidArgumentException;
use Yiisoft\Rbac\Cycle\DbSchemaManager;

abstract class DbSchemaManagerTest extends TestCase
{
    use SchemaTrait;

    protected function setUp(): void
    {
        // Skip
    }

    protected function tearDown(): void
    {
        if (str_starts_with($this->getName(), 'testInitWithEmptyTableNames')) {
            return;
        }

        if ($this->getName() === 'testHasTableWithEmptyString' || $this->getName() === 'testDropTableWithEmptyString') {
            return;
        }

        if (str_starts_with($this->getName(), 'testGet')) {
            return;
        }

        parent::tearDown();
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
        $schemaManager = $this->createSchemaManager(itemsChildrenTable: $itemsChildrenTable);
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

    public function testCreateItemTables(): void
    {
        $schemaManager = $this->createSchemaManager(assignmentsTable: null);
        $schemaManager->ensureTables();

        $this->checkItemsTable();
        $this->checkItemsChildrenTable();

        $this->assertFalse($schemaManager->hasTable(self::ASSIGNMENTS_TABLE));
    }

    public function testHasTableWithEmptyString(): void
    {
        $schemaManager = $this->createSchemaManager();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name must be non-empty.');
        $schemaManager->hasTable('');
    }

    public function testDropTableWithEmptyString(): void
    {
        $schemaManager = $this->createSchemaManager();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name must be non-empty.');
        $schemaManager->dropTable('');
    }

    public function testGetItemsTable(): void
    {
        $this->assertSame(self::ITEMS_TABLE, $this->createSchemaManager()->getItemsTable());
    }

    public function testGetItemsChildrenTable(): void
    {
        $this->assertSame(self::ITEMS_CHILDREN_TABLE, $this->createSchemaManager()->getItemsChildrenTable());
    }

    public function testGetAssignmentsTable(): void
    {
        $this->assertSame(self::ASSIGNMENTS_TABLE, $this->createSchemaManager()->getAssignmentsTable());
    }

    public function testEnsureNoTables(): void
    {
        $schemaManager = $this->createSchemaManager();
        $schemaManager->ensureTables();
        $schemaManager->ensureNoTables();
        $this->checkNoTables();
    }
}
