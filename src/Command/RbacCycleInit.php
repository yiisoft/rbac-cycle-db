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
    private DatabaseProviderInterface $dbal;
    /**
     * @psalm-var array{itemsTable: non-empty-string, assignmentsTable: non-empty-string, itemsChildrenTable?: non-empty-string}
     */
    private array $config;

    public function __construct(array $config, DatabaseProviderInterface $dbal)
    {
        $this->dbal = $dbal;
        $this->config = $config;
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
        /** @var non-empty-string $itemsChildrenTable */
        $itemsChildrenTable = $this->config['itemsChildrenTable'] ?? $this->config['itemsTable'] . '_child';
        $reCreate = $input->getOption('force') !== false;
        /** @var Table $table */
        if ($reCreate && $this->dbal->database()->hasTable($itemsChildrenTable) === true) {
            $this->dropTable($itemsChildrenTable);
        }
        if ($reCreate && $this->dbal->database()->hasTable($this->config['assignmentsTable']) === true) {
            $this->dropTable($this->config['assignmentsTable']);
        }
        if ($reCreate && $this->dbal->database()->hasTable($this->config['itemsTable']) === true) {
            $this->dropTable($this->config['itemsTable']);
        }
        if ($this->dbal->database()->hasTable($this->config['itemsTable']) === false) {
            $output->writeln('<fg=blue>Creating `' . $this->config['itemsTable'] . '` table...</>');
            $this->createItemsTable();
            $output->writeln('<bg=green>Table `' . $this->config['itemsTable'] . '` created successfully</>');
        }

        if ($this->dbal->database()->hasTable($itemsChildrenTable) === false) {
            $output->writeln('<fg=blue>Creating `' . $itemsChildrenTable . '` table...</>');
            $this->createItemsChildrenTable($itemsChildrenTable);
            $output->writeln('<bg=green>Table `' . $itemsChildrenTable . '` created successfully</>');
        }

        if ($this->dbal->database()->hasTable($this->config['assignmentsTable']) === false) {
            $output->writeln('<fg=blue>Creating `' . $this->config['assignmentsTable'] . '` table...</>');
            $this->createAssignmentsTable();
            $output->writeln('<bg=green>Table `' . $this->config['assignmentsTable'] . '` created successfully</>');
        }
        $output->writeln('<fg=green>DONE</>');
        return 0;
    }

    private function createItemsTable(): void
    {
        /** @var Table $table */
        $table = $this->dbal->database()->table($this->config['itemsTable']);
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
     * @param non-empty-string $itemsChildrenTable
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
            ->references($this->config['itemsTable'], ['name'])
            ->onDelete(ForeignKeyInterface::CASCADE)
            ->onUpdate(ForeignKeyInterface::CASCADE);

        $schema->foreignKey(['child'])
            ->references($this->config['itemsTable'], ['name'])
            ->onDelete(ForeignKeyInterface::CASCADE)
            ->onUpdate(ForeignKeyInterface::CASCADE);

        $schema->save();
    }

    private function createAssignmentsTable(): void
    {
        /** @var Table $table */
        $table = $this->dbal->database()->table($this->config['assignmentsTable']);
        $schema = $table->getSchema();

        $schema->string('itemName', 128)->nullable(false);
        $schema->string('userId', 128)->nullable(false);
        $schema->setPrimaryKeys(['itemName', 'userId']);
        $schema->integer('createdAt')->nullable(false);
        $schema->index(['itemName', 'userId']);

        $schema->foreignKey(['itemName'])
            ->references($this->config['itemsTable'], ['name'])
            ->onUpdate(ForeignKeyInterface::CASCADE)
            ->onDelete(ForeignKeyInterface::CASCADE);

        $schema->save();
    }

    /**
     * @param non-empty-string $tableName
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
