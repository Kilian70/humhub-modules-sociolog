<?php

use humhub\components\Migration;
use humhub\modules\sociolog\services\LegacyUpgradeService;
use yii\db\Query;

// Core 1.19's global migrator may skip external-module autoloading.
require_once dirname(__DIR__) . '/services/LegacyUpgradeService.php';

/** Added 2026-10-13; intentionally ordered before the destructive old steps. */
class m260228_175900_guard_legacy_upgrade extends Migration
{
    public function safeUp()
    {
        if (LegacyUpgradeService::$controlledUpdate) return true;
        $settingsAtRisk = !LegacyUpgradeService::applied(LegacyUpgradeService::SETTINGS)
            && (new Query())->from('setting')->where(['module_id'=>'sociolog','name'=>LegacyUpgradeService::KEYS])->exists();
        $schema = $this->db->schema->getTableSchema('sociolog_space_config', true);
        $areasAtRisk = !LegacyUpgradeService::applied(LegacyUpgradeService::ORGANS)
            && $schema && isset($schema->columns['bereich'])
            && (new Query())->from('sociolog_space_config')->where(['not', ['bereich'=>null]])->exists();
        $entrySchema = $this->db->schema->getTableSchema('sociolog_entry', true);
        $entriesAtRisk = (!LegacyUpgradeService::applied(LegacyUpgradeService::SETTINGS)
            || !LegacyUpgradeService::applied(LegacyUpgradeService::ORGANS))
            && $entrySchema && (new Query())->from('sociolog_entry')->exists();
        if ($settingsAtRisk || $areasAtRisk || $entriesAtRisk) {
            throw new \RuntimeException('Sociolog: Altwerte vorhanden. Erst sichern, dann geschützten upgrade.php-Pfad verwenden.');
        }
        return true;
    }
    public function safeDown() { return false; }
}
