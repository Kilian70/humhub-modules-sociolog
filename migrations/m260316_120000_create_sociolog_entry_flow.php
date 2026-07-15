<?php

use yii\db\Migration;

class m260316_120000_create_sociolog_entry_flow extends Migration
{
    public function safeUp()
    {
        $this->createTable('sociolog_entry_flow', [

            'id' => $this->primaryKey(),

            'entry_id' => $this->integer()->notNull(),

            'from_organ_id' => $this->integer(),

            'to_organ_id' => $this->integer(),

            'action' => $this->string(20)->notNull(),

            'created_at' => $this->dateTime()->notNull(),

            'created_by' => $this->integer(),

        ]);

        $this->addForeignKey(
            'fk-entry-flow-entry',
            'sociolog_entry_flow',
            'entry_id',
            'sociolog_entry',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-entry-flow-entry', 'sociolog_entry_flow');
        $this->dropTable('sociolog_entry_flow');
    }
}