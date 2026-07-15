<?php

use yii\db\Migration;

class m260313_120000_add_workflow_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('sociolog_entry','published_at',$this->date()->null());
        $this->addColumn('sociolog_entry','forwarded_at',$this->date()->null());
        $this->addColumn('sociolog_entry','forwarded_to',$this->string(255)->null());
    }

    public function safeDown()
    {
        $this->dropColumn('sociolog_entry','published_at');
        $this->dropColumn('sociolog_entry','forwarded_at');
        $this->dropColumn('sociolog_entry','forwarded_to');
    }
}