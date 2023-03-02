<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Command;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\ForeignKeyInterface;
use Cycle\Database\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Rbac\Item;

final class RbacCycleInit extends Command
{
    protected static $defaultName = 'rbac/cycle/init';

    public function __construct(
        private string $itemsTable,
        private string $assignmentsTable,
        private string $itemsChildrenTable,
        private DatabaseProviderInterface $dbal,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create RBAC schemas')
            ->setHelp('This command creates schemas for RBAC using Cycle DBAL')
            ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Force re-create schemas if exists', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $itemsChildrenTable = $this->itemsChildrenTable ?? $this->itemsTable . '_child';
        $reCreate = $input->getOption('force') !== false;
        if ($reCreate && $this->dbal->database()->hasTable($itemsChildrenTable) === true) {
            $this->dropTable($itemsChildrenTable);
        }
        if ($reCreate && $this->dbal->database()->hasTable($this->assignmentsTable) === true) {
            $this->dropTable($this->assignmentsTable);
        }
        if ($reCreate && $this->dbal->database()->hasTable($this->itemsTable) === true) {
            $this->dropTable($this->itemsTable);
        }
        if ($this->dbal->database()->hasTable($this->itemsTable) === false) {
            $output->writeln('<fg=blue>Creating `' . $this->itemsTable . '` table...</>');
            $this->createItemsTable();
            $output->writeln('<bg=green>Table `' . $this->itemsTable . '` created successfully</>');
        }

        if ($this->dbal->database()->hasTable($itemsChildrenTable) === false) {
            $output->writeln('<fg=blue>Creating `' . $itemsChildrenTable . '` table...</>');
            $this->createItemsChildrenTable($itemsChildrenTable);
            $output->writeln('<bg=green>Table `' . $itemsChildrenTable . '` created successfully</>');
        }

        if ($this->dbal->database()->hasTable($this->assignmentsTable) === false) {
            $output->writeln('<fg=blue>Creating `' . $this->assignmentsTable . '` table...</>');
            $this->createAssignmentsTable();
            $output->writeln('<bg=green>Table `' . $this->assignmentsTable . '` created successfully</>');
        }
        $output->writeln('<fg=green>DONE</>');
        return 0;
    }

    private function createItemsTable(): void
    {
        /** @var Table $table */
        $table = $this->dbal->database()->table($this->itemsTable);
        $schema = $table->getSchema();

        $schema->string('name', 128);
        $schema->enum('type', [Item::TYPE_ROLE, Item::TYPE_PERMISSION])->nullable(false);
        $schema->string('description', 191)->nullable();
        $schema->string('ruleName', 64)->nullable();
        $schema->integer('createdAt')->nullable(false);
        $schema->integer('updatedAt')->nullable(false);
        $schema->index(['type']);
        $schema->setPrimaryKeys(['name']);

        $schema->save();
    }

    /**
     * @psalm-param non-empty-string $itemsChildrenTable
     */
    private function createItemsChildrenTable(string $itemsChildrenTable): void
    {
        /** @var Table $table */
        $table = $this->dbal->database()->table($itemsChildrenTable);
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

    private function createAssignmentsTable(): void
    {
        /** @var Table $table */
        $table = $this->dbal->database()->table($this->assignmentsTable);
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
     * @psalm-param non-empty-string $tableName
     */
    private function dropTable(string $tableName): void
    {
        /** @var Table $table */
        $table = $this->dbal->database()->table($tableName);
        $schema = $table->getSchema();
        $schema->declareDropped();
        $schema->save();
    }
}
