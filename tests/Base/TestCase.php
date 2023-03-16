<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\DatabaseManager;
use Cycle\Database\Schema\AbstractTable;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Yiisoft\Rbac\Cycle\Command\RbacCycleInit;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private ?DatabaseManager $databaseManager = null;

    protected const ITEMS_TABLE = 'auth_item';
    protected const ASSIGNMENTS_TABLE = 'auth_assignment';
    protected const ITEMS_CHILDREN_TABLE = 'auth_item_child';
    private const TABLES = [self::ITEMS_TABLE, self::ASSIGNMENTS_TABLE, self::ITEMS_CHILDREN_TABLE];

    protected function getDbal(): DatabaseManager
    {
        if ($this->databaseManager === null) {
            $this->databaseManager = $this->createDbManager();
        }

        return $this->databaseManager;
    }

    protected function setUp(): void
    {
        $this->createDbTables();
        $this->populateDb();
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $name) {
            $table = $this->getDbal()->database()->table($name);
            /** @var AbstractTable $schema */
            $schema = $table->getSchema();
            $schema->declareDropped();
            $schema->save();
        }
    }

    protected function createDbTables(): void
    {
        $app = $this->createApplication();
        $app->find('rbac/cycle/init')->run(new ArrayInput([]), new NullOutput());
    }

    protected function createApplication(string|null $itemsChildrenTable = self::ITEMS_CHILDREN_TABLE): Application
    {
        $app = new Application();
        $command = new RbacCycleInit(
            itemsTable: self::ITEMS_TABLE,
            assignmentsTable: self::ASSIGNMENTS_TABLE,
            database: $this->getDbal()->database(),
            itemsChildrenTable: $itemsChildrenTable,
        );
        $app->add($command);

        return $app;
    }

    abstract protected function createDbManager(): DatabaseManager;

    abstract protected function populateDb(): void;
}
