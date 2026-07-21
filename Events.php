<?php

namespace humhub\modules\sociolog;

use Yii;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\notifications\EntryCreated;
use humhub\modules\sociolog\notifications\EntryUpdated;
use humhub\modules\user\models\User;
use humhub\modules\dashboard\widgets\Sidebar;
use humhub\modules\sociolog\widgets\LatestEntries;

/**
 * ============================================================
 * 🔹 Sociolog – Events
 * ------------------------------------------------------------
 * - Registriert Menüeintrag im TopMenu
 * - Fügt Dashboard-Widget hinzu
 * - Sendet Benachrichtigungen an definierte Gruppen
 * - Führt stündlichen Cron-Statuslauf aus
 * ============================================================
 */
class Events
{
    /** 🔹 Menüeintrag im TopMenu hinzufügen (HumHub 1.18+) */
    public static function onTopMenuInit($event)
    {
        if (!Yii::$app->hasModule('sociolog') || Yii::$app->user->isGuest) {
            return;
        }

        // getEntryById() ist in HumHub 1.18 und 1.19 verfügbar.
        if ($event->sender->getEntryById('topmenu-sociolog') !== null) {
            return;
        }

        $module = Yii::$app->getModule('sociolog');
        $title = $module?->settings->get('moduleTitle', 'Logbuch') ?? 'Logbuch';

        $event->sender->addEntry(new MenuLink([
            'id' => 'topmenu-sociolog',
            'label' => $title,
            'url' => ['/sociolog/entry/index'],
            'icon' => 'fa-book',
            'sortOrder' => 350,
            'isActive' => Yii::$app->controller?->module?->id === 'sociolog',
        ]));

        Yii::info('Sociolog: TopMenu-Eintrag hinzugefügt', 'sociolog');
    }

    /** 🔹 Dashboard-Widget (nur global) */
    public static function onDashboardSidebarInit($event)
    {
        $controller = Yii::$app->controller;
        if (!$controller || $controller->module->id !== 'dashboard') {
            return;
        }

        // Doppelte Registrierung verhindern
        foreach ($event->sender->widgets ?? [] as $widget) {
            if (($widget['class'] ?? '') === LatestEntries::class) {
                return;
            }
        }

        $settings  = Yii::$app->getModule('sociolog', false)?->settings;
        $sortOrder = (int)($settings?->get('widgetSortOrder', 100) ?? 100);

        $event->sender->addWidget(LatestEntries::class, [], ['sortOrder' => $sortOrder]);
        Yii::info('Sociolog: Dashboard-Widget LatestEntries hinzugefügt', 'sociolog');
    }

    /** 🔹 Notifications bei neuen/geänderten Einträgen */
    private static array $processedEntries = [];

    public static function onAfterSave($event)
    {
        /** @var Entry $entry */
        $entry = $event->sender;

        if (!$entry || !$entry->id) {
            Yii::warning('Sociolog: Kein Entry oder ID fehlt', 'sociolog');
            return;
        }

        $oid = spl_object_id($entry);
        if (isset(self::$processedEntries[$oid])) {
            // Schutz vor Doppel-Events
            return;
        }
        self::$processedEntries[$oid] = true;

        $settings = Yii::$app->getModule('sociolog')->settings;
        if (!$settings->get('notificationsEnabled', true)) {
            Yii::info('Sociolog: Benachrichtigungen deaktiviert', 'sociolog');
            return;
        }

		// Ereignistyp bestimmen
		$notificationClass = match ($event->name) {
			Entry::EVENT_AFTER_INSERT => EntryCreated::class,
			Entry::EVENT_AFTER_UPDATE => EntryUpdated::class,
			default => null,
		};
		
		if (!$notificationClass) {
			return;
		}
		
        // ============================================================
        // 🎯 Empfänger bestimmen (notifyGroups oder alle Benutzer)
        // ============================================================
        $module   = Yii::$app->getModule('sociolog');
        $groupIds = array_filter(array_map(
            'intval',
            (array)($module->settings->getSerialized('notifyGroups') ?? [])
        ));

        if (!empty($groupIds)) {
            $users = User::find()
                ->joinWith('groupUsers')
                ->where(['group_user.group_id' => $groupIds])
                ->andWhere(['user.status' => User::STATUS_ENABLED])
                ->all();
            Yii::info('Sociolog: ' . count($users) . ' Empfänger aus notifyGroups (' . implode(',', $groupIds) . ')', 'sociolog');
        } else {
            $users = User::find()->where(['status' => User::STATUS_ENABLED])->all();
            Yii::info('Sociolog: notifyGroups leer – alle aktiven Benutzer werden benachrichtigt', 'sociolog');
        }

        // ============================================================
        // ✉️ Benachrichtigung an Empfänger senden
        // ============================================================
        $sent = 0;

		foreach ($users as $user) {
		
			if ($entry->created_by == $user->id) {
				continue;
			}
		
			try {
		
				$notificationClass::instance()
					->from(Yii::$app->user->identity)
					->about($entry)
					->send($user);
		
				$sent++;
		
			} catch (\Throwable $e) {
		
				Yii::error(
					"Sociolog: Notification an User {$user->id} fehlgeschlagen – " . $e->getMessage(),
					'sociolog'
				);
			}
		}
		
        Yii::info("Sociolog: $sent Benachrichtigungen für Entry #{$entry->id} versendet", 'sociolog');
    }
    
    public static function onCronRun()
{
    \humhub\modules\sociolog\services\SociologStatusService::run();
}
}
