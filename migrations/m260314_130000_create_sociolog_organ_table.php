<?php

use yii\db\Migration;

class m260314_130000_create_sociolog_organ_table extends Migration
{
    public function safeUp()
    {
        // -------------------------------------------------
        // Tabelle: sociolog_organ
        // -------------------------------------------------
        $this->createTable('sociolog_organ', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'parent_id' => $this->integer()->null(),
            'sort_order' => $this->integer()->defaultValue(100),
            'color' => $this->string(20)->null(),
            'created_at' => $this->integer(),
            'created_by' => $this->integer(),
            'updated_at' => $this->integer(),
            'updated_by' => $this->integer(),
        ]);

        // Index für parent
        $this->createIndex(
            'idx_sociolog_organ_parent',
            'sociolog_organ',
            'parent_id'
        );

        // FK parent
        $this->addForeignKey(
            'fk_sociolog_organ_parent',
            'sociolog_organ',
            'parent_id',
            'sociolog_organ',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // -------------------------------------------------
        // SpaceConfig anpassen
        // -------------------------------------------------

        $this->addColumn(
            'sociolog_space_config',
            'organ_id',
            $this->integer()->null()
        );

        $this->createIndex(
            'idx_sociolog_space_config_organ',
            'sociolog_space_config',
            'organ_id'
        );

        $this->addForeignKey(
            'fk_sociolog_space_config_organ',
            'sociolog_space_config',
            'organ_id',
            'sociolog_organ',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // alte Spalte entfernen
        if ($this->db->schema->getTableSchema('sociolog_space_config')->getColumn('bereich') !== null) {
            $this->dropColumn('sociolog_space_config', 'bereich');
        }
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_sociolog_space_config_organ', 'sociolog_space_config');
        $this->dropIndex('idx_sociolog_space_config_organ', 'sociolog_space_config');
        $this->dropColumn('sociolog_space_config', 'organ_id');

        $this->dropForeignKey('fk_sociolog_organ_parent', 'sociolog_organ');
        $this->dropIndex('idx_sociolog_organ_parent', 'sociolog_organ');

        $this->dropTable('sociolog_organ');
    }
}