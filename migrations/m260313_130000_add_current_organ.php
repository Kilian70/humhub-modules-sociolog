<?php

use yii\db\Migration;

class m260313_130000_add_current_organ extends Migration
{
    public function safeUp()
    {
        $this->addColumn('sociolog_entry', 'current_organ', $this->string(255)->null()->after('organ'));
    }

    public function safeDown()
    {
        $this->dropColumn('sociolog_entry', 'current_organ');
    }
}