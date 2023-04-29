<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\ForeignKeyInterface;
use Cycle\Database\Table;
use InvalidArgumentException;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\SchemaManagerInterface;
use Yiisoft\Rbac\SchemaManagerTrait;

/**
 * Command for creating RBAC related database tables using Cycle ORM.
 */
final class SchemaManager implements SchemaManagerInterface
{
    use SchemaManagerTrait;

    /**
     * @param string $itemsTable A name of the table for storing RBAC items (roles and permissions).
     * @param string $assignmentsTable A name of the table for storing RBAC assignments.
     * @param DatabaseInterface $database Cycle database instance.
     * @param string|null $itemsChildrenTable A name of the table for storing relations between RBAC items. When set to
     * `null`, it will be automatically generated using {@see $itemsTable}.
     *
     * @throws InvalidArgumentException When a table name is set to the empty string.
     */
    public function __construct(
        string $itemsTable,
        string $assignmentsTable,
        private DatabaseInterface $database,
        string|null $itemsChildrenTable = null,
    ) {
        $this->initTables($itemsTable, $assignmentsTable, $itemsChildrenTable);
    }

    /**
     * Creates table for storing RBAC items (roles and permissions).
     *
     * @see $itemsTable
     */
    public function createItemsTable(): void
    {
        /** @var Table $table */
        $table = $this->database->table($this->itemsTable);
        $schema = $table->getSchema();

        $schema->string('name', 128)->nullable(false);
        $schema->enum('type', [Item::TYPE_ROLE, Item::TYPE_PERMISSION])->nullable(false);
        $schema->string('description', 191);
        $schema->string('ruleName', 64);
        $schema->integer('createdAt')->nullable(false);
        $schema->integer('updatedAt')->nullable(false);
        $schema->index(['type']);
        $schema->setPrimaryKeys(['name']);

        $schema->save();
    }

    /**
     * Creates table for storing relations between RBAC items.
     *
     * @see $itemsChildrenTable
     */
    public function createItemsChildrenTable(): void
    {
        /** @var Table $table */
        $table = $this->database->table($this->itemsChildrenTable);
        $schema = $table->getSchema();

        $schema->string('parent', 128)->nullable(false);
        $schema->string('child', 128)->nullable(false);
        $schema->setPrimaryKeys(['parent', 'child']);

        $schema
            ->foreignKey(['parent'])
            ->references($this->itemsTable, ['name']);

        $schema
            ->foreignKey(['child'])
            ->references($this->itemsTable, ['name']);

        $schema->save();
    }

    /**
     * Creates table for storing RBAC assignments.
     *
     * @see $assignmentsTable
     */
    public function createAssignmentsTable(): void
    {
        /** @var Table $table */
        $table = $this->database->table($this->assignmentsTable);
        $schema = $table->getSchema();

        $schema->string('itemName', 128)->nullable(false);
        $schema->string('userId', 128)->nullable(false);
        $schema->setPrimaryKeys(['itemName', 'userId']);
        $schema->integer('createdAt')->nullable(false);

        $schema
            ->foreignKey(['itemName'])
            ->references($this->itemsTable, ['name'])
            ->onUpdate(ForeignKeyInterface::CASCADE)
            ->onDelete(ForeignKeyInterface::CASCADE);

        $schema->save();
    }

    public function hasTable(string $tableName): bool
    {
        return $this->database->hasTable($tableName) === true;
    }

    public function dropTable(string $tableName): void
    {
        /** @var Table $table */
        $table = $this->database->table($tableName);
        $schema = $table->getSchema();
        $schema->declareDropped();
        $schema->save();
    }
}
