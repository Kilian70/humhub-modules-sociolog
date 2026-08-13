<?php

namespace humhub\modules\sociolog\services;

use RuntimeException;
use Yii;
use humhub\modules\content\models\Content;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\EntryFlow;
use humhub\modules\sociolog\models\Organ;
use humhub\modules\sociolog\models\Protocol;
use humhub\modules\user\models\User;

/**
 * Preserves institutional logbook records when HumHub permanently deletes a user.
 */
final class SociologUserDeletionService
{
    public static function preserveEntriesForUser(User $user): int
    {
        $module = Yii::$app->getModule('sociolog', false);
        if (!$module || !(bool)$module->settings->get('preserveEntriesOnUserDelete', false)) {
            return 0;
        }

        $userId = (int)$user->id;
        if ($userId <= 0 || !self::hasSociologReferences($userId)) {
            return 0;
        }

        $archiveUserId = (int)$module->settings->get('archiveUserId', 0);
        $archiveUser = $archiveUserId > 0 ? User::findOne($archiveUserId) : null;

        if (!$archiveUser || (int)$archiveUser->status !== User::STATUS_ENABLED) {
            throw new RuntimeException(
                'Sociolog: Benutzerlöschung abgebrochen, weil kein aktives Archivkonto konfiguriert ist.'
            );
        }

        if ($archiveUserId === $userId) {
            throw new RuntimeException(
                'Sociolog: Das konfigurierte Archivkonto kann nicht gelöscht werden. Bitte zuerst ein anderes Archivkonto auswählen.'
            );
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $objectModel = Entry::getObjectModel();

            // Dieser Schritt muss zuerst erfolgen: HumHub löscht anschließend alle
            // Content-Datensätze, deren created_by noch auf den Benutzer verweist.
            $protected = Content::updateAll(
                ['created_by' => $archiveUserId],
                ['object_model' => $objectModel, 'created_by' => $userId]
            );
            Content::updateAll(
                ['updated_by' => $archiveUserId],
                ['object_model' => $objectModel, 'updated_by' => $userId]
            );

            Entry::updateAll(['created_by' => $archiveUserId], ['created_by' => $userId]);
            Entry::updateAll(['updated_by' => $archiveUserId], ['updated_by' => $userId]);
            Protocol::updateAll(['created_by' => $archiveUserId], ['created_by' => $userId]);
            EntryFlow::updateAll(['created_by' => $archiveUserId], ['created_by' => $userId]);
            Organ::updateAll(['created_by' => $archiveUserId], ['created_by' => $userId]);
            Organ::updateAll(['updated_by' => $archiveUserId], ['updated_by' => $userId]);

            $transaction->commit();

            Yii::warning(
                "Sociolog: {$protected} Logbuch-Inhalte vor dem Löschen von Benutzer #{$userId} "
                . "auf Archivkonto #{$archiveUserId} übertragen.",
                'sociolog.userDeletion'
            );

            return (int)$protected;
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            Yii::error($exception, 'sociolog.userDeletion');
            throw $exception;
        }
    }

    private static function hasSociologReferences(int $userId): bool
    {
        $objectModel = Entry::getObjectModel();

        return Content::find()
            ->where(['object_model' => $objectModel, 'created_by' => $userId])
            ->exists()
            || Entry::find()->where([
                'or',
                ['created_by' => $userId],
                ['updated_by' => $userId],
            ])->exists()
            || Protocol::find()->where(['created_by' => $userId])->exists()
            || EntryFlow::find()->where(['created_by' => $userId])->exists()
            || Organ::find()->where([
                'or',
                ['created_by' => $userId],
                ['updated_by' => $userId],
            ])->exists();
    }
}
