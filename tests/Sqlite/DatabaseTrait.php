<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Sqlite;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use Yiisoft\Rbac\Cycle\Tests\Base\Logger;

trait DatabaseTrait
{
    protected function makeDatabaseManager(): DatabaseProviderInterface
    {
        $dbConfig = new DatabaseConfig(
            [
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => 'sqlite'],
                ],
                'connections' => [
                    'sqlite' => new SQLiteDriverConfig(new MemoryConnectionConfig()),
                ],
            ]
        );
        $dbManager = new DatabaseManager($dbConfig);
        $dbManager->database()->execute('PRAGMA foreign_keys = ON;');

        $logger = new Logger();
        $dbManager->setLogger($logger);
        $this->setLogger($logger);

        return $dbManager;
    }
}
