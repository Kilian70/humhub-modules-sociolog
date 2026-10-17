<?php

use humhub\components\Migration;

class m261017_120000_add_review_replacement_url extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%sociolog_entry_review}}', 'replacement_url', $this->string(1000)->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%sociolog_entry_review}}', 'replacement_url');
    }
}
