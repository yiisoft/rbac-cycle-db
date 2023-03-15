<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Pgsql;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\Postgres\DsnConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\DatabaseManager;

trait PgsqlTrait
{
    protected function createDbManager(): DatabaseManager
    {
        $dbConfig = new DatabaseConfig(
            [
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => 'pgsql'],
                ],
                'connections' => [
                    'pgsql' => new PostgresDriverConfig(new DsnConnectionConfig(
                        'mysql:host=127.0.0.1;port=5432;dbname=yiitest',
                        'root',
                        'root',
                    )),
                ],
            ]
        );

        return new DatabaseManager($dbConfig);
    }
}
