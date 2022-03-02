<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Command;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\ForeignKeyInterface;
use Cycle\Database\Schema\AbstractTable;
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
     * @psalm-var array{itemsTable: string, assignmentsTable: string, itemsChildrenTable?: string}
     */
    private array $config;

    public function __construct(array $config, DatabaseProviderInterface $dbal)
    {
        $this->dbal = $dbal;
        $this->config = $config;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Create RBAC schemas')
            ->setHelp('This command creates schemas for RBAC using Cycle DBAL')
            ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Force re-create schemas if exists', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $itemsChildrenTable = $this->config['itemsChildrenTable'] ?? $this->config['itemsTable'] . '_child';
        $force = $input->getOption('force');
        if ($force === false) {
            $reCreate = false;
        } else {
            $reCreate = true;
        }
        /** @var AbstractTable $schema */
        if ($reCreate && $this->dbal->database()->hasTable($itemsChildrenTable) === true) {
            $schema = $this->dbal->database()->table($itemsChildrenTable)->getSchema();
            $schema->declareDropped();
            $schema->save();
        }
        if ($reCreate && $this->dbal->database()->hasTable($this->config['assignmentsTable']) === true) {
            $schema = $this->dbal->database()->table($this->config['assignmentsTable'])->getSchema();
            $schema->declareDropped();
            $schema->save();
        }
        if ($reCreate && $this->dbal->database()->hasTable($this->config['itemsTable']) === true) {
            $schema = $this->dbal->database()->table($this->config['itemsTable'])->getSchema();
            $schema->declareDropped();
            $schema->save();
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
        /** @var AbstractTable $schema */
        $schema = $this->dbal->database()->table($this->config['itemsTable'])->getSchema();

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

    private function createItemsChildrenTable(string $itemsChildrenTable): void
    {
        /** @var AbstractTable $schema */
        $schema = $this->dbal->database()
            ->table($itemsChildrenTable)
            ->getSchema();

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
        /** @var AbstractTable $schema */
        $schema = $this->dbal->database()
            ->table($this->config['assignmentsTable'])
            ->getSchema();

        $schema->primary('itemName')->string(128);
        $schema->primary('userId')->string(128);
        $schema->integer('createdAt')->nullable(false);
        $schema->index(['itemName', 'userId']);

        $schema->foreignKey(['itemName'])
            ->references($this->config['itemsTable'], ['name'])
            ->onUpdate(ForeignKeyInterface::CASCADE)
            ->onDelete(ForeignKeyInterface::CASCADE);

        $schema->save();
    }
}
