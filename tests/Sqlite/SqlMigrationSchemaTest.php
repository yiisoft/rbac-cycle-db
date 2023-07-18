<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Sqlite;

class SqlMigrationSchemaTest extends \Yiisoft\Rbac\Cycle\Tests\Base\SqlMigrationSchemaTest
{
    use DatabaseTrait;
    use SchemaTrait;

    protected static string $driverName = 'sqlite';
}
