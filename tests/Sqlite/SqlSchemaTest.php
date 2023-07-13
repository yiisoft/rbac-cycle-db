<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Sqlite;

class SqlSchemaTest extends \Yiisoft\Rbac\Cycle\Tests\Base\SqlSchemaTest
{
    use DatabaseTrait;
    use SchemaTrait;

    protected static string $driverName = 'sqlite';
}
