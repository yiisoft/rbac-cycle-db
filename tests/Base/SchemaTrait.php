<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\ForeignKeyInterface;

trait SchemaTrait
{
    public static function setUpBeforeClass(): void
    {
        // Skip
    }

    public static function tearDownAfterClass(): void
    {
        // Skip
    }

    protected function setUp(): void
    {
        // Skip
    }

    protected function populateDatabase(): void
    {
        // Skip
    }

    public function testSchema(): void
    {
        $this->checkNoTables();
        $this->runMigrations();
        $this->checkTables();

        $this->rollbackMigrations();
        $this->checkNoTables();
    }

    protected function checkAssignmentsTable(): void
    {
        $database = $this->getDatabase();
        $this->assertTrue($database->hasTable(self::$assignmentsTable));

        $table = $database->table(self::$assignmentsTable);
        $columns = $table->getColumns();

        $this->assertArrayHasKey('item_name', $columns);
        $itemName = $columns['item_name'];
        $this->assertSame('string', $itemName->getType());
        $this->assertSame(126, $itemName->getSize());
        $this->assertFalse($itemName->isNullable());

        $this->assertArrayHasKey('user_id', $columns);
        $userId = $columns['user_id'];
        $this->assertSame('string', $userId->getType());
        $this->assertSame(126, $userId->getSize());
        $this->assertFalse($userId->isNullable());

        $this->assertArrayHasKey('created_at', $columns);
        $createdAt = $columns['created_at'];
        $this->assertSame('int', $createdAt->getType());
        $this->assertFalse($createdAt->isNullable());

        $this->assertSame(['item_name', 'user_id'], $table->getPrimaryKeys());
        $this->assertCount(0, $table->getForeignKeys());
        $this->assertCount(0, $table->getIndexes());
    }

    protected function checkItemsChildrenTable(): void
    {
        $database = $this->getDatabase();
        $this->assertTrue($database->hasTable(self::$itemsChildrenTable));

        $table = $database->table(self::$itemsChildrenTable);
        $columns = $table->getColumns();

        $this->assertArrayHasKey('parent', $columns);
        $parent = $columns['parent'];
        $this->assertSame('string', $parent->getType());
        $this->assertSame(126, $parent->getSize());
        $this->assertFalse($parent->isNullable());

        $this->assertArrayHasKey('child', $columns);
        $child = $columns['child'];
        $this->assertSame('string', $child->getType());
        $this->assertSame(126, $child->getSize());
        $this->assertFalse($child->isNullable());

        $this->assertSame(['parent', 'child'], $table->getPrimaryKeys());

        $this->assertCount(2, $this->getDatabase()->table(self::$itemsChildrenTable)->getIndexes());
        $this->assertIndex(self::$itemsChildrenTable, 'idx-yii_rbac_item_child-parent', ['parent']);
        $this->assertIndex(self::$itemsChildrenTable, 'idx-yii_rbac_item_child-child', ['child']);
    }

    protected function checkItemsChildrenTableForeignKeys(
        string $expectedParentForeignKeyName = 'fk-yii_rbac_item_child-parent',
        string $expectedChildForeignKeyName = 'fk-yii_rbac_item_child-child',
    ): void {
        $this->assertCount(2, $this->getDatabase()->table(self::$itemsChildrenTable)->getForeignKeys());
        $this->assertForeignKey(
            table: self::$itemsChildrenTable,
            expectedColumns: ['parent'],
            expectedForeignTable: self::$itemsTable,
            expectedForeignKeys: ['name'],
            expectedName: $expectedParentForeignKeyName,
        );
        $this->assertForeignKey(
            table: self::$itemsChildrenTable,
            expectedColumns: ['child'],
            expectedForeignTable: self::$itemsTable,
            expectedForeignKeys: ['name'],
            expectedName: $expectedChildForeignKeyName,
        );
    }

    protected function checkTables(): void
    {
        $this->checkItemsTable();
        $this->checkAssignmentsTable();
        $this->checkItemsChildrenTable();
    }

    private function checkItemsTable(): void
    {
        $database = $this->getDatabase();
        $this->assertTrue($database->hasTable(self::$itemsTable));

        $table = $database->table(self::$itemsTable);
        $columns = $table->getColumns();

        $this->assertArrayHasKey('name', $columns);
        $name = $columns['name'];
        $this->assertSame('string', $name->getType());
        $this->assertSame(126, $name->getSize());
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

        $this->assertArrayHasKey('rule_name', $columns);
        $ruleName = $columns['rule_name'];
        $this->assertSame('string', $ruleName->getType());
        $this->assertSame(64, $ruleName->getSize());
        $this->assertTrue($ruleName->isNullable());

        $this->assertArrayHasKey('created_at', $columns);
        $createdAt = $columns['created_at'];
        $this->assertSame('int', $createdAt->getType());
        $this->assertFalse($createdAt->isNullable());

        $this->assertArrayHasKey('updated_at', $columns);
        $updatedAt = $columns['updated_at'];
        $this->assertSame('int', $updatedAt->getType());
        $this->assertFalse($updatedAt->isNullable());

        $this->assertSame(['name'], $table->getPrimaryKeys());
        $this->assertCount(0, $table->getForeignKeys());

        $this->assertCount(1, $table->getIndexes());
        $this->assertIndex(self::$itemsTable, 'idx-yii_rbac_item-type', ['type']);
    }

    private function assertForeignKey(
        string $table,
        array $expectedColumns,
        string $expectedForeignTable,
        array $expectedForeignKeys,
        string $expectedName,
        string $expectedUpdateRule = ForeignKeyInterface::NO_ACTION,
        string $expectedDeleteRule = ForeignKeyInterface::NO_ACTION,
    ): void {
        $foreignKeys = $this->getDatabase()->table($table)->getForeignKeys();

        $this->assertArrayHasKey($expectedName, $foreignKeys);
        $foreignKey = $foreignKeys[$expectedName];
        $this->assertSame($expectedColumns, $foreignKey->getColumns());
        $this->assertSame($expectedForeignTable, $foreignKey->getForeignTable());
        $this->assertSame($expectedForeignKeys, $foreignKey->getForeignKeys());
        $this->assertSame($expectedName, $foreignKey->getName());
        $this->assertSame($expectedUpdateRule, $foreignKey->getUpdateRule());
        $this->assertSame($expectedDeleteRule, $foreignKey->getDeleteRule());
    }

    private function assertIndex(string $table, string $expectedName, array $expectedColumns): void
    {
        $indexes = $this->getDatabase()->table($table)->getIndexes();

        $this->assertArrayHasKey($expectedName, $indexes);
        $index = $indexes[$expectedName];
        $this->assertSame($expectedColumns, $index->getColumns());
        $this->assertSame($expectedName, $index->getName());
    }

    private function checkNoTables(): void
    {
        $this->assertFalse($this->getDatabase()->hasTable(self::$itemsTable));
        $this->assertFalse($this->getDatabase()->hasTable(self::$assignmentsTable));
        $this->assertFalse($this->getDatabase()->hasTable(self::$itemsChildrenTable));
    }
}
