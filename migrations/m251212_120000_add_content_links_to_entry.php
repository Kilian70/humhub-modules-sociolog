<?php

use yii\db\Migration;
use humhub\modules\content\models\Content;

/**
 * ============================================================
 * 🔹 Migration: content_id hinzufügen + Content-Einträge erzeugen
 * ------------------------------------------------------------
 * Diese Migration:
 *  1️⃣ Fügt die Spalte `content_id` in der Tabelle `sociolog_entry` hinzu (falls nicht vorhanden)
 *  2️⃣ Erstellt für alle bestehenden Einträge einen passenden `content`-Datensatz,
 *      damit sie im Stream & Kalender erscheinen können.
 * ============================================================
 */
class m251212_120000_add_content_links_to_entry extends Migration
{
    public function safeUp()
    {
        $table = 'sociolog_entry';

        // 1️⃣ Spalte content_id hinzufügen, falls nicht vorhanden
        $columns = $this->db->getTableSchema($table)->columns;
        if (!array_key_exists('content_id', $columns)) {
            $this->addColumn($table, 'content_id', $this->integer()->null());
            $this->addForeignKey(
                'fk-sociolog_entry-content_id',
                $table,
                'content_id',
                'content',
                'id',
                'CASCADE',
                'CASCADE'
            );
            echo "✔ Spalte 'content_id' hinzugefügt.\n";
        } else {
            echo "ℹ Spalte 'content_id' existiert bereits.\n";
        }

        // 2️⃣ Content-Einträge für bestehende Sociolog-Einträge erzeugen
        $entries = (new \yii\db\Query())
            ->from($table)
            ->where(['or', ['content_id' => null], ['content_id' => 0]])
            ->all();

        $count = 0;
        foreach ($entries as $entry) {
            try {
                $content = new Content([
                    'object_model' => 'humhub\modules\sociolog\models\Entry',
                    'object_id' => $entry['id'],
                    'created_by' => $entry['created_by'] ?? 1,
                    'updated_by' => $entry['updated_by'] ?? $entry['created_by'] ?? 1,
                    'visibility' => 1,
                ]);

                // Wenn möglich, Standardcontainer = Benutzer selbst
                if (Yii::$app->hasModule('user') && !empty($entry['created_by'])) {
                    $user = \humhub\modules\user\models\User::findOne($entry['created_by']);
                    if ($user && $user->contentContainer) {
                        $content->container = $user->contentContainer;
                    }
                }

                if ($content->save()) {
                    Yii::$app->db->createCommand()
                        ->update($table, ['content_id' => $content->id], ['id' => $entry['id']])
                        ->execute();
                    $count++;
                } else {
                    echo "⚠ Fehler beim Erstellen von Content für Entry-ID {$entry['id']}:\n";
                    print_r($content->errors);
                }
            } catch (\Throwable $e) {
                echo "⚠ Ausnahme bei Entry-ID {$entry['id']}: {$e->getMessage()}\n";
            }
        }

        echo "✔ {$count} Content-Einträge erfolgreich verknüpft.\n";
        return true;
    }

    public function safeDown()
    {
        echo "⛔ Diese Migration kann nicht rückgängig gemacht werden (Daten bleiben verknüpft).\n";
        return false;
    }
}
