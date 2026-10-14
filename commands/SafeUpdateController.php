<?php
namespace humhub\modules\sociolog\commands;

use humhub\commands\MigrateController;
use humhub\modules\sociolog\services\LegacyUpgradeService;
use humhub\modules\sociolog\services\SociologMigrationService;
use humhub\modules\sociolog\upgrade\PreserveSettings;
use humhub\modules\sociolog\upgrade\PreserveOrgans;
use humhub\modules\sociolog\upgrade\PreserveOrganFlags;
use yii\db\MigrationInterface;

/** Fixed module-only route; published migration files are never edited. */
class SafeUpdateController extends MigrateController
{
    public function beforeAction($action): bool
    {
        if (!in_array($action->id, ['up', 'new', 'legacy-areas'], true)) throw new \RuntimeException('Nur Vorprüfung und Update erlaubt.');
        if (!\Yii::$app->moduleManager instanceof \humhub\modules\sociolog\upgrade\CoreOnlyModuleManager) {
            throw new \RuntimeException('Geschützter Konsolenstart erforderlich: upgrade.php verwenden.');
        }
        SociologMigrationService::assertSupportedDatabase();
        if ($action->id !== 'legacy-areas') {
            LegacyUpgradeService::validateSchema();
            LegacyUpgradeService::settingsPlan();
        }
        return parent::beforeAction($action);
    }
    public function actionLegacyAreas(): int
    {
        $schema = \Yii::$app->db->schema->getTableSchema('sociolog_space_config', true);
        if (!$schema || !isset($schema->columns['bereich'])) {
            $this->stdout("Keine erhaltene Bereichsspalte vorhanden; frühere Verluste benötigen eine Sicherung.\n");
            return 0;
        }
        $columns = ['id', 'space_id', 'bereich'];
        if (isset($schema->columns['organ_id'])) $columns[] = 'organ_id';
        foreach ((new \yii\db\Query())->select($columns)->from('sociolog_space_config')->orderBy('id')->each(100) as $row) {
            $this->stdout(json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . "\n");
        }
        return 0;
    }

    protected function migrateUp($class)
    {
        if ($class !== 'm260228_175900_guard_legacy_upgrade') return parent::migrateUp($class);
        LegacyUpgradeService::$controlledUpdate = true;
        try { return parent::migrateUp($class); }
        finally { LegacyUpgradeService::$controlledUpdate = false; }
    }

    protected function getMigrationPaths(): array
    {
        return [dirname(__DIR__) . '/migrations'];
    }
    protected function getNewMigrations(): array
    {
        $this->includeModuleMigrations = 1;
        $pending = parent::getNewMigrations();
        if (in_array(LegacyUpgradeService::ARCHIVE, $pending, true)) {
            $pending = array_values(array_diff($pending, [LegacyUpgradeService::ARCHIVE]));
            array_unshift($pending, LegacyUpgradeService::ARCHIVE);
        }
        return $pending;
    }
    protected function createMigration($class): MigrationInterface
    {
        $original = parent::createMigration($class); // Native path resolution/history unchanged.
        if ($class === LegacyUpgradeService::SETTINGS) return new PreserveSettings(['db' => $this->db, 'compact' => $this->compact]);
        if ($class === LegacyUpgradeService::ORGANS) return new PreserveOrgans(['db' => $this->db, 'compact' => $this->compact]);
        if ($class === 'm260316_140000_add_is_organ_space') return new PreserveOrganFlags(['db'=>$this->db, 'compact'=>$this->compact]);
        return $original;
    }
}
