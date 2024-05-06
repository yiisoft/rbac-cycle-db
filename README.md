<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://github.com/yiisoft.png" height="100px">
    </a>
    <h1 align="center">Yii RBAC Cycle Database</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/rbac-cycle-db/v/stable.png)](https://packagist.org/packages/yiisoft/rbac-cycle-db)
[![Total Downloads](https://poser.pugx.org/yiisoft/rbac-cycle-db/downloads.png)](https://packagist.org/packages/yiisoft/rbac-cycle-db)
[![Build status](https://github.com/yiisoft/rbac-cycle-db/workflows/build/badge.svg)](https://github.com/yiisoft/rbac-cycle-db/actions?query=workflow%3Abuild)
[![codecov](https://codecov.io/gh/yiisoft/rbac-cycle-db/graph/badge.svg?token=OAABFCCC7A)](https://codecov.io/gh/yiisoft/rbac-cycle-db)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Frbac-cycle-db%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/rbac-cycle-db/master)
[![static analysis](https://github.com/yiisoft/rbac-cycle-db/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/rbac-cycle-db/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/rbac-cycle-db/coverage.svg)](https://shepherd.dev/github/yiisoft/rbac-cycle-db)

The package provides [Cycle Database](https://github.com/cycle/database) storage for [Yii RBAC](https://github.com/yiisoft/rbac).

Detailed build statuses:

| RDBMS | Status |
|-------|--------|
| SQLite | [![SQLite status](https://github.com/yiisoft/rbac-cycle-db/workflows/sqlite/badge.svg)](https://github.com/yiisoft/rbac-cycle-db/actions?query=workflow%3Asqlite) |
| MySQL | [![MYSQL status](https://github.com/yiisoft/rbac-cycle-db/workflows/mysql/badge.svg)](https://github.com/yiisoft/rbac-cycle-db/actions?query=workflow%3Amysql) |
| PostgreSQL | [![MYSQL status](https://github.com/yiisoft/rbac-cycle-db/workflows/pgsql/badge.svg)](https://github.com/yiisoft/rbac-cycle-db/actions?query=workflow%3Apgsql) |
| Microsoft SQL Server | [![MYSQL status](https://github.com/yiisoft/rbac-cycle-db/workflows/mssql/badge.svg)](https://github.com/yiisoft/rbac-cycle-db/actions?query=workflow%3Amssql) |

## Requirements

- PHP 8.1 or higher.
- In the case of using with SQLite, a minimal required version is 3.8.3.
- In the case of using with SQL Server, a minimal required version of PDO is 5.11.1. 

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/rbac-cycle-db
```

See [yiisoft/rbac](https://github.com/yiisoft/rbac) for RBAC package installation instructions.

## General usage

### Configuring database connection

Configuration depends on a selected driver. Here is an example for PostgreSQL:

```php
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\Postgres\DsnConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\DatabaseManager;

$dbConfig = new DatabaseConfig(
    [
        'default' => 'default',
        'databases' => [
            'default' => ['connection' => 'pgsql'],
        ],
        'connections' => [
            'pgsql' => new PostgresDriverConfig(new DsnConnectionConfig(
                'pgsql:host=127.0.0.1;dbname=yiitest;port=5432',
                'user',
                'password',
            )),
        ],
    ]
);
$databaseManager = new DatabaseManager($dbConfig);
$database = $databaseManager->database();
```

More comprehensive examples can be found at
[Cycle Database docs](https://cycle-orm.dev/docs/database-configuration#declare-connection).

### Working with migrations

This package uses [Cycle Migrations](https://github.com/cycle/migrations) for managing database tables required for 
storages. There are three tables in total (`yii_rbac_` prefix is used).

Items storage:

- `yii_rbac_item`.
- `yii_rbac_item_child`.

Assignments storage:

- `yii_rbac_assignment`.

#### Configuring migrator and capsule

```php
use Cycle\Database\DatabaseManager;
use Cycle\Migrations\Capsule;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;

$migrationsSubfolders = ['items', 'assignments'];
$directories = array_map(
    static fn (): string => dirname(__DIR__. 2),  "/vendor/yiisoft/rbac-cycle-db/migrations/$subfolder",
    $migrationsSubfolders, 
);
$config = new MigrationConfig([
    'directory' => $directories[0],
    // "vendorDirectories" are specified because the "directory" option doesn't support multiple directories. In the
    // end, it makes no difference because they all will be merged into a single array.
    'vendorDirectories' => $directories[1] ?? [],
    'table' => 'cycle_migration',
    'safe' => true,
]);
/** @var DatabaseManager $databaseManager */
$migrator = new Migrator($config, $databaseManager, new FileRepository($config));
$migrator->configure();

$capsule = new Capsule($databaseManager->database());
```

For configuring `$databaseManager`, see [previous section](#configuring-database-connection).

Because item and assignment storages are completely independent, migrations are separated as well to prevent 
the creation of unused tables. So, for example, if you only want to use assignment storage, adjust 
`$migrationsSubfolders` variable like this:

```php
$migrationsSubfolders = ['assignments'];
```

#### Applying migrations

```php
use Cycle\Migrations\Capsule;
use Cycle\Migrations\Migrator;

/**
 * @var Migrator $migrator
 * @var Capsule $capsule 
 */
while ($migrator->run($capsule) !== null) {
    echo "Migration {$migration->getState()->getName()} applied successfully.\n";
}
```

#### Reverting migrations

```php
use Cycle\Migrations\Capsule;
use Cycle\Migrations\Migrator;

/**
 * @var Migrator $migrator
 * @var Capsule $capsule 
 */
while ($migrator->rollback($capsule) !== null) {
    echo "Migration {$migration->getState()->getName()} reverted successfully.\n";
}
```

### Using storages

The storages are not intended to be used directly. Instead, use them with `Manager` from
[Yii RBAC](https://github.com/yiisoft/rbac) package:

```php
use Cycle\Database\DatabaseInterface;
use Yiisoft\Rbac\Cycle\AssignmentsStorage;
use Yiisoft\Rbac\Cycle\ItemsStorage;
use Yiisoft\Rbac\Cycle\TransactionalManagerDecorator;
use Yiisoft\Rbac\Manager;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\RuleFactoryInterface;

/** @var DatabaseInterface $database */
$itemsStorage = new ItemsStorage($database);
$assignmentsStorage = new AssignmentsStorage($database);
/** @var RuleFactoryInterface $rulesContainer */
$manager = new TransactionalManagerDecorator(
    new Manager(
        itemsStorage: $itemsStorage, 
        assignmentsStorage: $assignmentsStorage,
        // Requires https://github.com/yiisoft/rbac-rules-container or another compatible factory.
        ruleFactory: $rulesContainer,
    ),
);
$manager->addPermission(new Permission('posts.create'));
```

> Note wrapping manager with decorator—it additionally provides database transactions to guarantee data integrity.

> Note that it's not necessary to use both DB storages. Combining different implementations is possible. A quite popular 
> case is to manage items via [PHP file](https://github.com/yiisoft/rbac-php) while store assignments in a database.

More examples can be found in [Yii RBAC](https://github.com/yiisoft/rbac) documentation.

### Syncing storages manually

The storages stay synced thanks to manager, but there can be situations where you need to sync them manually. One of 
them is using combination with PHP file based storage and 
[editing it manually](https://github.com/yiisoft/rbac-php/?tab=readme-ov-file#file-structure).

Let's say PHP file is used for items, while database - for assignments, and some items were deleted:

```diff
return [
    [
        'name' => 'posts.admin',        
        'type' => 'role',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
        'children' => [
            'posts.redactor',
            'posts.delete',
            'posts.update.all',
        ],
    ],
-   [
-       'name' => 'posts.redactor',
-       'type' => 'role',        
-       'created_at' => 1683707079,
-       'updated_at' => 1683707079,
-       'children' => [
-           'posts.viewer',
-           'posts.create',
-           'posts.update',
-       ],
-   ],
    [
        'name' => 'posts.viewer',
        'type' => 'role',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
        'children' => [
            'posts.view',
        ],
    ],
    [
        'name' => 'posts.view',
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
    [
        'name' => 'posts.create',
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
-   [
-       'name' => 'posts.update',
-       'rule_name' => 'is_author',
-       'type' => 'permission',
-       'created_at' => 1683707079,
-       'updated_at' => 1683707079,
-   ],
    [
        'name' => 'posts.delete',        
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
    [
        'name' => 'posts.update.all',
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
];
```

Then related entries in other storage needs to be deleted as well. This can be done within a migration:

```php
use Cycle\Migrations\Migration;

final class DeletePostUpdateItems extends Migration
{
    private const TABLE_PREFIX = 'yii_rbac_';
    private const ASSIGNMENTS_TABLE = self::TABLE_PREFIX . 'assignment';

    public function up()
    {
        $this
            ->database()
            ->delete()
            ->from(self::ASSIGNMENTS_TABLE)
            ->where('item_name', 'IN', ['posts.redactor', 'posts.update']);
    }

    public function down()
    {
    }
}
```

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place
for that. You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii RBAC Cycle Database is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
