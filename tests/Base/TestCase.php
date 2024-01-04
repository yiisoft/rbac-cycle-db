<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Migrations\Capsule;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;
use RuntimeException;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static string $itemsTable = 'yii_rbac_item';
    protected static string $itemsChildrenTable = 'yii_rbac_item_child';
    protected static string $assignmentsTable = 'yii_rbac_assignment';
    protected static array $migrationsSubfolders = ['items', 'assignments'];

    private ?DatabaseProviderInterface $databaseManager = null;
    private ?DatabaseInterface $database = null;
    private ?Migrator $migrator = null;
    private ?Logger $logger = null;

    public function getLogger(): Logger
    {
        if ($this->logger === null) {
            throw new RuntimeException('Logger was not set.');
        }

        return $this->logger;
    }

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    protected function getDatabaseManager(): DatabaseProviderInterface
    {
        if ($this->databaseManager === null) {
            $this->databaseManager = $this->makeDatabaseManager();
        }

        return $this->databaseManager;
    }

    protected function getDatabase(): DatabaseInterface
    {
        if ($this->database === null) {
            $this->database = $this->getDatabaseManager()->database();
        }

        return $this->database;
    }

    protected function getMigrator(): Migrator
    {
        if ($this->migrator === null) {
            $this->migrator = $this->makeMigrator();
        }

        return $this->migrator;
    }

    protected function setUp(): void
    {
        $this->runMigrations();
        $this->populateDatabase();
    }

    protected function tearDown(): void
    {
        $this->rollbackMigrations();
        $this->getDatabase()->getDriver()->disconnect();
    }

    protected function makeMigrator(): Migrator
    {
        $directories = [];
        foreach (self::$migrationsSubfolders as $subfolder) {
            $directories[] = implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 2), 'migrations', $subfolder]);
        }

        $config = new MigrationConfig([
            'directory' => $directories[0],
            // "vendorDirectories" are specified because "directory" option doesn't support multiple directories. In the
            // end, it makes no difference.
            'vendorDirectories' => $directories[1] ?: [],
            'table' => 'cycle_migration',
            'safe' => true,
        ]);
        $migrator = new Migrator($config, $this->makeDatabaseManager(), new FileRepository($config));
        $migrator->configure();

        return $migrator;
    }

    protected function runMigrations(): void
    {
        $migrator = $this->getMigrator();
        $capsule = new Capsule($this->getDatabase());

        while (($migration = $migrator->run($capsule)) !== null) {
        }
    }

    protected function rollbackMigrations(): void
    {
        $migrator = $this->getMigrator();
        $capsule = new Capsule($this->getDatabase());

        while (($migration = $migrator->rollback($capsule)) !== null) {
        }
    }

    abstract protected function makeDatabaseManager(): DatabaseProviderInterface;

    abstract protected function populateDatabase(): void;
}
