<?php

use yii\db\Migration;

/**
 * ============================================================
 * 🔹 Migration: Erweiterte Link-Konfiguration für Spaces
 * ------------------------------------------------------------
 * ermöglicht:
 *
 * - Space im Logbuch anzeigen / ausblenden
 * - automatischen Space-Link
 * - About-Seite
 * - eigenen Link
 * - kein Link
 *
 * ============================================================
 */
class m260401_210000_add_link_to_space_config extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%sociolog_space_config}}', true) === null) {
            echo "Tabelle sociolog_space_config existiert nicht – Migration übersprungen.\n";
            return;
        }

        // externer Link
        $this->addColumn(
            '{{%sociolog_space_config}}',
            'link',
            $this->string()->null()
        );

        // Link-Typ
        $this->addColumn(
            '{{%sociolog_space_config}}',
            'link_mode',
            $this->string()->defaultValue('about')
        );

        // Space im Logbuch anzeigen
        $this->addColumn(
            '{{%sociolog_space_config}}',
            'enabled',
            $this->boolean()->defaultValue(true)
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%sociolog_space_config}}', 'link');
        $this->dropColumn('{{%sociolog_space_config}}', 'link_mode');
        $this->dropColumn('{{%sociolog_space_config}}', 'enabled');
    }
}