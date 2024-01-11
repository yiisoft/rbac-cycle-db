<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Migrations\Capsule;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;
use phpDocumentor\Reflection\Types\Static_;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static string $itemsTable = 'yii_rbac_item';
    protected static string $itemsChildrenTable = 'yii_rbac_item_child';
    protected static string $assignmentsTable = 'yii_rbac_assignment';
    protected static array $migrationsSubfolders = ['items', 'assignments'];

    protected static ?DatabaseManager $databaseManager = null;
    protected ?Migrator $migrator = null;

    protected function getDatabaseManager(): DatabaseManager
    {
        if (self::$databaseManager === null) {
            self::$databaseManager = $this->makeDatabaseManager();
        }

        return self::$databaseManager;
    }

    protected function getDatabase(): DatabaseInterface
    {
        return $this->getDatabaseManager()->database();
    }

    protected function getMigrator(): Migrator
    {
        if ($this->migrator === null) {
            $this->migrator = $this->makeMigrator();
        }

        return $this->migrator;
    }

    public static function setUpBeforeClass(): void
    {
        (new static(static::class))->runMigrations();
    }

    public static function tearDownAfterClass(): void
    {
        (new static(static::class))->rollbackMigrations();
        (new static(static::class))->getDatabase()->getDriver()->disconnect();
    }

    protected function setUp(): void
    {
        $this->populateDatabase();
    }

    private function makeMigrator(): Migrator
    {
        $directories = [];
        foreach (static::$migrationsSubfolders as $subfolder) {
            $directories[] = implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 2), 'migrations', $subfolder]);
        }

        $config = new MigrationConfig([
            'directory' => $directories[0],
            // "vendorDirectories" are specified because "directory" option doesn't support multiple directories. In the
            // end, it makes no difference.
            'vendorDirectories' => $directories[1] ?? [],
            'table' => 'cycle_migration',
            'safe' => true,
        ]);
        $migrator = new Migrator($config, $this->getDatabaseManager(), new FileRepository($config));
        $migrator->configure();

        return $migrator;
    }

    protected function runMigrations(): void
    {
        $migrator = $this->getMigrator();
        $capsule = new Capsule($this->getDatabase());

        while ($migrator->run($capsule) !== null) {
        }
    }

    protected function rollbackMigrations(): void
    {
        $migrator = $this->getMigrator();
        $capsule = new Capsule($this->getDatabase());

        while ($migrator->rollback($capsule) !== null) {
        }
    }

    abstract protected function makeDatabaseManager(): DatabaseProviderInterface;

    abstract protected function populateDatabase(): void;
}
