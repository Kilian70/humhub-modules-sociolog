<?php

use humhub\components\Migration;

class m261011_170000_add_read_scope extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%sociolog_read_scope}}', [
            'id' => $this->primaryKey(),
            'guid' => $this->string(36)->notNull(),
            'entry_id' => $this->integer()->notNull(),
            'contentcontainer_id' => $this->integer()->null(),
            'original_container_id' => $this->integer()->null(),
            'original_hidden' => $this->boolean()->notNull()->defaultValue(false),
        ]);
        $this->createIndex('sociolog_read_scope_entry', '{{%sociolog_read_scope}}', 'entry_id', true);
        $this->createIndex('sociolog_read_scope_guid', '{{%sociolog_read_scope}}', 'guid', true);
    }

    public function safeDown()
    {
        // Never leave protected content pointing to a deleted ACL container.
        echo "Restore the pre-update database backup to downgrade this access-control migration.\n";
        return false;
    }
}
