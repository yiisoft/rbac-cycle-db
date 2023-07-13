<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use DirectoryIterator;

abstract class SqlMigrationSchemaTest extends SqlSchemaTest
{
    public static function setUpBeforeClass(): void
    {
        $driverName = static::$driverName;
        $migrationsFolderPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'migrations';
        $migrationsFolderIterator = new DirectoryIterator($migrationsFolderPath);
        foreach ($migrationsFolderIterator as $migrationFolder) {
            if (!$migrationFolder->isDir() || $migrationFolder->isDot()) {
                continue;
            }

            $sqlBasePath = $migrationsFolderPath . DIRECTORY_SEPARATOR . $migrationFolder->getFilename();

            $upSqlPath = $sqlBasePath . DIRECTORY_SEPARATOR . "$driverName-up.sql";
            $upSql = file_get_contents($upSqlPath);
            $upQueries = explode(';' . PHP_EOL, trim($upSql));
            self::$upQueries = array_merge(self::$upQueries, $upQueries);

            $downSqlPath = $migrationsFolderPath . DIRECTORY_SEPARATOR . $migrationFolder->getFilename() . DIRECTORY_SEPARATOR . "$driverName-down.sql";
            $downSql = file_get_contents($downSqlPath);
            $downQueries = explode(';' . PHP_EOL, trim($downSql));
            self::$downQueries = array_merge(self::$downQueries, $downQueries);
        }
    }
}
