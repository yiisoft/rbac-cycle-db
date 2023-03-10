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

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/rbac-cycle-db
```

## General usage

```shell
rbac/cycle/init
```

By default, when called repeatedly, the creation of tables will be skipped if they are already exist. The `--force` flag 
can be added to force dropping of existing tables and recreate them:

```shell
rbac/cycle/init --force
```

> **Note:** All existing data will be erased as well.

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
