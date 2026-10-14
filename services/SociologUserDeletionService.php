<?php

namespace humhub\modules\sociolog\services;

use RuntimeException;
use Yii;
use humhub\modules\content\models\Content;
use humhub\modules\file\models\File;
use humhub\modules\file\models\FileHistory;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\EntryFlow;
use humhub\modules\sociolog\models\EntryReview;
use humhub\modules\sociolog\models\Organ;
use humhub\modules\sociolog\models\Protocol;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\space\helpers\MembershipHelper;
use humhub\modules\user\models\User;

/**
 * Preserves institutional logbook records when HumHub permanently deletes a user.
 */
final class SociologUserDeletionService
{
    /**
     * Must run before HumHub's User::softDelete(), which deletes owned Spaces
     * before it raises any user-deletion event. The native admin form transfers
     * ownership itself when deleteSpaces is false; only that form may defer this
     * check until the job starts. Queue execution always checks again.
     */
    public static function assertDeletionAllowed(User $user, bool $spacesWillBeTransferred = false): void
    {
        // Native account forms and fresh queue workers may not have loaded us yet.
        // The application only registers enabled modules; load that configuration.
        $module = Yii::$app->getModule('sociolog');
        if (!$module || !(bool)$module->settings->getUncached('preserveEntriesOnUserDelete', false)) {
            return;
        }

        $userId = (int)$user->id;
        $archiveUserId = (int)$module->settings->getUncached('archiveUserId', 0);
        // Protect the archive even before it has its first logbook reference.
        if ($userId > 0 && $archiveUserId === $userId) {
            throw new RuntimeException('Sociolog: Das konfigurierte Archivkonto kann nicht gelöscht werden. Bitte zuerst ein anderes Archivkonto auswählen.');
        }

        $ownsLogbookSpace = self::ownsLogbookSpace($user);
        if ($userId <= 0 || (!self::hasSociologReferences($userId) && !$ownsLogbookSpace)) {
            return;
        }

        $archiveUser = $archiveUserId > 0 ? User::findOne($archiveUserId) : null;
        if (!$archiveUser || (int)$archiveUser->status !== User::STATUS_ENABLED) {
            throw new RuntimeException('Sociolog: Benutzerlöschung abgebrochen, weil kein aktives Archivkonto konfiguriert ist.');
        }

        if ($ownsLogbookSpace && !$spacesWillBeTransferred) {
            throw new RuntimeException('Sociolog: Dieser Benutzer besitzt einen Logbuch-Space. Bitte zuerst dessen Eigentum übergeben oder beim Löschen die Option zum Löschen der Spaces ausschalten. Die Logbuchdaten bleiben erhalten.');
        }
    }

    /** Safe entry point for maintenance scripts; never call the raw Core deletion first. */
    public static function deleteUser(User $user, bool $permanently = true)
    {
        self::assertDeletionAllowed($user);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            self::preserveEntriesForUser($user);
            $result = $permanently ? $user->delete() : $user->softDelete();
            if ($result === false) {
                throw new RuntimeException('Sociolog: Benutzerlöschung wurde abgebrochen.');
            }
            $transaction->commit();
            return $result;
        } catch (\Throwable $error) {
            $transaction->rollBack();
            throw $error;
        }
    }

    /** Keep the original Core job, including its own deletion implementation. */
    public static function runDeletionJob($job): void
    {
        $user = User::findOne((int)$job->user_id);
        if ($user === null) {
            return;
        }
        self::assertDeletionAllowed($user);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            self::preserveEntriesForUser($user);
            $job->run();
            $transaction->commit();
        } catch (\Throwable $error) {
            $transaction->rollBack();
            throw $error;
        }
    }

    public static function isProtectedArchive(User $user): bool
    {
        $module = Yii::$app->getModule('sociolog');
        return $module !== null
            && (bool)$module->settings->getUncached('preserveEntriesOnUserDelete', false)
            && (int)$user->id > 0
            && (int)$module->settings->getUncached('archiveUserId', 0) === (int)$user->id;
    }

    public static function preserveEntriesForUser(User $user): int
    {
        $module = Yii::$app->getModule('sociolog');
        if (!$module || !(bool)$module->settings->getUncached('preserveEntriesOnUserDelete', false)) {
            return 0;
        }

        self::assertDeletionAllowed($user);
        $userId = (int)$user->id;
        if ($userId <= 0 || !self::hasSociologReferences($userId)) {
            return 0;
        }

        $archiveUserId = (int)$module->settings->getUncached('archiveUserId', 0);
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
            EntryReview::updateAll(['created_by' => $archiveUserId], ['created_by' => $userId]);
            Organ::updateAll(['created_by' => $archiveUserId], ['created_by' => $userId]);
            Organ::updateAll(['updated_by' => $archiveUserId], ['updated_by' => $userId]);

            // Core's user-deletion handler also deletes uploads by their author.
            // Only transfer files assigned to existing logbook entries; preserve
            // unrelated files' normal deletion semantics and never touch bytes.
            $fileIds = self::logbookFiles()->select('id');
            File::updateAll(['created_by' => $archiveUserId], ['and',
                ['created_by' => $userId], self::logbookFileCondition(),
            ]);
            File::updateAll(['updated_by' => $archiveUserId], ['and',
                ['updated_by' => $userId], self::logbookFileCondition(),
            ]);
            FileHistory::updateAll(['created_by' => $archiveUserId], ['and',
                ['created_by' => $userId], ['in', 'file_id', $fileIds],
            ]);

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

    private static function logbookFiles(): \yii\db\ActiveQuery
    {
        return File::find()->where(self::logbookFileCondition());
    }

    private static function logbookFileCondition(): array
    {
        return ['and', ['object_model' => Entry::getObjectModel()],
            ['in', 'object_id', Entry::find()->select('id')]];
    }

    private static function ownsLogbookSpace(User $user): bool
    {
        if ((int)$user->id <= 0) {
            return false;
        }
        foreach (MembershipHelper::getOwnSpaces($user, false) as $space) {
            $spaceId = (int)$space->id;
            if (SpaceConfig::find()->where(['space_id' => $spaceId, 'enabled' => 1])->exists()
                || Entry::find()->where(['or',
                    ['organ' => $spaceId],
                    ['current_organ' => $spaceId],
                    ['forwarded_to' => $spaceId],
                ])->exists()) {
                return true;
            }
            $containerId = (int)$space->contentcontainer_id;
            if (Content::find()->where([
                'object_model' => Entry::getObjectModel(),
                'contentcontainer_id' => $containerId,
            ])->exists()
                || \humhub\modules\sociolog\models\ReadScope::find()->where(['original_container_id' => $containerId])->exists()) {
                return true;
            }
        }
        return false;
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
            || EntryReview::find()->where(['created_by' => $userId])->exists()
            || Organ::find()->where([
                'or',
                ['created_by' => $userId],
                ['updated_by' => $userId],
            ])->exists()
            || self::logbookFiles()->andWhere(['or',
                ['created_by' => $userId], ['updated_by' => $userId],
            ])->exists()
            || FileHistory::find()->where(['created_by' => $userId])
                ->andWhere(['in', 'file_id', self::logbookFiles()->select('id')])->exists();
    }
}
