<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Schema\AbstractTable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Rbac\Item;
use Yiisoft\Yii\Console\ExitCode;

final class RbacCycleInit extends Command
{
    protected static $defaultName = 'rbac/cycle/init';
    private DatabaseProviderInterface $dbal;
    /**
     * @psalm-var array{itemsTable: string, assignmentsTable: string, itemsChildrenTable?: string, database?: string}
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
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->dbal->database($this->config['database'])->hasTable($this->config['itemsTable']) === false) {
            $output->writeln('<fg=blue>Creating `' . $this->config['itemsTable'] . '` table...</>');
            $this->createItemsTable();
            $output->writeln('<bg=green>Table `' . $this->config['itemsTable'] . '` created successfully</>');
        }

        $itemsChildrenTable = $this->config['itemsChildrenTable'] ?? $this->config['itemsTable'] . '_children';

        if ($this->dbal->database($this->config['database'])->hasTable($itemsChildrenTable) === false) {
            $output->writeln('<fg=blue>Creating `' . $itemsChildrenTable . '` table...</>');
            $this->createItemsChildrenTable($itemsChildrenTable);
            $output->writeln('<bg=green>Table `' . $itemsChildrenTable . '` created successfully</>');
        }

        if ($this->dbal->database($this->config['database'])->hasTable($this->config['assignmentsTable']) === false) {
            $output->writeln('<fg=blue>Creating `' . $this->config['assignmentsTable'] . '` table...</>');
            $this->createAssignmentsTable();
            $output->writeln('<bg=green>Table `' . $this->config['assignmentsTable'] . '` created successfully</>');
        }
        $output->writeln('<fg=green>DONE</>');
        return ExitCode::OK;
    }

    private function createItemsTable(): void
    {
        /** @var AbstractTable $schema */
        $schema = $this->dbal->database($this->config['database'])->table($this->config['itemsTable'])->getSchema();

        $schema->primary('name')->string(128);
        $schema->primary('type')->enum([Item::TYPE_ROLE, Item::TYPE_PERMISSION]);
        $schema->string('description', 191)->nullable();
        $schema->string('rule_name', 64)->nullable();
        $schema->integer('created_at');
        $schema->integer('updated_at');
        $schema->index(['name', 'type']);
    }

    private function createItemsChildrenTable(string $itemsChildrenTable): void
    {
        /** @var AbstractTable $schema */
        $schema = $this->dbal->database($this->config['database'])
            ->table($itemsChildrenTable)->getSchema();

        $schema->primary('parent')->string(128);
        $schema->primary('children')->string(128);
    }

    private function createAssignmentsTable(): void
    {
        /** @var AbstractTable $schema */
        $schema = $this->dbal->database($this->config['database'])
            ->table($this->config['assignmentsTable'])->getSchema();

        $schema->primary('item_name')->string(128);
        $schema->primary('user_id')->string(128);
        $schema->integer('created_at');
        $schema->index(['item_name', 'user_id']);
    }
}
