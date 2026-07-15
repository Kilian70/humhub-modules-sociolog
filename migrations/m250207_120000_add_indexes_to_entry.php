<?php

use yii\db\Migration;

/**
 * ============================================================
 * 🔹 Migration: Indexe für sociolog_entry
 * ------------------------------------------------------------
 * Optimiert Such- und Filterleistung für grosse Datenmengen.
 * ============================================================
 */
class m250207_120000_add_indexes_to_entry extends Migration
{
    public function safeUp()
    {
        // Prüfen, ob Tabelle existiert
        if ($this->db->schema->getTableSchema('{{%sociolog_entry}}', true) === null) {
            echo "Tabelle sociolog_entry existiert nicht – Migration übersprungen.\n";
            return;
        }

        // Indexe anlegen (nur wenn noch nicht vorhanden)
        $indexes = [
            'idx_decision_date'  => 'decision_date',
            'idx_status'         => 'status',
            'idx_organ'          => 'organ',
            'idx_decision_type'  => 'decision_type_id',
            'idx_review_date'    => 'review_date',
        ];

        foreach ($indexes as $name => $column) {
            if ($this->db->schema->getTableSchema('{{%sociolog_entry}}')->getColumn($column)) {
                if (!$this->hasIndex($name, '{{%sociolog_entry}}')) {
                    $this->createIndex($name, '{{%sociolog_entry}}', $column);
                    echo "Index {$name} erstellt.\n";
                } else {
                    echo "Index {$name} existiert bereits – übersprungen.\n";
                }
            }
        }
    }

    public function safeDown()
    {
        $indexes = [
            'idx_decision_date',
            'idx_status',
            'idx_organ',
            'idx_decision_type',
            'idx_review_date',
        ];

        foreach ($indexes as $name) {
            if ($this->hasIndex($name, '{{%sociolog_entry}}')) {
                $this->dropIndex($name, '{{%sociolog_entry}}');
                echo "Index {$name} entfernt.\n";
            }
        }
    }

    /**
     * Prüft, ob ein Index bereits existiert.
     */
    private function hasIndex(string $name, string $table): bool
    {
        $tableSchema = $this->db->schema->getTableSchema($table);
        return isset($tableSchema->indexes[$name]);
    }
}
