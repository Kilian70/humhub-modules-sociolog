<?php

namespace humhub\modules\sociolog\services;

use Yii;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\space\models\Space;
use humhub\modules\content\models\Content;

/**
 * ============================================================
 * 📢 SociologStreamService
 * ------------------------------------------------------------
 * Erstellt oder aktualisiert automatisch Stream-Einträge
 * für Sociolog-Beschlüsse. Sichtbar im zugehörigen Space.
 * ============================================================
 */
class SociologStreamService
{
    /**
     * Wird nach dem Speichern eines Eintrags aufgerufen.
     * Erstellt oder aktualisiert den Stream-Post im richtigen Space.
     */
public static function onAfterSave(Entry $entry): void
{
    try {

        Yii::info(
            "SociologStreamService::onAfterSave Entry #{$entry->id}",
            'sociolog.stream'
        );

        $space = self::findSpaceForEntry($entry);

        if (!$space || !$entry->content) {
            Yii::warning(
			"Kein Space für Organ-ID '{$entry->getDecisionOrgan()}' gefunden.",
			'sociolog.stream'
		);
            return;
        }

        // Container nur setzen wenn noch keiner vorhanden ist
        if ($entry->content->getContainer() === null) {
            $entry->content->setContainer($space);
        }

        $entry->content->visibility = Content::VISIBILITY_PUBLIC;
        $entry->content->state = Content::STATE_PUBLISHED;

        // Content-Felder werden kontrolliert gesetzt; Rückgabewert trotzdem
        // prüfen, damit kein falscher Erfolg protokolliert wird.
        if (!$entry->content->save(false)) {
            Yii::error(
                'Sociolog Content konnte nicht gespeichert werden: '
                . json_encode($entry->content->getErrors()),
                'sociolog.stream'
            );
            return;
        }

        Yii::info(
            "Stream aktualisiert im Space '{$space->name}' (Entry #{$entry->id})",
            'sociolog.stream'
        );

    } catch (\Throwable $e) {

        Yii::error(
            "SociologStreamService Fehler: " . $e->getMessage(),
            'sociolog.stream'
        );
    }
}

    /**
     * Wird nach dem Löschen eines Eintrags aufgerufen.
     * Entfernt den zugehörigen Stream-Beitrag.
     */
    public static function onAfterDelete(Entry $entry): void
    {
        try {
            if ($entry->content) {
                $entry->content->delete();
                Yii::info("🗑️ Stream-Eintrag gelöscht für Sociolog-Entry #{$entry->id}", 'sociolog.stream');
            }
        } catch (\Throwable $e) {
            Yii::error("💥 SociologStreamService::onAfterDelete Fehler: " . $e->getMessage(), 'sociolog.stream');
        }
    }

    /**
     * Hilfsfunktion: passenden Space über Organ-Namen finden.
     */
    private static function findSpaceForEntry(Entry $entry): ?Space
	{
		$spaceId = $entry->getDecisionOrgan();
	
		if (!$spaceId) {
			return null;
		}
	
		return Space::findOne((int)$spaceId);
	}
}
