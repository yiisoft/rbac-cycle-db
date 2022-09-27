<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\MySQL\DsnConnectionConfig as MyQSLDsnConnectionConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Config\Postgres\DsnConnectionConfig as PostgresDsnConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
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

    private array $tables = [
        'itemsChildTable' => 'auth_item_child',
        'assignmentsTable' => 'auth_assignment',
        'itemsTable' => 'auth_item',
    ];

    protected function getDbal(): DatabaseManager
    {
        if ($this->databaseManager === null) {
            $this->createConnection();
        }
        return $this->databaseManager;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createDbTables();
        $this->populateDb();
    }

    protected function tearDown(): void
    {
        foreach ($this->tables as $name) {
            $table = $this->getDbal()->database()->table($name);
            /** @var AbstractTable $schema */
            $schema = $table->getSchema();
            $schema->declareDropped();
            $schema->save();
        }
    }

    protected function clear()
    {
        foreach ($this->tables as $name) {
            $this->getDbal()->database()->delete($name)->run();
        }
    }

    protected function createDbTables(): void
    {
        $app = new Application();
        $app->add(
            new RbacCycleInit(
                $this->tables,
                $this->getDbal()
            )
        );
        $app->find('rbac/cycle/init')->run(new ArrayInput([]), new NullOutput());
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
                    // 'mysql' => new MySQLDriverConfig(new MyQSLDsnConnectionConfig(
                    //     'mysql:host=127.0.0.1;port=3306;dbname=test',
                    //     'root',
                    //     '123456',
                    // )),
                    // 'postgres' => new PostgresDriverConfig(new PostgresDsnConnectionConfig(
                    //     'pgsql:host=127.0.0.1;port=5432;dbname=test',
                    //     'postgres',
                    //     '123456',
                    // )),
                ],
            ]
        );
        $this->databaseManager = new DatabaseManager($dbConfig);
    }

    abstract protected function populateDb(): void;
}
