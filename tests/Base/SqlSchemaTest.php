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

        $upSqlPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . "$driverName-up.sql";
        $upSql = file_get_contents($upSqlPath);
        self::$upQueries = explode(';' . PHP_EOL, trim($upSqlPath));

        $downSqlPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . "$driverName-down.sql";
        $downSql = file_get_contents($downSqlPath);
        self::$downQueries = explode(PHP_EOL, trim($downSql));
    }

    protected function setUp(): void
    {
        $this->createSchemaManager()->ensureNoTables();
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
        $this->createSchema();
        $this->checkTables();
    }
}
