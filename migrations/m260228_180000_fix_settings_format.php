<?php

use humhub\components\Migration;

class m260228_180000_fix_settings_format extends Migration
{
    public function safeUp()
    {
        $this->delete('{{%setting}}', [
            'module_id' => 'sociolog',
            'name' => [
                'writerUsers',
                'deleterUsers',
                'writerGroups',
                'deleterGroups',
                'notifyGroups',
            ]
        ]);
    }

    public function safeDown()
    {
        return true;
    }
}