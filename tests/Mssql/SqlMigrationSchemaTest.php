<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Mssql;

class SqlMigrationSchemaTest extends \Yiisoft\Rbac\Cycle\Tests\Base\SqlMigrationSchemaTest
{
    use DatabaseTrait;

    protected static string $driverName = 'mssql';
}
