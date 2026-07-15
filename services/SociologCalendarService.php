<?php

namespace humhub\modules\sociolog\services;

use Yii;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\space\models\Space;
use humhub\modules\content\models\Content;

/**
 * 📅 SociologCalendarService
 * Erstellt / aktualisiert automatisch Kalender-Einträge aus Sociolog-Einträgen.
 * Sichtbar nur im Space-Kalender (nicht im Stream).
 */
class SociologCalendarService
{

    private static function isAvailable(): bool
    {
        return Yii::$app->getModule('calendar', false) !== null
            && class_exists(CalendarEntry::class);
    }

    public static function onAfterSave(Entry $entry): void
    {
        if (!self::isAvailable()) {
            return;
        }

        try {

            Yii::info("==== SociologCalendarService::onAfterSave für Entry #{$entry->id} ====", 'sociolog.calendar');

            static $handled = [];

            if (isset($handled[$entry->id])) {
                Yii::info("↩️ CalendarService bereits verarbeitet – übersprungen", 'sociolog.calendar');
                return;
            }

            $handled[$entry->id] = true;

            $uid = "sociolog-entry-{$entry->id}";


            /**
             * ------------------------------------------------------------
             * review_date entfernt → alle Kalender-Einträge löschen
             * ------------------------------------------------------------
             */
            if (empty($entry->review_date)) {

                Yii::info("⏭️ Kein Überprüfungsdatum – Kalendereintrag wird entfernt.", 'sociolog.calendar');

                $existingEntries = CalendarEntry::find()
                    ->where(['uid' => $uid])
                    ->all();

                foreach ($existingEntries as $existing) {

                    Yii::info(
                        "🗑️ Kalendereintrag gelöscht (#{$existing->id})",
                        'sociolog.calendar'
                    );

                    $existing->delete();
                }

                return;
            }


            /**
             * ------------------------------------------------------------
             * Ziel-Space bestimmen
             * ------------------------------------------------------------
             */
            $space = self::findSpaceForEntry($entry);

            if (!$space) {

                Yii::warning(
                    "⚠️ Kein Space gefunden für Entry #{$entry->id}",
                    'sociolog.calendar'
                );

                return;
            }

            Yii::info(
                "→ Space gefunden: {$space->name} (ID {$space->id})",
                'sociolog.calendar'
            );


            /**
             * ------------------------------------------------------------
             * vorhandene Einträge prüfen / falsche löschen
             * ------------------------------------------------------------
             */
            $calendar = null;

            $existingEntries = CalendarEntry::find()
                ->where(['uid' => $uid])
                ->all();

            foreach ($existingEntries as $existing) {

                if (!$existing->content || !$existing->content->container) {
                    continue;
                }

                if ((int)$existing->content->container->id === (int)$space->id) {

                    // richtiger Space → behalten
                    $calendar = $existing;

                } else {

                    Yii::info(
                        "🗑️ Alter Kalendereintrag gelöscht (#{$existing->id})",
                        'sociolog.calendar'
                    );

                    $existing->delete();
                }
            }


            /**
             * ------------------------------------------------------------
             * neuen Eintrag erstellen falls nötig
             * ------------------------------------------------------------
             */
            $isNew = false;

            if (!$calendar) {

                $calendar = new CalendarEntry();
                $calendar->uid = $uid;

                $calendar->content->container = $space;
                $calendar->content->created_by = Yii::$app->user->id ?? 1;
                $calendar->content->created_at = date('Y-m-d H:i:s');
                $calendar->content->visibility = Content::VISIBILITY_PRIVATE;

                $isNew = true;

                Yii::info("➕ Neuer Kalender-Eintrag wird erstellt", 'sociolog.calendar');
            }


            /**
             * ------------------------------------------------------------
             * Daten setzen
             * ------------------------------------------------------------
             */
            $link = Yii::$app->urlManager->createAbsoluteUrl([
                '/sociolog/entry/view',
                'id' => $entry->id,
            ]);

            $calendar->title = "Überprüfung: " . ($entry->title ?: "Eintrag #{$entry->id}");

            $calendar->description =
                "Automatisch aus Sociolog (Eintrag #{$entry->id})\n\n👉 {$link}";

            $calendar->start_datetime = $entry->review_date . ' 00:00:00';
            $calendar->end_datetime   = $entry->review_date . ' 23:59:59';

            $calendar->all_day = 1;


            /**
             * ------------------------------------------------------------
             * speichern
             * ------------------------------------------------------------
             */
            if (!$calendar->save()) {

                Yii::error(
                    "❌ CalendarEntry-Fehler: " . json_encode($calendar->getErrors()),
                    'sociolog.calendar'
                );

                return;
            }

            Yii::info(
                "✔️ CalendarEntry gespeichert (#{$calendar->id})",
                'sociolog.calendar'
            );


            /**
             * ------------------------------------------------------------
             * nur im Kalender anzeigen
             * ------------------------------------------------------------
             */
            $calendar->content->stream_channel = '';
            $calendar->content->visibility = Content::VISIBILITY_PRIVATE;

            if (!$calendar->content->save(false)) {
                Yii::error(
                    'Kalender-Content konnte nicht gespeichert werden: '
                    . json_encode($calendar->content->getErrors()),
                    'sociolog.calendar'
                );
                return;
            }


            Yii::info(
                ($isNew ? "➕ Neu erstellt" : "♻️ Aktualisiert")
                . " – Kalendereintrag für Entry #{$entry->id}",
                'sociolog.calendar'
            );

        } catch (\Throwable $e) {

            Yii::error(
                "💥 Fehler in SociologCalendarService::onAfterSave: "
                . $e->getMessage(),
                'sociolog.calendar'
            );
        }
    }



