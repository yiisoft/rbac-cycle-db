<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Pgsql;

class SqlSchemaTest extends \Yiisoft\Rbac\Cycle\Tests\Base\SqlSchemaTest
{
    use DatabaseTrait;

    protected static string $driverName = 'pgsql';
}
