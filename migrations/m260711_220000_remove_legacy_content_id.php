<?php

use humhub\components\Migration;

/**
 * Entfernt die alte direkte Verknüpfung zur Content-Tabelle.
 *
 * ContentActiveRecord verknüpft Inhalte standardmässig über
 * content.object_model und content.object_id. Die zusätzliche content_id-
 * Spalte mit CASCADE kollidiert mit dem HumHub-Lösch-Lifecycle.
 */
class m260711_220000_remove_legacy_content_id extends Migration
{
    private const TABLE = '{{%sociolog_entry}}';
    private const FOREIGN_KEY = 'fk-sociolog_entry-content_id';

    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema(self::TABLE, true);

        if ($schema === null || !isset($schema->columns['content_id'])) {
            return true;
        }

        if (isset($schema->foreignKeys[self::FOREIGN_KEY])) {
            $this->dropForeignKey(self::FOREIGN_KEY, self::TABLE);
        }

        $this->dropColumn(self::TABLE, 'content_id');

        return true;
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema(self::TABLE, true);

        if ($schema === null || isset($schema->columns['content_id'])) {
            return true;
        }

        $this->addColumn(self::TABLE, 'content_id', $this->integer()->null());
        $this->addForeignKey(
            self::FOREIGN_KEY,
            self::TABLE,
            'content_id',
            '{{%content}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        return true;
    }
}
