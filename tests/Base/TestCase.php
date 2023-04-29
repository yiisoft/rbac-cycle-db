<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Schema\AbstractTable;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Yiisoft\Rbac\Cycle\Command\RbacCycleInit;
use Yiisoft\Rbac\Cycle\SchemaManager;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private ?DatabaseInterface $database = null;

    protected const ITEMS_TABLE = 'auth_item';
    protected const ASSIGNMENTS_TABLE = 'auth_assignment';
    protected const ITEMS_CHILDREN_TABLE = 'auth_item_child';
    private const TABLES_FOR_DROPPING = [self::ITEMS_CHILDREN_TABLE, self::ASSIGNMENTS_TABLE, self::ITEMS_TABLE];

    protected function getDatabase(): DatabaseInterface
    {
        if ($this->database === null) {
            $this->database = $this->makeDatabase();
        }

        return $this->database;
    }

    protected function setUp(): void
    {
        $this->createDatabaseTables();
        $this->populateDatabase();
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES_FOR_DROPPING as $name) {
            $table = $this->getDatabase()->table($name);
            /** @var AbstractTable $schema */
            $schema = $table->getSchema();
            $schema->declareDropped();
            $schema->save();
        }
    }

    protected function createDatabaseTables(): void
    {
        $app = $this->createApplication();
        $app->find('rbac/cycle/init')->run(new ArrayInput([]), new NullOutput());
    }

    protected function createApplication(string|null $itemsChildrenTable = self::ITEMS_CHILDREN_TABLE): Application
    {
        $app = new Application();
        $schemaManager = new SchemaManager(
            itemsTable: self::ITEMS_TABLE,
            assignmentsTable: self::ASSIGNMENTS_TABLE,
            database: $this->getDatabase(),
            itemsChildrenTable: $itemsChildrenTable,
        );
        $command = new RbacCycleInit($schemaManager);
        $app->add($command);

        return $app;
    }

    abstract protected function makeDatabase(): DatabaseInterface;

    abstract protected function populateDatabase(): void;
}
