<?php

use humhub\components\Migration;
use yii\db\Query;

/**
 * Migriert das alte einzelne protocol_link-Feld in die Protokolltabelle.
 */
class m260712_120000_migrate_legacy_protocol_link extends Migration
{
    private const ENTRY_TABLE = '{{%sociolog_entry}}';
    private const PROTOCOL_TABLE = '{{%sociolog_protocol}}';

    public function safeUp()
    {
        $entrySchema = $this->db->schema->getTableSchema(self::ENTRY_TABLE, true);
        $protocolSchema = $this->db->schema->getTableSchema(self::PROTOCOL_TABLE, true);

        if ($entrySchema === null || !isset($entrySchema->columns['protocol_link'])) {
            return true;
        }

        if ($protocolSchema !== null) {
            $entries = (new Query())
                ->select(['id', 'protocol_link'])
                ->from(self::ENTRY_TABLE)
                ->where(['not', ['protocol_link' => null]])
                ->andWhere(['<>', 'protocol_link', ''])
                ->each(100, $this->db);

            foreach ($entries as $entry) {
                $url = trim((string)$entry['protocol_link']);
                if ($url === '') {
                    continue;
                }

                $exists = (new Query())
                    ->from(self::PROTOCOL_TABLE)
                    ->where([
                        'entry_id' => (int)$entry['id'],
                        'url' => $url,
                    ])
                    ->exists($this->db);

                if (!$exists) {
                    $this->insert(self::PROTOCOL_TABLE, [
                        'entry_id' => (int)$entry['id'],
                        'title' => 'Protokoll',
                        'url' => $url,
                        'created_at' => time(),
                    ]);
                }
            }
        }

        $this->dropColumn(self::ENTRY_TABLE, 'protocol_link');

        return true;
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema(self::ENTRY_TABLE, true);

        if ($schema !== null && !isset($schema->columns['protocol_link'])) {
            $this->addColumn(
                self::ENTRY_TABLE,
                'protocol_link',
                $this->string(500)->null()
            );
        }

        return true;
    }
}
