<?php

declare(strict_types=1);

use Cycle\Database\Table;
use Cycle\Migrations\Migration;

final class CreateItemsTables extends Migration
{
    private const TABLE_PREFIX = 'yii_rbac_';
    private const ITEMS_TABLE = self::TABLE_PREFIX . 'item';
    private const ITEMS_CHILDREN_TABLE = self::TABLE_PREFIX . 'item_child';

    public function up()
    {
        $this->createItemsTable();
        $this->createItemsChildrenTable();
    }

    public function down()
    {
        $this->dropTable(self::ITEMS_CHILDREN_TABLE);
        $this->dropTable(self::ITEMS_TABLE);
    }

    private function createItemsTable(): void
    {
        /** @var Table $table */
        $table = $this->database()->table(self::ITEMS_TABLE);
        $schema = $table->getSchema();

        $schema->string('name', 128)->nullable(false);
        $schema->string('type', 10)->nullable(false);
        $schema->string('description', 191);
        $schema->string('rule_name', 64);
        $schema->integer('created_at')->nullable(false);
        $schema->integer('updated_at')->nullable(false);
        $schema->index(['type'])->setName(sprintf('idx-%s-type', self::ITEMS_TABLE));
        $schema->setPrimaryKeys(['name']);

        $schema->save();
    }

    private function createItemsChildrenTable(): void
    {
        /** @var Table $table */
        $table = $this->database()->table(self::ITEMS_CHILDREN_TABLE);
        $schema = $table->getSchema();

        $schema->string('parent', 128)->nullable(false);
        $schema->string('child', 128)->nullable(false);
        $schema->setPrimaryKeys(['parent', 'child']);

        $schema
            ->foreignKey(['parent'])
            ->references(self::ITEMS_TABLE, ['name'])
            ->setName(sprintf('fk-%s-parent', self::ITEMS_CHILDREN_TABLE));
        $schema->renameIndex(['parent'], sprintf('idx-%s-parent', self::ITEMS_CHILDREN_TABLE));

        $schema
            ->foreignKey(['child'])
            ->references(self::ITEMS_TABLE, ['name'])
            ->setName(sprintf('fk-%s-child', self::ITEMS_CHILDREN_TABLE));
        $schema->renameIndex(['child'], sprintf('idx-%s-child', self::ITEMS_CHILDREN_TABLE));

        $schema->save();
    }

    private function dropTable(string $tableName): void
    {
        /** @var Table $table */
        $table = $this->database()->table($tableName);
        $schema = $table->getSchema();
        $schema->declareDropped();
        $schema->save();
    }
}
