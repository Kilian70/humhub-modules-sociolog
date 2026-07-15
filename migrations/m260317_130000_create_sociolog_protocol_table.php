<?php

use yii\db\Migration;

class m260317_130000_create_sociolog_protocol_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('sociolog_protocol', [

            'id' => $this->primaryKey(),

            'entry_id' => $this->integer()->notNull(),

            'title' => $this->string(255)->notNull(),

            'url' => $this->string(1000)->notNull(),

            'created_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
        ]);

        $this->createIndex(
            'idx_sociolog_protocol_entry',
            'sociolog_protocol',
            'entry_id'
        );

        $this->addForeignKey(
            'fk_sociolog_protocol_entry',
            'sociolog_protocol',
            'entry_id',
            'sociolog_entry',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(
            'fk_sociolog_protocol_entry',
            'sociolog_protocol'
        );

        $this->dropTable('sociolog_protocol');
    }
}