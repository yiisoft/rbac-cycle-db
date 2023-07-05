<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Sqlite;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\FileConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Yiisoft\Rbac\Cycle\Tests\Base\Logger;

trait SqliteTrait
{
    protected function makeDatabase(): DatabaseInterface
    {
        $dbPath = __DIR__ . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'test.db';
        $dbConfig = new DatabaseConfig(
            [
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => 'sqlite'],
                ],
                'connections' => [
                    'sqlite' => new SQLiteDriverConfig(new FileConnectionConfig($dbPath)),
                ],
            ]
        );
        $dbManager = new DatabaseManager($dbConfig);
        $dbManager->database()->execute('PRAGMA foreign_keys = ON;');
        // Uncomment to dump schema changes
        $dbManager->setLogger(new Logger());

        return $dbManager->database();
    }
}
