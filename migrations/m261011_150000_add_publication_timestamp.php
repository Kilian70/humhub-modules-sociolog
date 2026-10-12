<?php

use humhub\components\Migration;

class m261011_150000_add_publication_timestamp extends Migration
{
    public function safeUp()
    {
        // Historical publication dates have no reliable time of day.
        $this->addColumn('{{%sociolog_entry}}', 'published_timestamp', $this->bigInteger()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%sociolog_entry}}', 'published_timestamp');
    }
}
