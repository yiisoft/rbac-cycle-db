<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\FileConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Schema\AbstractTable;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Yiisoft\Rbac\Cycle\Command\RbacCycleInit;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private ?DatabaseManager $databaseManager = null;

    public function getDbal(): DatabaseManager
    {
        if ($this->databaseManager === null) {
            $this->createConnection();
        }
        return $this->databaseManager;
    }

    private function createConnection(): void
    {
        $dbConfig = new DatabaseConfig(
            [
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => 'sqlite'],
                ],
                'connections' => [
                    'sqlite' => new SQLiteDriverConfig(new FileConnectionConfig(__DIR__ . '/runtime/test.db')),
                ],
            ]
        );
        $this->databaseManager = new DatabaseManager($dbConfig);
    }

    protected function tearDown(): void
    {
        foreach ($this->getDbal()->database()->getTables() as $table) {
            /** @var AbstractTable $schema */
            $schema = $table->getSchema();
            $schema->declareDropped();
            $schema->save();
        }
    }

    protected function createDbTables(): void
    {
        $app = new Application();
        $app->add(
            new RbacCycleInit(
                [
                    'itemsTable' => 'auth_item',
                    'itemsChildTable' => 'auth_item_child',
                    'assignmentsTable' => 'auth_assignment',
                ],
                $this->getDbal()
            )
        );
        $app->find('rbac/cycle/init')->run(new ArrayInput([]), new NullOutput());
    }

    abstract protected function populateDb(): void;
}
