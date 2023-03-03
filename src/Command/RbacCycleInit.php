<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Command;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\ForeignKeyInterface;
use Cycle\Database\Table;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Rbac\Item;

final class RbacCycleInit extends Command
{
    protected static $defaultName = 'rbac/cycle/init';

    /**
     * @psalm-var non-empty-string
     */
    private string $itemsTable;
    /**
     * @psalm-var non-empty-string
     */
    private string $assignmentsTable;
    /**
     * @psalm-var non-empty-string
     */
    private string $itemsChildrenTable;

    public function __construct(
        string $itemsTable,
        string $assignmentsTable,
        private DatabaseProviderInterface $dbal,
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
            ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Force recreation of schemas if they exist', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force') === true;
        if ($force === true) {
            $this->dropTable($this->itemsChildrenTable, $output);
            $this->dropTable($this->assignmentsTable, $output);
            $this->dropTable($this->itemsTable, $output);
            $checkExistence = false;
        } else {
            $checkExistence = true;
        }

        $this->createTable($this->itemsTable, $output, checkExistence: $checkExistence);
        $this->createTable($this->itemsChildrenTable, $output, checkExistence: $checkExistence);
        $this->createTable($this->assignmentsTable, $output, checkExistence: $checkExistence);

        $output->writeln('<fg=green>DONE</>');

        return Command::SUCCESS;
    }

    private function createItemsTable(): void
    {
        /** @var Table $table */
        $table = $this->dbal->database()->table($this->itemsTable);
        $schema = $table->getSchema();

        $schema->string('name', 128);
        $schema->enum('type', [Item::TYPE_ROLE, Item::TYPE_PERMISSION])->nullable(false);
        $schema->string('description', 191);
        $schema->string('ruleName', 64);
        $schema->timestamp('createdAt')->nullable(false);
        $schema->timestamp('updatedAt')->nullable(false);
        $schema->index(['type']);
        $schema->setPrimaryKeys(['name']);

        $schema->save();
    }

    private function createItemsChildrenTable(): void
    {
        /** @var Table $table */
        $table = $this->dbal->database()->table($this->itemsChildrenTable);
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
        $schema->timestamp('createdAt')->nullable(false);

        $schema->foreignKey(['itemName'])
            ->references($this->itemsTable, ['name'])
            ->onUpdate(ForeignKeyInterface::CASCADE)
            ->onDelete(ForeignKeyInterface::CASCADE);

        $schema->save();
    }

    /**
     * @psalm-param non-empty-string $tableName
     */
    private function createTable(string $tableName, OutputInterface $output, bool $checkExistence = true): void
    {
        $output->writeln('<fg=blue>Creating `' . $tableName . '` table...</>');

        if ($checkExistence) {
            $output->writeln('<fg=blue>Checking existence of table `' . $tableName . '`...</>');

            if ($this->dbal->database()->hasTable($tableName) === true) {
                $output->writeln('<bg=yellow>Table `' . $tableName . '` already exists, skipped creating.</>');

                return;
            }
        }

        match ($tableName) {
            $this->itemsTable => $this->createItemsTable(),
            $this->assignmentsTable => $this->createAssignmentsTable(),
            $this->itemsChildrenTable => $this->createItemsChildrenTable(),
        };

        $output->writeln('<bg=green>Table `' . $tableName . '` successfully created.</>');
    }

    /**
     * @psalm-param non-empty-string $tableName
     */
    private function dropTable(string $tableName, OutputInterface $output): void
    {
        $output->writeln('<fg=blue>Dropping `' . $tableName . '` table...</>');

        if ($this->dbal->database()->hasTable($tableName) === false) {
            $output->writeln('<bg=yellow>Table `' . $tableName . '` doesn\'t exist, skipped dropping.</>');

            return;
        }

        /** @var Table $table */
        $table = $this->dbal->database()->table($tableName);
        $schema = $table->getSchema();
        $schema->declareDropped();
        $schema->save();

        $output->writeln('<bg=green>Table `' . $tableName . '` successfully dropped.</>');
    }
}
