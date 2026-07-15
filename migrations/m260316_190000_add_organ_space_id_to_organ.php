<?php

use yii\db\Migration;

class m260316_190000_add_organ_space_id_to_organ extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'sociolog_organ',
            'organ_space_id',
            $this->integer()->null()
        );

        $this->addForeignKey(
            'fk_sociolog_organ_space',
            'sociolog_organ',
            'organ_space_id',
            'space',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(
            'fk_sociolog_organ_space',
            'sociolog_organ'
        );

        $this->dropColumn(
            'sociolog_organ',
            'organ_space_id'
        );
    }
}