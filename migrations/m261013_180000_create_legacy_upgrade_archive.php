<?php

use humhub\components\Migration;

/** Additive archive for the explicitly controlled legacy update route. */
class m261013_180000_create_legacy_upgrade_archive extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%sociolog_upgrade_archive}}', [
            'id' => $this->primaryKey(),
            'source_table' => $this->string(64)->notNull(),
            'source_id' => $this->integer()->notNull(),
            'payload' => $this->db->getSchema()->createColumnSchemaBuilder('longtext')->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('sociolog_upgrade_archive_source', '{{%sociolog_upgrade_archive}}', ['source_table', 'source_id'], true);
    }

    public function safeDown()
    {
        echo "Archiv erhalten; Downgrade nur aus einer vollständigen Sicherung.\n";
        return false;
    }
}
