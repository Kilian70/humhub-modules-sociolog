<?php

namespace humhub\modules\sociolog;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\base\Event;
use humhub\modules\sociolog\models\Entry;
use humhub\components\Module as BaseModule;
use humhub\modules\sociolog\permissions\CreateEntry;
use humhub\modules\sociolog\permissions\UpdateEntry;
use humhub\modules\sociolog\permissions\DeleteEntry;
use humhub\modules\space\models\Space;

/**
 * ============================================================
 * 🔹 Sociolog-Modul (Logbuch)
 * ------------------------------------------------------------
 * - Verwaltung von Grundsatzentscheiden, Regeln und Richtlinien
 * - Kompatibel mit HumHub 1.18+
 * - Anzeige neuer Einträge im Stream (optional)
 * - Integration mit Kalendermodul (Überprüfungstermine)
 * - Automatischer Cron-Statuslauf via CLI
 * ============================================================
 */
class Module extends BaseModule
{
    /** 🏷️ Modul-Name (Fallback) */
    public $moduleName = 'Logbuch';

    /** 📁 Pfad für Ressourcen */
    public $resourcesPath = 'resources';

    /** 🧩 Modul-Version & Kompatibilität */
    public string $version = '1.0.36';
    public string $humhubMinVersion = '1.18';
    
  
// ============================================================
// 🔧 Initialisierung
// ============================================================
public function init()
{
    parent::init();
    \humhub\modules\sociolog\components\LogbookMailSummary::register();

    // ============================================================
    // 📁 Modul-Alias setzen (HumHub Best Practice)
    // ============================================================
    Yii::setAlias('@sociolog', __DIR__);



    // ============================================================
    // 🪵 Eigenes Logfile registrieren
    // ============================================================
    if (!isset(Yii::$app->log->targets['sociolog'])) {

        Yii::$app->log->targets['sociolog'] = new \yii\log\FileTarget([
            'logFile'     => Yii::getAlias('@runtime/logs/sociolog.log'),
            'categories'  => ['sociolog', 'sociolog.*'],
            'logVars'     => [],
            'levels'      => ['info', 'warning', 'error'],
            'maxFileSize' => 1024,
            'maxLogFiles' => 5,
        ]);
    }

    // ============================================================
    // 🔹 Übersetzungen registrieren
    // ============================================================
    // Exact instances retain the German source language when HumHub's I18N
    // automatically registers module categories. Native overrides remain supported.
    foreach (['base', 'permissions'] as $category) {
        Yii::$app->i18n->translations['SociologModule.' . $category] =
            new \humhub\components\i18n\ModuleMessageSource([
                'module' => $this,
                'sourceLanguage' => 'de',
                'forceTranslation' => true,
            ]);
    }

    // ============================================================
    // ⚙️ Standardwerte setzen
    // ============================================================
    $this->ensureDefaultSettings();

    // ============================================================
    // 🏷️ Modul-Titel für JavaScript verfügbar machen
    // ============================================================
    if (Yii::$app instanceof \yii\web\Application && Yii::$app->has('view')) {

        $moduleTitle = $this->settings->get('moduleTitle')
            ?: Yii::t('SociologModule.base', 'Logbuch');

        Yii::$app->view->registerJs(
            'window.SOCIOLOG_MODULE_TITLE = ' . json_encode($moduleTitle) . ';',
            \yii\web\View::POS_HEAD
        );
    }

    // Vor dem HumHub-Content-Handler ausführen. Dieser löscht bei einer
    // vollständigen Benutzerlöschung sonst alle vom Benutzer erstellten Inhalte.
    // Diese Registrierung bleibt absichtlich hier, weil append=false den Handler
    // vor den bereits registrierten HumHub-Löschhandler setzt. Die normalen
    // Modul-Events werden einheitlich über config.php registriert.
    Event::on(
        \humhub\modules\user\models\User::class,
        \humhub\modules\user\models\User::EVENT_BEFORE_DELETE,
        [\humhub\modules\sociolog\Events::class, 'onUserDelete'],
        null,
        false
    );
    // Profile content is removed at this earlier event. Queue/form preflight
    // additionally runs before Core can start deleting owned Spaces.
    Event::on(
        \humhub\modules\user\models\User::class,
        \humhub\modules\user\models\User::EVENT_BEFORE_SOFT_DELETE,
        [\humhub\modules\sociolog\Events::class, 'onUserSoftDelete'],
        null,
        false
    );
}
    
