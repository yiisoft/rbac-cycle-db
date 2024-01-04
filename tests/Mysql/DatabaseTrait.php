<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Mysql;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\MySQL\DsnConnectionConfig;
use Cycle\Database\Config\MySQLDriverConfig;
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
                    'default' => ['connection' => 'mysql'],
                ],
                'connections' => [
                    'mysql' => new MySQLDriverConfig(new DsnConnectionConfig(
                        'mysql:host=127.0.0.1;port=3306;dbname=yiitest',
                        'root',
                        '',
                    )),
                ],
            ],
        );
        $dbManager = new DatabaseManager($dbConfig);
        $dbManager->database()->execute('SET GLOBAL max_connections = 5000;');

        $logger = new Logger();
        $dbManager->setLogger($logger);
        $this->setLogger($logger);

        return $dbManager;
    }

    protected function checkItemsChildrenTable(): void
    {
        parent::checkItemsChildrenTable();

        $this->checkItemsChildrenTableForeignKeys();
    }
}
