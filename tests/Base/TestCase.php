<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\DatabaseInterface;
use Yiisoft\Rbac\Cycle\DbSchemaManager;

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
        $this->createSchemaManager()->ensureTables();
        $this->populateDatabase();
    }

    protected function tearDown(): void
    {
        $this->createSchemaManager()->ensureNoTables();
    }

    protected function createSchemaManager(
        ?string $itemsTable = self::ITEMS_TABLE,
        ?string $itemsChildrenTable = self::ITEMS_CHILDREN_TABLE,
        ?string $assignmentsTable = self::ASSIGNMENTS_TABLE,
    ): DbSchemaManager {
        return new DbSchemaManager(
            database: $this->getDatabase(),
            itemsTable: $itemsTable,
            itemsChildrenTable: $itemsChildrenTable,
            assignmentsTable: $assignmentsTable,
        );
    }

    abstract protected function makeDatabase(): DatabaseInterface;

    abstract protected function populateDatabase(): void;
}
