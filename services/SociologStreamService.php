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

        ReadAccess::apply($entry);
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
     * Prüft die bereits durch ContentActiveRecord erledigte Stream-Bereinigung.
     * Kompatibilitätscallback; löst keine erneute Eintrags-/Content-Löschung aus.
     */
    public static function onAfterDelete(Entry $entry): void
    {
        try {
            // Keine gecachte Content-Relation verwenden: sie kann nach dem
            // Löschen noch auf das alte Objekt zeigen. Native Bereinigung bleibt
            // Aufgabe von ContentActiveRecord::afterDelete(), keine Rekursion.
            if (Content::find()->where([
                'object_model' => Entry::getObjectModel(),
                'object_id' => (int)$entry->id,
            ])->exists()) {
                Yii::warning("Content für Entry #{$entry->id} noch vorhanden; nativen Eintrags-Lifecycle prüfen.", 'sociolog.stream');
                return;
            }
            Yii::info("Stream-Bereinigung für Sociolog-Entry #{$entry->id} abgeschlossen.", 'sociolog.stream');
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
