<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://github.com/yiisoft.png" height="100px">
    </a>
    <h1 align="center">Yii RBAC Cycle Database</h1>
    <br>
</p>

The package provides [Cycle Database](https://github.com/cycle/database) storage for [Yii RBAC](https://github.com/yiisoft/rbac).

[![Latest Stable Version](https://poser.pugx.org/yiisoft/rbac-cycle-db/v/stable.png)](https://packagist.org/packages/yiisoft/rbac-cycle-db)
[![Total Downloads](https://poser.pugx.org/yiisoft/rbac-cycle-db/downloads.png)](https://packagist.org/packages/yiisoft/rbac-cycle-db)
[![Build status](https://github.com/yiisoft/rbac-cycle-db/workflows/build/badge.svg)](https://github.com/yiisoft/rbac-cycle-db/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/rbac-cycle-db/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/rbac-cycle-db/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/rbac-cycle-db/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/rbac-cycle-db/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Frbac-cycle-db%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/rbac-cycle-db/master)
[![static analysis](https://github.com/yiisoft/rbac-cycle-db/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/rbac-cycle-db/actions?query=workflow%3A%22static+analysis%22)

## Requirements

- PHP 8.0 or higher.
- In case of using with SQLite, minimal required version is 3.8.3.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/rbac-cycle-db
```

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
$database = (new DatabaseManager($dbConfig))->database();
```

More comprehensive examples can be found at
[Cycle Database docs](https://cycle-orm.dev/docs/database-configuration#declare-connection).

### Working with schema

In order to keep less dependencies, this package doesn't provide any CLI for working with schema. There are multiple
options to choose from:

- Use migration tool like [Yii DB Migration](https://github.com/yiisoft/yii-db-migration). Migrations are dumped as
  plain SQL in `sql/migrations` folder.
- Without migrations, `DbSchemaManager` class can be used. An example of CLI command containing it can be found
  [here](examples/Command/RbacCycleInit.php).
- Use plain SQL that is actual at the moment of installing `rbac-db` package (located at the root of `sql` folder).

The structure of plain SQL files:

- `pgsql-up.sql` - apply the changes for PostgreSQL driver.
- `pgsql-down.sql` - revert the changes for PostgreSQL driver.

Plain SQL assumes using default names for all 3 tables (`yii_rbac_` prefix is used):

- `yii_rbac_item`.
- `yii_rbac_assignment`.
- `yii_rbac_item_child`.

`DbSchemaManager` allows to customize table names:

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Rbac\Db\DbSchemaManager;

/** @var ConnectionInterface $database */
$schemaManager = new DbSchemaManager(
    database: $database,
    itemsTable: 'custom_items',
    assignmentsTable: 'custom_assignments',    
    itemsChildrenTable: 'custom_items_children',
);
$schemaManager->ensureTables();
$schemaManager->ensureNoTables(); // Note: All existing data will be erased.
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
        // Requires https://github.com/yiisoft/rbac-rules-container or other compatible factory.
        ruleFactory: $rulesContainer,
    ),
);
$manager->addPermission(new Permission('posts.create'));
```

> Note wrapping manager with decorator - it additionally provides database transactions to guarantee data integrity.

> Note that it's not necessary to use both DB storages. Combining different implementations is possible. A quite popular 
> case is to manage items via [PHP files](https://github.com/yiisoft/rbac-php) while store assignments in database.

More examples can be found in [Yii RBAC](https://github.com/yiisoft/rbac) documentation.

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```php
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev). To run static analysis:

```php
./vendor/bin/psalm
```
