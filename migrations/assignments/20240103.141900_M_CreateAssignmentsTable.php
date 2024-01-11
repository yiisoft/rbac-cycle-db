<?php

declare(strict_types=1);

use Cycle\Database\Table;
use Cycle\Migrations\Migration;

final class CreateAssignmentsTable extends Migration
{
    private const TABLE_PREFIX = 'yii_rbac_';
    private const ASSIGNMENTS_TABLE = self::TABLE_PREFIX . 'assignment';

    public function up()
    {
        $this->createAssignmentsTable();
    }

    public function down()
    {
        $this->dropTable(self::ASSIGNMENTS_TABLE);
    }

    private function createAssignmentsTable(): void
    {
        /** @var Table $table */
        $table = $this->database()->table(self::ASSIGNMENTS_TABLE);
        $schema = $table->getSchema();

        $schema->string('itemName', 128)->nullable(false);
        $schema->string('userId', 128)->nullable(false);
        $schema->setPrimaryKeys(['itemName', 'userId']);
        $schema->integer('createdAt')->nullable(false);

        $schema->save();
    }

    private function dropTable(string $tableName): void
    {
        /** @var Table $table */
        $table = $this->database()->table($tableName);
        $schema = $table->getSchema();
        $schema->declareDropped();
        $schema->save();
    }
}
