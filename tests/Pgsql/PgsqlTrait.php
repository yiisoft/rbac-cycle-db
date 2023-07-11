<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Pgsql;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\Postgres\DsnConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Yiisoft\Rbac\Cycle\Tests\Base\Logger;

trait PgsqlTrait
{
    protected function makeDatabase(): DatabaseInterface
    {
        $dbConfig = new DatabaseConfig(
            [
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => 'pgsql'],
                ],
                'connections' => [
                    'pgsql' => new PostgresDriverConfig(new DsnConnectionConfig(
                        'pgsql:host=127.0.0.1;dbname=yiitest;port=5432',
                        'root',
                        'root',
                    )),
                ],
            ]
        );
        $dbManager = new DatabaseManager($dbConfig);
        // Uncomment to dump schema changes
        // $dbManager->setLogger(new Logger());

        return $dbManager->database();
    }
}
