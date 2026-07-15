<?php

use yii\db\Migration;

class m260311_200000_create_sociolog_space_config extends Migration
{
    public function safeUp()
    {
        $this->createTable('sociolog_space_config', [
            'id' => $this->primaryKey(),
            'space_id' => $this->integer()->notNull(),
            'bereich' => $this->string()->null(),
            'global_write' => $this->boolean()->defaultValue(false),
            'can_delete' => $this->boolean()->defaultValue(false),
        ]);

        $this->createIndex(
            'idx-sociolog-space-config-space',
            'sociolog_space_config',
            'space_id',
            true
        );
    }

    public function safeDown()
    {
        $this->dropTable('sociolog_space_config');
    }
}