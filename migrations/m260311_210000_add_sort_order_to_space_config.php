<?php

use yii\db\Migration;

class m260311_210000_add_sort_order_to_space_config extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'sociolog_space_config',
            'sort_order',
            $this->integer()->defaultValue(1000)
        );
    }

    public function safeDown()
    {
        $this->dropColumn('sociolog_space_config', 'sort_order');
    }
}