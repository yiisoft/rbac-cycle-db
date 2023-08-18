<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\ForeignKeyInterface;
use Yiisoft\Rbac\Cycle\DbSchemaManager;

trait SchemaTrait
{
    protected function checkAssignmentsTable(): void
    {
        $database = $this->getDatabase();
        $this->assertTrue($database->hasTable(DbSchemaManager::ASSIGNMENTS_TABLE));

        $table = $database->table(DbSchemaManager::ASSIGNMENTS_TABLE);
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
        $this->assertCount(0, $table->getForeignKeys());
        $this->assertCount(0, $table->getIndexes());
    }

    protected function checkItemsChildrenTable(): void
    {
        $database = $this->getDatabase();
        $this->assertTrue($database->hasTable(DbSchemaManager::ITEMS_CHILDREN_TABLE));

        $table = $database->table(DbSchemaManager::ITEMS_CHILDREN_TABLE);
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

        $this->assertCount(2, $this->getDatabase()->table(DbSchemaManager::ITEMS_CHILDREN_TABLE)->getIndexes());
        $this->assertIndex(DbSchemaManager::ITEMS_CHILDREN_TABLE, 'idx-yii_rbac_item_child-parent', ['parent']);
        $this->assertIndex(DbSchemaManager::ITEMS_CHILDREN_TABLE, 'idx-yii_rbac_item_child-child', ['child']);
    }

    protected function checkItemsChildrenTableForeignKeys(
        string $expectedParentForeignKeyName = 'fk-yii_rbac_item_child-parent',
        string $expectedChildForeignKeyName = 'fk-yii_rbac_item_child-child',
    ): void {
        $this->assertCount(2, $this->getDatabase()->table(DbSchemaManager::ITEMS_CHILDREN_TABLE)->getForeignKeys());
        $this->assertForeignKey(
            table: DbSchemaManager::ITEMS_CHILDREN_TABLE,
            expectedColumns: ['parent'],
            expectedForeignTable: DbSchemaManager::ITEMS_TABLE,
            expectedForeignKeys: ['name'],
            expectedName: $expectedParentForeignKeyName,
        );
        $this->assertForeignKey(
            table: DbSchemaManager::ITEMS_CHILDREN_TABLE,
            expectedColumns: ['child'],
            expectedForeignTable: DbSchemaManager::ITEMS_TABLE,
            expectedForeignKeys: ['name'],
            expectedName: $expectedChildForeignKeyName,
        );
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
        $this->assertTrue($database->hasTable(DbSchemaManager::ITEMS_TABLE));

        $table = $database->table(DbSchemaManager::ITEMS_TABLE);
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

        $this->assertSame(['name'], $table->getPrimaryKeys());
        $this->assertCount(0, $table->getForeignKeys());

        $this->assertCount(1, $table->getIndexes());
        $this->assertIndex(DbSchemaManager::ITEMS_TABLE, 'idx-yii_rbac_item-type', ['type']);
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
        $schemaManager = $this->createSchemaManager();

        $this->assertFalse($schemaManager->hasTable($schemaManager->getItemsTable()));
        $this->assertFalse($schemaManager->hasTable($schemaManager->getAssignmentsTable()));
        $this->assertFalse($schemaManager->hasTable($schemaManager->getItemsChildrenTable()));
    }
}
