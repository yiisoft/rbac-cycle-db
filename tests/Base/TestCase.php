<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\DatabaseInterface;
use Yiisoft\Rbac\Cycle\SchemaManager;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private ?DatabaseInterface $database = null;

    protected const ITEMS_TABLE = 'auth_item';
    protected const ASSIGNMENTS_TABLE = 'auth_assignment';
    protected const ITEMS_CHILDREN_TABLE = 'auth_item_child';

    protected function getDatabase(): DatabaseInterface
    {
        if ($this->database === null) {
            $this->database = $this->makeDatabase();
        }

        return $this->database;
    }

    protected function setUp(): void
    {
        $this->createDatabaseTables();
        $this->populateDatabase();
    }

    protected function tearDown(): void
    {
        $this->createSchemaManager()->dropAll();
    }

    protected function createDatabaseTables(): void
    {
        $this->createSchemaManager()->createAll();
    }

    protected function createSchemaManager(string|null $itemsChildrenTable = self::ITEMS_CHILDREN_TABLE): SchemaManager
    {
        return new SchemaManager(
            itemsTable: self::ITEMS_TABLE,
            assignmentsTable: self::ASSIGNMENTS_TABLE,
            database: $this->getDatabase(),
            itemsChildrenTable: $itemsChildrenTable,
        );
    }

    abstract protected function makeDatabase(): DatabaseInterface;

    abstract protected function populateDatabase(): void;
}
