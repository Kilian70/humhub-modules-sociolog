<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use sociolog\SociologTestCase;
use Yii;

class MigrationSchemaTest extends SociologTestCase
{
    public function testCompleteMigrationResult(): void
    {
        $requiredTables = [
            'sociolog_entry',
            'sociolog_decision_type',
            'sociolog_space_config',
            'sociolog_organ',
            'sociolog_entry_flow',
            'sociolog_protocol',
        ];

        foreach ($requiredTables as $table) {
            $this->assertNotNull(
                Yii::$app->db->schema->getTableSchema($table, true),
                "Migration result is missing table {$table}."
            );
        }

        $entrySchema = Yii::$app->db->schema->getTableSchema('sociolog_entry', true);
        foreach ([
            'id',
            'title',
            'organ',
            'current_organ',
            'organ_id',
            'decision_type_id',
            'published_at',
            'forwarded_at',
            'forwarded_to',
            'organ_link_mode',
            'organ_custom_link',
        ] as $column) {
            $this->assertArrayHasKey($column, $entrySchema->columns);
        }

        $this->assertArrayNotHasKey('content_id', $entrySchema->columns);
        $this->assertArrayNotHasKey('protocol_link', $entrySchema->columns);
    }
}
