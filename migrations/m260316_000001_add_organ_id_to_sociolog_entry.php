<?php

use yii\db\Migration;

class m260316_000001_add_organ_id_to_sociolog_entry extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%sociolog_entry}}',
            'organ_id',
            $this->integer()->null()->after('organ')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%sociolog_entry}}', 'organ_id');
    }
}