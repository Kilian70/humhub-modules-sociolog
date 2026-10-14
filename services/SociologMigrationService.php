<?php

namespace humhub\modules\sociolog\services;

use humhub\services\MigrationService;
use yii\base\InvalidConfigException;

/**
 * Stops an unsupported installation before the historical migration chain can
 * create a mixture of prefixed and unprefixed tables. Published migrations
 * remain unchanged; prefix support requires a separate installation strategy.
 */
class SociologMigrationService extends MigrationService
{
    public static function assertSupportedDatabase(): void
    {
        if ((string)\Yii::$app->db->tablePrefix !== '') {
            throw new InvalidConfigException(
                'Sociolog: Die bestehende Migrationsfolge unterstützt keine Tabellenpräfixe. '
                . 'Die Modulinstallation wurde vor Datenbankänderungen abgebrochen. '
                . 'Bitte eine HumHub-Testinstallation ohne Tabellenpräfix verwenden.'
            );
        }
    }

    public function migrateUp(): ?bool
    {
        self::assertSupportedDatabase();
        LegacyUpgradeService::assertNormalRouteSafe();
        return parent::migrateUp();
    }
}