    // ============================================================
    // ⚙️ Standard-Settings initialisieren
    // ============================================================
    private function ensureDefaultSettings(): void
    {
        // Sicherheitscheck, falls Settings-Objekt noch nicht existiert
        if (!isset($this->settings)) {
            $this->settings = Yii::$app->settings->module('sociolog');
        }

        $defaults = [
            'notificationsEnabled' => true,
            'widgetSortOrder'      => 100,
            'mainMenuSortOrder'    => 350,
            'moduleTitle'          => 'Logbuch',
            'showEntriesInStream'  => true,
            'showReviewInCalendar' => true,
            'infoPageEnabled'      => false,
            'autoPublicationDate'  => false,
            'fixedDecisionTypeId'  => 0,
            'showDecisionTypeHeader' => true,
            'reviewDateRequiredForNewEntries' => false,
            'limitedReviewMaintenanceEnabled' => false,
            'lockPublishedEntries' => false,
            'statusManagersOnly'   => false,
            'extendedStatusesEnabled' => false,
            'effectiveDateAddExtraDay' => true,
            'preserveEntriesOnUserDelete' => false,
            'archiveUserId' => 0,
            'notificationRecipientMode' => 'all',
        ];

        foreach ($defaults as $key => $value) {
            if ($this->settings->get($key) === null) {
                $this->settings->set($key, $value);
            }
        }
    }

    public function enable()
    {
        // Check before HumHub registers the module as enabled.
        \humhub\modules\sociolog\services\SociologMigrationService::assertSupportedDatabase();
        \humhub\modules\sociolog\services\LegacyUpgradeService::assertNormalRouteSafe();
        return parent::enable();
    }

    public function getMigrationService(): \humhub\services\MigrationService
    {
        return \humhub\modules\sociolog\services\SociologMigrationService::create($this);
    }

    // ============================================================
    // 🧩 Asset-Registrierung
    // ============================================================
    public function beforeAction($action)
    {
        if (Yii::$app instanceof \yii\web\Application && Yii::$app->has('view')) {
            \humhub\modules\sociolog\assets\SociologAsset::register(Yii::$app->view);
        }

        return parent::beforeAction($action);
    }

    // ============================================================
    // 🏷️ Modulname & Beschreibung
    // ============================================================
    public function getName(): string
    {
        $customName = $this->settings->get('moduleTitle', $this->moduleName);
        return Html::encode($customName ?: $this->moduleName);
    }

    public function getDescription(): string
    {
        return Yii::t(
            'SociologModule.base',
            'Modul zur Dokumentation von Beschlüssen und Grundsatzentscheiden (Logbuch).'
        );
    }

    // ============================================================
    // ⚙️ Admin-Link
    // ============================================================
    public function getConfigUrl(): string
    {
        return Url::to(['/sociolog/admin/index']);
    }

    // ============================================================
    // 🔐 Berechtigungen
    // ============================================================
    public function getPermissions($contentContainer = null): array
    {
        if ($contentContainer !== null && !$contentContainer instanceof Space) {
            return [];
        }

        return [
            new CreateEntry(),
            new UpdateEntry(),
            new DeleteEntry(),
        ];
    }

    // ============================================================
    // 🖼️ Modul-Icon (nicht geändert)
    // ============================================================
    public function getIcon(): string
    {
        return $this->getAssetsUrl() . '/icon.svg';
    }

    // ============================================================
    // 🧩 Hilfsfunktion
    // ============================================================
    public static function getModuleTitle(): string
    {
        $module = Yii::$app->getModule('sociolog', false);
        if ($module && $module->settings) {
            return Html::encode($module->settings->get('moduleTitle', 'Logbuch'));
        }
        return 'Logbuch';
    }

    public function getCustomLabel(string $setting, string $fallback): string
    {
        $value = trim((string)$this->settings->get($setting, ''));
        if ($value === '') {
            return $fallback;
        }

        // Gespeicherte Standardbezeichnungen folgen der aktuellen Sprache.
        // Frei eingegebene Bezeichnungen bleiben unverändert, da Yii bei
        // unbekannten Übersetzungsschlüsseln den Ausgangstext zurückgibt.
        return Yii::t('SociologModule.base', $value);
    }

    // ============================================================
    // 🧭 CLI Namespace
    // ============================================================
    public function getConsoleControllerNamespace(): string
    {
        return 'humhub\modules\sociolog\commands';
    }
    
    }
