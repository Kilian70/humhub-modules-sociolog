<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\admin\models\forms\UserDeleteForm;
use humhub\modules\content\models\Content;
use humhub\modules\file\models\File;
use humhub\modules\file\models\FileHistory;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\EntryFlow;
use humhub\modules\sociolog\models\EntryReview;
use humhub\modules\sociolog\models\Protocol;
use humhub\modules\sociolog\services\ReadAccess;
use humhub\modules\sociolog\services\SociologUserDeletionService;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\jobs\DeleteUser;
use humhub\modules\user\jobs\SoftDeleteUser;
use humhub\modules\user\models\GroupUser;
use humhub\modules\user\models\Password;
use humhub\modules\user\models\User;
use humhub\modules\user\models\forms\AccountDelete;
use sociolog\SociologTestCase;
use Yii;
use yii\queue\ExecEvent;
use yii\queue\Queue;

/** Exercises native forms and serialized queue execution, not only the service. */
class UserDeletionProtectionTest extends SociologTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->disableNotifications();
        $this->becomeUser('Admin');
        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->set('showReviewInCalendar', false);
        $settings->set('preserveEntriesOnUserDelete', false);
        $settings->set('readerMode', ReadAccess::ALL);
    }

    protected function tearDown(): void
    {
        Yii::$app->getModule('sociolog')->settings->set('preserveEntriesOnUserDelete', false);
        parent::tearDown();
    }

    private function syntheticUser(): User
    {
        $suffix = bin2hex(random_bytes(6));
        $user = new User(['username' => 'delete-test-' . $suffix,
            'email' => $suffix . '@example.invalid', 'status' => User::STATUS_ENABLED]);
        $this->assertTrue($user->save(false));
        return $user;
    }

    private function configureArchive(User $archive): void
    {
        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->set('archiveUserId', (int)$archive->id);
        $settings->set('preserveEntriesOnUserDelete', true);
    }

    /** Reproduce the registered-but-not-loaded state of a fresh native request. */
    private function unloadSociolog(): void
    {
        $module = Yii::$app->getModule('sociolog');
        Yii::$app->setModule('sociolog', ['class' => $module::class, 'basePath' => $module->basePath]);
        $this->assertNull(Yii::$app->getModule('sociolog', false));
    }

    public function testUnloadedModuleStillProtectsNativeFormsAndArchiveStatus(): void
    {
        $victim = $this->syntheticUser();
        [$space] = $this->ownedEntry($victim);
        $archive = $this->syntheticUser();
        $this->configureArchive($archive);
        $this->unloadSociolog();
        $form = new UserDeleteForm(['user' => $victim, 'deleteSpaces' => true, 'deleteContributions' => true]);
        $this->assertFalse($form->performDelete());
        $this->assertTrue($form->hasErrors('deleteSpaces'));
        $this->assertNotNull(Space::findOne($space->id));
        $this->unloadSociolog();
        $form = new UserDeleteForm(['user' => $archive, 'deleteSpaces' => false, 'deleteContributions' => true]);
        $this->assertFalse($form->performDelete());
        $this->assertTrue($form->hasErrors('deleteContributions'));
        $this->unloadSociolog();
        $archive->status = User::STATUS_SOFT_DELETED;
        $this->assertFalse($archive->save(false));
        $this->assertSame(User::STATUS_ENABLED, (int)User::findOne($archive->id)->status);
    }

    public function testUnloadedModuleGuardsSerializedJobsAndTransfersSoftDeleteAuthors(): void
    {
        $victim = $this->syntheticUser();
        [$space] = $this->ownedEntry($victim);
        $archive = $this->syntheticUser();
        $this->configureArchive($archive);
        foreach ([DeleteUser::class, SoftDeleteUser::class] as $class) {
            $queue = new DeletionTestQueue();
            $this->unloadSociolog();
            $queue->executeSerialized(new $class(['user_id' => $victim->id]));
            $this->assertInstanceOf(\RuntimeException::class, $queue->lastError);
            $this->assertNotNull(Space::findOne($space->id));
        }
        $author = $this->syntheticUser();
        Yii::$app->user->setIdentity($author);
        $entry = $this->createEntry(Space::findOne(4));
        $queue = new DeletionTestQueue();
        $this->unloadSociolog();
        $queue->executeSerialized(new SoftDeleteUser(['user_id' => $author->id]));
        $this->assertNull($queue->lastError);
        $this->assertSame(User::STATUS_SOFT_DELETED, (int)User::findOne($author->id)->status);
        $this->assertSame((int)$archive->id, (int)Entry::findOne($entry->id)->created_by);
        $this->assertSame((int)$archive->id, (int)Entry::findOne($entry->id)->content->created_by);
    }

    private function snapshot(User $user): array
    {
        $fresh = User::findOne($user->id);
        return [
            'user' => $fresh->getAttributes(),
            'profile' => $fresh->profile?->getAttributes(),
            'groups' => GroupUser::find()->where(['user_id' => $user->id])->orderBy('group_id')->asArray()->all(),
            'passwords' => Password::find()->where(['user_id' => $user->id])->asArray()->all(),
            'memberships' => Membership::find()->where(['user_id' => $user->id])->orderBy('space_id')->asArray()->all(),
        ];
    }

    private function ownedEntry(User $owner, string $mode = ReadAccess::ALL): array
    {
        Yii::$app->user->setIdentity($owner);
        Yii::$app->getModule('sociolog')->settings->set('readerMode', $mode);
        $space = new Space(['name' => 'Synthetic deletion protection Space', 'visibility' => Space::VISIBILITY_NONE]);
        $this->assertTrue($space->save(false));
        $entry = $this->createEntry($space, 'Retained institutional decision');
        $this->becomeUser('Admin');
        return [$space, $entry];
    }

    public function testArchiveAccountIsProtectedBeforeNativeSoftAndHardDeletion(): void
    {
        foreach ([false, true] as $hasReferences) {
            $archive = $this->syntheticUser();
            if ($hasReferences) {
                Yii::$app->user->setIdentity($archive);
                $this->createEntry(Space::findOne(4));
                $this->becomeUser('Admin');
            }
            $this->configureArchive($archive);
            $before = $this->snapshot($archive);
            foreach ([false, true] as $hard) {
                try {
                    $hard ? $archive->delete() : $archive->softDelete();
                    $this->fail('Archive account deletion must be rejected.');
                } catch (\RuntimeException $error) {
                    $this->assertStringContainsString('Archivkonto', $error->getMessage());
                }
                $this->assertSame($before, $this->snapshot($archive));
            }
        }
    }

    public function testNativeAdminAndAccountFormsCannotDeleteArchiveOrTransferItsSpaces(): void
    {
        $archive = $this->syntheticUser();
        [$space, $entry] = $this->ownedEntry($archive);
        $this->configureArchive($archive);
        $before = $this->snapshot($archive);
        $spaceBefore = Space::findOne($space->id)->getAttributes();
        $form = new UserDeleteForm(['user' => $archive, 'deleteSpaces' => false, 'deleteContributions' => true]);
        $this->assertFalse($form->performDelete());
        $this->assertTrue($form->hasErrors());
        $this->assertSame($spaceBefore, Space::findOne($space->id)->getAttributes());
        Yii::$app->user->setIdentity($archive);
        $account = new AccountDelete(['user' => $archive]);
        $this->assertFalse($account->performDelete());
        $this->assertTrue($account->hasErrors('currentPassword'));
        $this->assertSame($before, $this->snapshot($archive));
        $this->assertSame(Content::STATE_PUBLISHED, (int)Entry::findOne($entry->id)->content->state);
        $this->becomeUser('Admin');
    }

    public function testArchiveStatusSaveCannotBypassDeletionGuard(): void
    {
        $archive = $this->syntheticUser();
        $this->configureArchive($archive);
        $before = $this->snapshot($archive);
        foreach ([User::STATUS_SOFT_DELETED, 0] as $status) {
            $fresh = User::findOne($archive->id);
            $fresh->status = $status;
            $this->assertFalse($fresh->save(false));
            $this->assertTrue($fresh->hasErrors('status'));
            $this->assertSame($before, $this->snapshot($archive));
        }
    }

    public function testSpaceDeletionOptionIsRejectedWithoutChangingData(): void
    {
        $victim = $this->syntheticUser();
        [$space, $entry] = $this->ownedEntry($victim);
        $this->configureArchive($this->syntheticUser());
        $before = $this->snapshot($victim);
        $contentBefore = Content::findOne($entry->content->id)->getAttributes();
        $form = new UserDeleteForm(['user' => $victim, 'deleteSpaces' => true, 'deleteContributions' => true]);
        $this->assertFalse($form->performDelete());
        $this->assertTrue($form->hasErrors('deleteSpaces'));
        $this->assertSame($before, $this->snapshot($victim));
        $this->assertNotNull(Space::findOne($space->id));
        $this->assertSame($contentBefore, Content::findOne($entry->content->id)->getAttributes());
    }

    public function testExistingQueuedDeletionIsBlockedBeforeTouchingOwnedSpace(): void
    {
        $victim = $this->syntheticUser();
        [$space, $entry] = $this->ownedEntry($victim);
        $this->configureArchive($this->syntheticUser());
        $before = $this->snapshot($victim);
        foreach ([DeleteUser::class, SoftDeleteUser::class] as $jobClass) {
            $queue = new DeletionTestQueue();
            $queue->executeSerialized(new $jobClass(['user_id' => $victim->id]));
            $this->assertInstanceOf(\RuntimeException::class, $queue->lastError);
            $this->assertSame($before, $this->snapshot($victim));
            $this->assertNotNull(Space::findOne($space->id));
            $this->assertSame(Content::STATE_PUBLISHED, (int)Entry::findOne($entry->id)->content->state);
        }
    }

    public function testArchiveCannotBeQueuedAndOldArchiveJobIsAlsoRejected(): void
    {
        $archive = $this->syntheticUser();
        [$space] = $this->ownedEntry($archive);
        $this->configureArchive($archive);
        $before = $this->snapshot($archive);
        foreach ([DeleteUser::class, SoftDeleteUser::class] as $jobClass) {
            $queue = new DeletionTestQueue();
            try {
                $queue->push(new $jobClass(['user_id' => $archive->id]));
                $this->fail('Archive must not be queued.');
            } catch (\RuntimeException $error) {
                $this->assertStringContainsString('Archivkonto', $error->getMessage());
            }
            $this->assertSame([], $queue->messages);
            $queue->executeSerialized(new $jobClass(['user_id' => $archive->id]));
            $this->assertInstanceOf(\RuntimeException::class, $queue->lastError);
            $this->assertSame($before, $this->snapshot($archive));
            $this->assertNotNull(Space::findOne($space->id));
        }
    }

    public function testOwnershipHandoverAndHardDeletionRetainEntryHistoryAndAttachment(): void
    {
        foreach ([ReadAccess::ALL, ReadAccess::SPACE, ReadAccess::SELECTED] as $mode) {
            $victim = $this->syntheticUser();
            [$space, $entry] = $this->ownedEntry($victim, $mode);
            $archive = $this->syntheticUser();
            $this->configureArchive($archive);
            $protocol = new Protocol(['entry_id' => $entry->id, 'title' => 'Retained protocol', 'url' => 'https://example.invalid/protocol']);
            $this->assertTrue($protocol->save());
            $flow = new EntryFlow(['entry_id' => $entry->id, 'action' => 'review', 'from_organ_id' => $space->id,
                'to_organ_id' => $space->id, 'created_by' => $victim->id, 'created_at' => '2026-10-13 10:00:00']);
            $this->assertTrue($flow->save());
            $review = new EntryReview(['entry_id' => $entry->id, 'protocol_id' => $protocol->id,
                'result' => EntryReview::RESULT_CONFIRMED, 'justification' => 'Retained review',
                'created_by' => $victim->id, 'created_at' => time()]);
            $this->assertTrue($review->save());
            $file = new File(['object_model' => Entry::class, 'object_id' => $entry->id,
                'file_name' => 'synthetic-retained.txt', 'mime_type' => 'text/plain', 'public' => 0,
                'created_by' => $victim->id]);
            $this->assertTrue($file->save());
            $file->setStoredFileContent('Synthetic retained file bytes');
            $file->updateAttributes(['updated_by' => $victim->id]);
            $file = File::findOne($file->id);
            $this->assertTrue(FileHistory::createEntryForFile($file));
            $history = FileHistory::findOne(['file_id' => $file->id]);
            $this->assertNotNull($history);
            $this->assertSame((int)$victim->id, (int)$history->created_by);
            $historyHash = $history->hash_sha1;
            $originalContentId = (int)$entry->content->id;
            $originalContainerId = ReadAccess::originalContainerId($entry);
            $entryAttributes = $entry->getAttributes();
            $queue = new DeletionTestQueue();
            $originalQueue = Yii::$app->get('queue');
            Yii::$app->set('queue', $queue);
            try {
                $form = new UserDeleteForm(['user' => $victim, 'deleteSpaces' => false, 'deleteContributions' => true]);
                $this->assertTrue($form->performDelete(), json_encode($form->errors));
            } finally {
                Yii::$app->set('queue', $originalQueue);
            }
            $this->assertSame(1, count($queue->messages));
            $this->assertSame((int)Yii::$app->user->id, (int)Space::findOne($space->id)->created_by);
            $queue->executeMessage(reset($queue->messages));
            $this->assertNull($queue->lastError);
            $this->assertNull(User::findOne($victim->id));
            $kept = Entry::findOne($entry->id);
            $this->assertNotNull($kept);
            $this->assertSame($originalContentId, (int)$kept->content->id);
            $this->assertSame($originalContainerId, ReadAccess::originalContainerId($kept));
            $this->assertSame(Content::STATE_PUBLISHED, (int)$kept->content->state);
            $this->assertSame((int)$archive->id, (int)$kept->created_by);
            $this->assertSame((int)$archive->id, (int)$kept->content->created_by);
            unset($entryAttributes['created_by'], $entryAttributes['updated_by']);
            $after = $kept->getAttributes();
            unset($after['created_by'], $after['updated_by']);
            $this->assertSame($entryAttributes, $after);
            $this->assertSame($protocol->id, EntryReview::findOne($review->id)->protocol_id);
            $this->assertSame((int)$archive->id, (int)EntryReview::findOne($review->id)->created_by);
            $this->assertSame((int)$archive->id, (int)EntryFlow::findOne($flow->id)->created_by);
            $keptFile = File::findOne($file->id);
            $this->assertNotNull($keptFile);
            $store = $keptFile->store;
            $bytes = method_exists($store, 'getContent') ? $store->getContent() : file_get_contents($store->get());
            $this->assertSame('Synthetic retained file bytes', $bytes);
            $this->assertSame((int)$archive->id, (int)$keptFile->created_by);
            $this->assertSame($file->guid, $keptFile->guid);
            $this->assertSame(0, (int)$keptFile->public);
            $keptHistory = FileHistory::findOne($history->id);
            $this->assertSame((int)$archive->id, (int)$keptHistory->created_by);
            $this->assertSame($historyHash, $keptHistory->hash_sha1);
            $variant = $keptHistory->getFileVariantId();
            $historyBytes = method_exists($store, 'getContent')
                ? $store->getContent($variant) : file_get_contents($store->get($variant));
            $this->assertSame('Synthetic retained file bytes', $historyBytes);
            $this->assertTrue(File::findOne($file->id)->canView($archive));
            if ($mode !== ReadAccess::ALL) {
                $this->assertFalse(File::findOne($file->id)->canView(User::findOne(4)));
            }
            $this->assertTrue(Entry::find()->publishedOrLegacy()->andWhere(['sociolog_entry.id' => $entry->id])->exists());
            $file->delete(); // Only this synthetic test file, never an application file.
        }
    }

    public function testSoftDeletionTransfersReferencesWithoutDeletingEntry(): void
    {
        $victim = $this->syntheticUser();
        Yii::$app->user->setIdentity($victim);
        $entry = $this->createEntry(Space::findOne(4));
        $this->becomeUser('Admin');
        $archive = $this->syntheticUser();
        $this->configureArchive($archive);
        $queue = new DeletionTestQueue();
        $queue->executeSerialized(new SoftDeleteUser(['user_id' => $victim->id]));
        $this->assertNull($queue->lastError);
        $this->assertSame(User::STATUS_SOFT_DELETED, (int)User::findOne($victim->id)->status);
        $this->assertSame((int)$archive->id, (int)Entry::findOne($entry->id)->created_by);
        $this->assertSame(Content::STATE_PUBLISHED, (int)Entry::findOne($entry->id)->content->state);
    }

    public function testMissingArchiveIsRejectedBeforeNativeFormOrJobChangesData(): void
    {
        $victim = $this->syntheticUser();
        [$space, $entry] = $this->ownedEntry($victim);
        $this->configureArchive(User::findOne(5)); // Disabled fixture account.
        $before = $this->snapshot($victim);
        $form = new UserDeleteForm(['user' => $victim, 'deleteSpaces' => false, 'deleteContributions' => true]);
        $this->assertFalse($form->performDelete());
        $this->assertTrue($form->hasErrors());
        $queue = new DeletionTestQueue();
        $queue->executeSerialized(new DeleteUser(['user_id' => $victim->id]));
        $this->assertInstanceOf(\RuntimeException::class, $queue->lastError);
        $this->assertSame($before, $this->snapshot($victim));
        $this->assertSame((int)$victim->id, (int)Space::findOne($space->id)->created_by);
        $this->assertSame((int)$victim->id, (int)Entry::findOne($entry->id)->created_by);
    }

    public function testSafeMaintenanceEntryPointRejectsOwnedSpaceBeforeCoreDeletion(): void
    {
        $victim = $this->syntheticUser();
        [$space, $entry] = $this->ownedEntry($victim);
        $this->configureArchive($this->syntheticUser());
        $before = $this->snapshot($victim);
        try {
            SociologUserDeletionService::deleteUser($victim);
            $this->fail('Space ownership must be handed over first.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Logbuch-Space', $error->getMessage());
        }
        $this->assertSame($before, $this->snapshot($victim));
        $this->assertNotNull(Space::findOne($space->id));
        $this->assertSame(Content::STATE_PUBLISHED, (int)Entry::findOne($entry->id)->content->state);
    }

    public function testAttachmentOnlyAuthorIsTransferredAndUnrelatedFileIsNotArchived(): void
    {
        $entry = $this->createEntry(Space::findOne(4));
        $victim = $this->syntheticUser();
        $archive = $this->syntheticUser();
        $this->configureArchive($archive);
        $file = new File(['object_model' => Entry::class, 'object_id' => $entry->id,
            'file_name' => 'synthetic-only-reference.txt', 'mime_type' => 'text/plain', 'created_by' => $victim->id]);
        $this->assertTrue($file->save());
        $file->setStoredFileContent('Synthetic file-only reference');
        $outside = new File(['object_model' => User::class, 'object_id' => 4,
            'file_name' => 'synthetic-unrelated.txt', 'mime_type' => 'text/plain', 'created_by' => $victim->id]);
        $this->assertTrue($outside->save());
        $outside->setStoredFileContent('Unrelated synthetic file');
        $queue = new DeletionTestQueue();
        $queue->executeSerialized(new DeleteUser(['user_id' => $victim->id]));
        $this->assertNull($queue->lastError);
        $this->assertNull(User::findOne($victim->id));
        $this->assertSame((int)$archive->id, (int)File::findOne($file->id)->created_by);
        $this->assertNotNull(Entry::findOne($entry->id));
        $this->assertNull(File::findOne($outside->id));
        $file->delete();
    }

    public function testDisabledPreservationLeavesNativeJobSemanticsIntact(): void
    {
        $victim = $this->syntheticUser();
        Yii::$app->user->setIdentity($victim);
        $entry = $this->createEntry(Space::findOne(4));
        $this->becomeUser('Admin');
        Yii::$app->getModule('sociolog')->settings->set('preserveEntriesOnUserDelete', false);
        $queue = new DeletionTestQueue();
        $queue->executeSerialized(new DeleteUser(['user_id' => $victim->id]));
        $this->assertNull($queue->lastError);
        $this->assertNull(User::findOne($victim->id));
        $this->assertNull(Entry::findOne($entry->id));
    }
}

/** Runs the real Yii queue execution pipeline without an external worker. */
class DeletionTestQueue extends Queue
{
    public array $messages = [];
    public ?\Throwable $lastError = null;

    public function init()
    {
        parent::init();
        $this->on(self::EVENT_AFTER_ERROR, function (ExecEvent $event): void {
            $this->lastError = $event->error;
        });
    }

    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        $id = 'sociolog-test-' . bin2hex(random_bytes(6));
        $this->messages[$id] = $message;
        return $id;
    }

    public function status($id)
    {
        return isset($this->messages[$id]) ? self::STATUS_WAITING : self::STATUS_DONE;
    }

    public function executeSerialized($job): void
    {
        $this->executeMessage($this->serializer->serialize($job));
    }

    public function executeMessage(string $message): void
    {
        $this->lastError = null;
        $this->handleMessage('sociolog-synthetic-job', $message, 300, 1);
    }
}
