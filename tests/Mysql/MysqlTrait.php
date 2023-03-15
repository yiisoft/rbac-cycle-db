<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Mysql;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\MySQL\DsnConnectionConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\DatabaseManager;

trait MysqlTrait
{
    protected function createDbManager(): DatabaseManager
    {
        $dbConfig = new DatabaseConfig(
            [
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => 'sqlite'],
                ],
                'connections' => [
                    'mysql' => new MySQLDriverConfig(new DsnConnectionConfig(
                        'mysql:host=127.0.0.1;port=3306;dbname=yiitest',
                        'root',
                        '',
                    )),
                ],
            ]
        );

        return new DatabaseManager($dbConfig);
    }
}
