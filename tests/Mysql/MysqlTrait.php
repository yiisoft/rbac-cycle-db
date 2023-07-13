<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Mysql;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\MySQL\DsnConnectionConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Yiisoft\Rbac\Cycle\Tests\Base\Logger;

trait MysqlTrait
{
    protected function makeDatabase(): DatabaseInterface
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
        // Uncomment to dump schema changes
        // $dbManager->setLogger(new Logger());

        return $dbManager->database();
    }

    protected function checkAssignmentsTable(): void
    {
        parent::checkAssignmentsTable();

        $this->checkAssignmentsTableForeignKeys();
    }

    protected function checkItemsChildrenTable(): void
    {
        parent::checkItemsChildrenTable();

        $this->checkItemsChildrenTableForeignKeys();
    }
}
