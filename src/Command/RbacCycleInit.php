<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Command;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\ForeignKeyInterface;
use Cycle\Database\Table;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Rbac\Item;

/**
 * Command for creating RBAC related database tables using Cycle ORM.
 */
final class RbacCycleInit extends Command
{
    protected static $defaultName = 'rbac/cycle/init';

    /**
     * @var string A name of the table for storing RBAC items (roles and permissions).
     * @psalm-var non-empty-string
     */
    private string $itemsTable;
    /**
     * @var string A name of the table for storing RBAC assignments.
     * @psalm-var non-empty-string
     */
    private string $assignmentsTable;
    /**
     * @var string A name of the table for storing relations between RBAC items.
     * @psalm-var non-empty-string
     */
    private string $itemsChildrenTable;

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
        if ($itemsTable === '') {
            throw new InvalidArgumentException('Items table name can\'t be empty.');
        }

        $this->itemsTable = $itemsTable;

        if ($assignmentsTable === '') {
            throw new InvalidArgumentException('Assignments table name can\'t be empty.');
        }

        $this->assignmentsTable = $assignmentsTable;

        if ($itemsChildrenTable === '') {
            throw new InvalidArgumentException('Items children table name can\'t be empty.');
        }

        $this->itemsChildrenTable = $itemsChildrenTable ?? $this->itemsTable . '_child';

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create RBAC schemas')
            ->setHelp('This command creates schemas for RBAC using Cycle DBAL')
            ->addOption(name: 'force', shortcut: 'f', description: 'Force recreation of schemas if they exist');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');
        if ($force === true) {
            $this->dropTable($this->itemsChildrenTable, $output);
            $this->dropTable($this->assignmentsTable, $output);
            $this->dropTable($this->itemsTable, $output);
        }

        $this->createTable($this->itemsTable, $output);
        $this->createTable($this->itemsChildrenTable, $output);
        $this->createTable($this->assignmentsTable, $output);

        $output->writeln('<fg=green>DONE</>');

        return Command::SUCCESS;
    }

    /**
     * Creates table for storing RBAC items (roles and permissions).
     *
     * @see $itemsTable
     */
    private function createItemsTable(): void
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
    private function createItemsChildrenTable(): void
    {
        /** @var Table $table */
        $table = $this->database->table($this->itemsChildrenTable);
        $schema = $table->getSchema();

        $schema->string('parent', 128)->nullable(false);
        $schema->string('child', 128)->nullable(false);
        $schema->setPrimaryKeys(['parent', 'child']);

        $schema->foreignKey(['parent'])
            ->references($this->itemsTable, ['name'])
            ->onDelete(ForeignKeyInterface::CASCADE)
            ->onUpdate(ForeignKeyInterface::CASCADE);

        $schema->foreignKey(['child'])
            ->references($this->itemsTable, ['name'])
            ->onDelete(ForeignKeyInterface::CASCADE)
            ->onUpdate(ForeignKeyInterface::CASCADE);

        $schema->save();
    }

    /**
     * Creates table for storing RBAC assignments.
     *
     * @see $assignmentsTable
     */
    private function createAssignmentsTable(): void
    {
        /** @var Table $table */
        $table = $this->database->table($this->assignmentsTable);
        $schema = $table->getSchema();

        $schema->string('itemName', 128)->nullable(false);
        $schema->string('userId', 128)->nullable(false);
        $schema->setPrimaryKeys(['itemName', 'userId']);
        $schema->integer('createdAt')->nullable(false);

        $schema->foreignKey(['itemName'])
            ->references($this->itemsTable, ['name'])
            ->onUpdate(ForeignKeyInterface::CASCADE)
            ->onDelete(ForeignKeyInterface::CASCADE);

        $schema->save();
    }

    /**
     * Basic method for creating RBAC related table. When a table already exists, creation is skipped. Operations are
     * accompanied by explanations printed to console.
     *
     * @param string $tableName A name of created table.
     * @psalm-param non-empty-string $tableName
     *
     * @param OutputInterface $output Output for writing messages.
     */
    private function createTable(string $tableName, OutputInterface $output): void
    {
        $output->writeln("<fg=blue>Checking existence of `$tableName` table...</>");

        if ($this->database->hasTable($tableName) === true) {
            $output->writeln("<bg=yellow>`$tableName` table already exists. Skipped creating.</>");

            return;
        }

        $output->writeln("<fg=blue>`$tableName` table doesn't exist. Creating...</>");

        match ($tableName) {
            $this->itemsTable => $this->createItemsTable(),
            $this->assignmentsTable => $this->createAssignmentsTable(),
            $this->itemsChildrenTable => $this->createItemsChildrenTable(),
        };

        $output->writeln("<bg=green>`$tableName` table has been successfully created.</>");
    }

    /**
     * Basic method for dropping RBAC related table. When a table already exists, dropping is skipped. Operations are
     * accompanied by explanations printed to console.
     *
     * @param string $tableName A name of created table.
     * @psalm-param non-empty-string $tableName
     *
     * @param OutputInterface $output Output for writing messages.
     */
    private function dropTable(string $tableName, OutputInterface $output): void
    {
        $output->writeln("<fg=blue>Checking existence of `$tableName` table...</>");

        if ($this->database->hasTable($tableName) === false) {
            $output->writeln("<bg=yellow>`$tableName` table doesn't exist. Skipped dropping.</>");

            return;
        }

        $output->writeln("<fg=blue>`$tableName` table exists. Dropping...</>");

        /** @var Table $table */
        $table = $this->database->table($tableName);
        $schema = $table->getSchema();
        $schema->declareDropped();
        $schema->save();

        $output->writeln("<bg=green>`$tableName` table has been successfully dropped.</>");
    }
}
