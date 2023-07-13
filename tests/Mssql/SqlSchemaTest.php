<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Mssql;

class SqlSchemaTest extends \Yiisoft\Rbac\Cycle\Tests\Base\SqlSchemaTest
{
    use DatabaseTrait;

    protected static string $driverName = 'mssql';
}
