<?php

namespace humhub\modules\sociolog\services;

use Yii;
use yii\db\Query;

/** No guessing, no deletion, no reconstruction of already lost historical values. */
class LegacyUpgradeService
{
    public static bool $controlledUpdate = false;

    public const SETTINGS = 'm260228_180000_fix_settings_format';
    public const ORGANS = 'm260314_130000_create_sociolog_organ_table';
    public const ARCHIVE = 'm261013_180000_create_legacy_upgrade_archive';
    public const KEYS = ['writerUsers', 'deleterUsers', 'writerGroups', 'deleterGroups', 'notifyGroups'];

    public static function applied(string $version): bool
    {
        return Yii::$app->db->schema->getTableSchema('migration', true) !== null
            && (new Query())->from('migration')->where(['version' => $version])->exists();
    }

    public static function assertNormalRouteSafe(): void
    {
        if (Yii::$app->db->schema->getTableSchema('sociolog_entry', true) !== null
            && (!self::applied(self::SETTINGS) || !self::applied(self::ORGANS))) {
            throw new \yii\base\InvalidConfigException(
                'Sociolog: Historischer Updatepfad gesperrt. Erst sichern und '
                . 'php protected/modules/sociolog/upgrade.php prüfen; danach mit --apply ausführen.'
            );
        }
    }

    public static function validateSchema(): void
    {
        $schema = \Yii::$app->db->schema;
        if ($schema->getTableSchema('sociolog_entry', true) !== null
            && !self::applied('m241120_000001_create_sociolog_tables')) {
            throw new \RuntimeException('Migrationshistorie unvollständig; keine automatische Schema-Rekonstruktion.');
        }
        if (!self::applied(self::ORGANS) && $schema->getTableSchema('sociolog_organ', true) !== null) {
            throw new \RuntimeException('Teilweise Organmigration erkannt; zuerst Schema und Sicherung prüfen.');
        }
    }

    public static function settingsPlan(): array
    {
        if (self::applied(self::SETTINGS)) return [];
        $plan = [];
        foreach ((new Query())->from('setting')->where(['module_id' => 'sociolog', 'name' => self::KEYS])->all() as $row) {
            $raw = (string)$row['value'];
            try { $values = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
            catch (\JsonException $e) { $values = @unserialize($raw, ['allowed_classes' => false]); }
            if ($row['value'] === null || $raw === '' || $raw === 'null') $values = [];
            if (!is_array($values) || !array_is_list($values)) {
                throw new \RuntimeException('Unbekanntes Auswahlformat: ' . $row['name'] . '; keine Werte verändert.');
            }
            $converted = [];
            foreach ($values as $value) {
                if (str_ends_with($row['name'], 'Users')) {
                    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                        $guid = (new Query())->select('guid')->from('user')->where(['id' => (int)$value])->scalar();
                    } elseif (is_string($value)) {
                        $guid = (new Query())->select('guid')->from('user')->where(['guid' => $value])->scalar();
                    } else { $guid = false; }
                    if (!$guid) throw new \RuntimeException('Nicht auflösbare Benutzerauswahl: ' . $row['name']);
                    $converted[] = $guid;
                } else {
                    if (!(is_int($value) || (is_string($value) && ctype_digit($value))) || (int)$value <= 0
                        || !(new Query())->from('group')->where(['id' => (int)$value])->exists()) {
                        throw new \RuntimeException('Nicht auflösbare Gruppenauswahl: ' . $row['name']);
                    }
                    $converted[] = (int)$value;
                }
            }
            $plan[] = ['row' => $row, 'value' => json_encode($converted, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)];
        }
        return $plan;
    }

    /** Preserve complete raw rows, never overwrite an earlier snapshot. */
    public static function snapshot(): void
    {
        $db = Yii::$app->db;
        foreach (['setting', 'sociolog_space_config', 'sociolog_entry'] as $table) {
            if ($db->schema->getTableSchema($table, true) === null) continue;
            $query = (new Query())->from($table);
            if ($table === 'setting') $query->where(['module_id' => 'sociolog']);
            foreach ($query->each(100) as $row) {
                $key = ['source_table' => $table, 'source_id' => (int)$row['id']];
                if ((new Query())->from('sociolog_upgrade_archive')->where($key)->exists()) continue;
                $db->createCommand()->insert('sociolog_upgrade_archive', $key + [
                    'payload' => json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'created_at' => time(),
                ])->execute();
            }
        }
    }
}
