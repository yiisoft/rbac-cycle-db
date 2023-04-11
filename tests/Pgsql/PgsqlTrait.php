<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Pgsql;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\Postgres\DsnConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;

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

        return (new DatabaseManager($dbConfig))->database();
    }
}
