<?php

use yii\db\Migration;

/**
 * ============================================================
 * 🔹 Migration: Organ-Link Felder für sociolog_entry
 * ------------------------------------------------------------
 * Ermöglicht:
 * - automatischen Space-Link
 * - eigenen Link
 * - kein Link
 * ============================================================
 */
class m260401_200000_add_organ_link_fields_to_entry extends Migration
{
    public function safeUp()
    {
        // prüfen ob Tabelle existiert
        if ($this->db->schema->getTableSchema('{{%sociolog_entry}}', true) === null) {
            echo "Tabelle sociolog_entry existiert nicht – Migration übersprungen.\n";
            return;
        }

        // Link-Modus
        $this->addColumn(
            '{{%sociolog_entry}}',
            'organ_link_mode',
            $this->string()->defaultValue('space')
        );

        // eigener Link
        $this->addColumn(
            '{{%sociolog_entry}}',
            'organ_custom_link',
            $this->string()->null()
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%sociolog_entry}}', 'organ_link_mode');
        $this->dropColumn('{{%sociolog_entry}}', 'organ_custom_link');
    }
}