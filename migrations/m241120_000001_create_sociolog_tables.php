<?php
// 🚨 vorübergehend KEIN namespace
use yii\db\Migration;

class m241120_000001_create_sociolog_tables extends Migration
{
    public function safeUp()
    {
        // ============================================================
        // 🔹 Tabelle: Entscheidungsarten (Decision Types)
        // ============================================================
        $this->createTable('{{%sociolog_decision_type}}', [
            'id'          => $this->primaryKey(),
            'name'        => $this->string(150)->notNull(),
            'color'       => $this->string(20)->defaultValue('#777777'),
            'description' => $this->string(500)->null(),
            'sort_order'  => $this->integer()->defaultValue(100),
        ]);

        // Beispiel-Datensätze
        $this->batchInsert('{{%sociolog_decision_type}}',
            ['name', 'color', 'description', 'sort_order'],
            [
                ['Grundsatzentscheid', '#4CAF50', 'Übergeordnete Leitlinie oder Regelung.', 10],
                ['Prozessentscheid',   '#2196F3', 'Legt konkrete Abläufe oder Zuständigkeiten fest.', 20],
                ['Richtlinie',         '#FFC107', 'Definiert verbindliches Verhalten im Alltag.', 30],
            ]
        );

        // ============================================================
        // 🔹 Tabelle: Logbuch-Einträge (Entries)
        // ============================================================
        $this->createTable('{{%sociolog_entry}}', [
            'id'               => $this->primaryKey(),
            'title'            => $this->string(255)->notNull(),
            'organ'            => $this->string(150)->notNull(),
            'topic_owner'      => $this->string(255)->null(),
            'decision'         => $this->text()->notNull(),
            'description'      => $this->text()->null(),
            'decision_type_id' => $this->integer()->notNull(),
            'decision_date'    => $this->date()->notNull(),
            'effective_date'   => $this->date()->null(),
            'review_date'      => $this->date()->null(),
            'protocol_link'    => $this->string(500)->null(),
            'status'           => $this->string(20)->null(),
            'created_by'       => $this->integer()->null(),
            'updated_by'       => $this->integer()->null(),
            'created_at'       => $this->integer()->null(),
            'updated_at'       => $this->integer()->null(),
        ]);

        // ============================================================
        // 🔹 Fremdschlüssel & Indizes
        // ============================================================
        $this->createIndex('idx_sociolog_entry_decision_type_id', '{{%sociolog_entry}}', 'decision_type_id');
        $this->addForeignKey(
            'fk_sociolog_entry_decision_type',
            '{{%sociolog_entry}}',
            'decision_type_id',
            '{{%sociolog_decision_type}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createIndex('idx_sociolog_entry_organ', '{{%sociolog_entry}}', 'organ');
        $this->createIndex('idx_sociolog_entry_status', '{{%sociolog_entry}}', 'status');
        $this->createIndex('idx_sociolog_entry_decision_date', '{{%sociolog_entry}}', 'decision_date');

        // 🔸 Fremdschlüssel zu Usern (optional, wenn user-Tabelle existiert)
        if ($this->db->schema->getTableSchema('{{%user}}', true)) {
            $this->addForeignKey(
                'fk_sociolog_entry_created_by',
                '{{%sociolog_entry}}',
                'created_by',
                '{{%user}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
            $this->addForeignKey(
                'fk_sociolog_entry_updated_by',
                '{{%sociolog_entry}}',
                'updated_by',
                '{{%user}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_sociolog_entry_created_by', '{{%sociolog_entry}}');
        $this->dropForeignKey('fk_sociolog_entry_updated_by', '{{%sociolog_entry}}');
        $this->dropForeignKey('fk_sociolog_entry_decision_type', '{{%sociolog_entry}}');

        $this->dropTable('{{%sociolog_entry}}');
        $this->dropTable('{{%sociolog_decision_type}}');
    }
}
