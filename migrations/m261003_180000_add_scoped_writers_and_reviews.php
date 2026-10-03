<?php

use yii\db\Migration;

class m261003_180000_add_scoped_writers_and_reviews extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'sociolog_space_config',
            'writer_mode',
            $this->string(20)->notNull()->defaultValue('space_admins')->after('is_organ_space')
        );
        $this->addColumn(
            'sociolog_space_config',
            'writer_user_guids',
            $this->text()->null()->after('writer_mode')
        );

        $this->createTable('sociolog_entry_review', [
            'id' => $this->primaryKey(),
            'entry_id' => $this->integer()->notNull(),
            'result' => $this->string(20)->notNull(),
            'justification' => $this->text()->notNull(),
            'previous_review_date' => $this->date()->null(),
            'next_review_date' => $this->date()->null(),
            'protocol_id' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'created_by' => $this->integer()->null(),
        ]);

        $this->createIndex('idx-sociolog-entry-review-entry', 'sociolog_entry_review', 'entry_id');
        $this->addForeignKey(
            'fk-sociolog-entry-review-entry',
            'sociolog_entry_review',
            'entry_id',
            'sociolog_entry',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk-sociolog-entry-review-protocol',
            'sociolog_entry_review',
            'protocol_id',
            'sociolog_protocol',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-sociolog-entry-review-protocol', 'sociolog_entry_review');
        $this->dropForeignKey('fk-sociolog-entry-review-entry', 'sociolog_entry_review');
        $this->dropTable('sociolog_entry_review');
        $this->dropColumn('sociolog_space_config', 'writer_user_guids');
        $this->dropColumn('sociolog_space_config', 'writer_mode');
    }
}
