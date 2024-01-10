<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Mssql;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLServer\DsnConnectionConfig;
use Cycle\Database\Config\SQLServerDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;

trait DatabaseTrait
{
    protected function makeDatabaseManager(): DatabaseProviderInterface
    {
        $dbConfig = new DatabaseConfig(
            [
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => 'mssql'],
                ],
                'connections' => [
                    'mssql' => new SQLServerDriverConfig(new DsnConnectionConfig(
                        'sqlsrv:Server=127.0.0.1,1433;Database=yiitest',
                        'SA',
                        'YourStrong!Passw0rd',
                    )),
                ],
            ]
        );

        return new DatabaseManager($dbConfig);
    }

    protected function checkItemsChildrenTable(): void
    {
        parent::checkItemsChildrenTable();

        $this->checkItemsChildrenTableForeignKeys();
    }
}
