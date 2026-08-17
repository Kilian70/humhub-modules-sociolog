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
use humhub\modules\sociolog\services\SociologUserDeletionService;

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
    /**
     * Überträgt institutionelle Logbuchdaten vor einer vollständigen
     * Benutzerlöschung auf das konfigurierte Archivkonto.
     */
    public static function onUserDelete($event): void
    {
        if ($event->sender instanceof User) {
            SociologUserDeletionService::preserveEntriesForUser($event->sender);
        }
    }

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
        $sortOrder = (int)($module?->settings->get('mainMenuSortOrder', 350) ?? 350);

        $event->sender->addEntry(new MenuLink([
            'id' => 'topmenu-sociolog',
            'label' => $title,
            'url' => ['/sociolog/entry/index'],
            'icon' => 'fa-book',
            'sortOrder' => $sortOrder,
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

        if ($entry->historicalImport) {
            return;
        }

        $entryKey = (int)$entry->id;
        if (isset(self::$processedEntries[$entryKey])) {
            // Pro Request höchstens eine Benachrichtigungsrunde je Eintrag –
            // auch wenn derselbe Datensatz über mehrere Modellinstanzen gespeichert wird.
            return;
        }
        self::$processedEntries[$entryKey] = true;

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

        $actor = Yii::$app->user->identity ?? null;
        if (!$actor instanceof User) {
            Yii::warning(
                "Sociolog: Benachrichtigung für Entry #{$entry->id} ohne handelnden Benutzer übersprungen.",
                'sociolog'
            );
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

        $userQuery = User::find()
            ->where(['user.status' => User::STATUS_ENABLED])
            ->andWhere(['<>', 'user.id', (int)$actor->id]);

        if (!empty($groupIds)) {
            $userQuery
                ->joinWith('groupUsers')
                ->andWhere(['group_user.group_id' => $groupIds])
                ->distinct();
            $sourceDescription = 'notifyGroups (' . implode(',', $groupIds) . ')';
        } else {
            $sourceDescription = 'allen aktiven Benutzern';
        }

        $recipientCount = (int)(clone $userQuery)->count();
        Yii::info(
            "Sociolog: {$recipientCount} Empfänger aus {$sourceDescription}; handelnder Benutzer #{$actor->id} ausgeschlossen.",
            'sociolog'
        );

        // ============================================================
        // ✉️ Benachrichtigung an Empfänger senden
        // ============================================================
        $sent = 0;

		foreach ($userQuery->each(200) as $user) {
			try {
		
				$notificationClass::instance()
					->from($actor)
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
