<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\Database;

abstract class SqlSchemaTest extends TestCase
{
    use SchemaTrait;

    protected static string $driverName = '';
    protected static array $upQueries = [];
    protected static array $downQueries = [];

    public static function setUpBeforeClass(): void
    {
        $driverName = static::$driverName;
        $sqlBasePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sql';

        self::$upQueries = self::parseQueries($sqlBasePath . DIRECTORY_SEPARATOR . "$driverName-up.sql");
        self::$downQueries = self::parseQueries($sqlBasePath . DIRECTORY_SEPARATOR . "$driverName-down.sql");
    }

    protected function setUp(): void
    {
        // Skip
    }

    protected function tearDown(): void
    {
        $this->createSchemaManager()->ensureNoTables();
    }

    protected function populateDatabase(): void
    {
        // Skip
    }

    protected function createSchema(): void
    {
        $this
            ->getDatabase()
            ->transaction(static function (Database $database): void {
                foreach (self::$upQueries as $query) {
                    $database->execute($query);
                }
            });
    }

    protected function dropSchema(): void
    {
        $this
            ->getDatabase()
            ->transaction(static function (Database $database): void {
                foreach (self::$downQueries as $query) {
                    $database->execute($query);
                }
            });
    }

    public function testCreateSchema(): void
    {
        var_dump($this->createSchemaManager()->hasTable(self::ITEMS_TABLE));
        $this->createSchemaManager()->ensureNoTables();
        var_dump($this->createSchemaManager()->hasTable(self::ITEMS_TABLE));
        $this->createSchema();
        $this->checkTables();
    }

//    public function testDropSchema(): void
//    {
//        $schemaManager = $this->createSchemaManager();
//        $schemaManager->ensureTables();
//
//        $this->dropSchema();
//
//        $this->assertFalse($this->createSchemaManager()->hasTable($schemaManager->getItemsTable()));
//        $this->assertFalse($this->createSchemaManager()->hasTable($schemaManager->getAssignmentsTable()));
//        $this->assertFalse($this->createSchemaManager()->hasTable($schemaManager->getItemsChildrenTable()));
//    }

    protected static function parseQueries(string $sqlPath): array
    {
        $sql = file_get_contents($sqlPath);
        $sql = trim($sql);
        $sql = rtrim($sql, ';');

        return preg_split('/;\R/', $sql);
    }
}