	/**
	 * ------------------------------------------------------------
	 * Entry gelöscht → Kalender löschen
	 * ------------------------------------------------------------
	 */
public static function onAfterDelete(Entry $entry): void
{
    if (!self::isAvailable()) {
        return;
    }

    try {

        Yii::info("CalendarService Delete gestartet für Entry #{$entry->id}", 'sociolog.calendar');
        Yii::info("UID gesucht: sociolog-entry-{$entry->id}", 'sociolog.calendar');

        $uid = "sociolog-entry-{$entry->id}";

        $existingEntries = CalendarEntry::find()
            ->where(['uid' => $uid])
            ->all();

        foreach ($existingEntries as $existing) {

            Yii::info(
                "🗑️ Kalendereintrag gelöscht (#{$existing->id})",
                'sociolog.calendar'
            );

            $existing->delete();
        }

    } catch (\Throwable $e) {

        Yii::error(
            "💥 Fehler in SociologCalendarService::onAfterDelete: " . $e->getMessage(),
            'sociolog.calendar'
        );
    }
    }
    
    public static function deleteByEntryId(int $entryId): void
{
    if (!self::isAvailable()) {
        return;
    }

    try {

        Yii::info(
            "CalendarService DeleteByEntryId gestartet für Entry #{$entryId}",
            'sociolog.calendar'
        );

        $uid = "sociolog-entry-{$entryId}";

        $existingEntries = CalendarEntry::find()
            ->where(['uid' => $uid])
            ->all();

        foreach ($existingEntries as $existing) {

            Yii::info(
                "🗑️ Kalendereintrag gelöscht (#{$existing->id})",
                'sociolog.calendar'
            );

            $existing->delete();
        }

    } catch (\Throwable $e) {

        Yii::error(
            "💥 Fehler in deleteByEntryId(): " . $e->getMessage(),
            'sociolog.calendar'
        );
    }
}

    /**
     * ------------------------------------------------------------
     * Ziel-Space bestimmen
     * ------------------------------------------------------------
     */
    private static function findSpaceForEntry(Entry $entry): ?Space
    {
        $spaceId = (int)$entry->current_organ ?: (int)$entry->organ;

        if (!$spaceId) {

            Yii::warning(
                "⚠️ Kein Entscheidungsorgan gesetzt (Entry #{$entry->id})",
                'sociolog.calendar'
            );

            return null;
        }

        $space = Space::findOne($spaceId);

        if (!$space) {

            Yii::warning(
                "⚠️ Space {$spaceId} existiert nicht (Entry #{$entry->id})",
                'sociolog.calendar'
            );

            return null;
        }

        return $space;
    }
}
