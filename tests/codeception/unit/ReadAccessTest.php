<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\content\models\Content;
use humhub\modules\sociolog\controllers\EntryController;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\EntrySearch;
use humhub\modules\sociolog\models\ReadScope;
use humhub\modules\sociolog\models\SettingsForm;
use humhub\modules\sociolog\services\ReadAccess;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\stream\models\StreamQuery;
use humhub\modules\user\models\GroupUser;
use humhub\modules\user\models\User;
use sociolog\SociologTestCase;
use Yii;
use yii\web\NotFoundHttpException;

class ReadAccessTest extends SociologTestCase
{
    private function configure(string $mode, array $users = [], array $groups = []): void
    {
        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->set('showReviewInCalendar', false);
        $form = new SettingsForm();
        $form->loadSettings();
        $form->readerMode = $mode;
        $form->readerUsers = $users;
        $form->readerGroups = $groups;
        $this->assertTrue($form->save(), json_encode($form->getErrors()));
    }

    public function testDefaultGrantsActiveNonMembersButNeverGuests(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(2));
        $reader = User::findOne(3);
        $this->assertFalse(Space::findOne(2)->isMember($reader));
        $this->assertTrue(ReadAccess::canRead($entry, $reader));
        $this->becomeUser('User2');
        $this->assertTrue(Entry::find()->publishedOrLegacy()->andWhere(['sociolog_entry.id' => $entry->id])->exists());
        $this->logout();
        $this->assertFalse(ReadAccess::canRead($entry));
        $this->assertFalse(Entry::find()->publishedOrLegacy()->andWhere(['sociolog_entry.id' => $entry->id])->exists());
        $this->assertFalse(Content::findOne($entry->content->id)->canView());
    }

    public function testGuestVisibleSpaceUsesPersistentPrivateContainer(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $space = Space::findOne(4);
        $space->updateAttributes(['visibility' => Space::VISIBILITY_ALL]);
        $entry = $this->createEntry($space);
        $this->assertInstanceOf(ReadScope::class, $entry->content->getContainer());
        $this->assertSame((int)$space->contentcontainer_id, ReadAccess::originalContainerId($entry));
        $this->logout();
        $this->assertFalse(Content::findOne($entry->content->id)->canView());
    }

    public function testSpaceMembershipAndRevocationProtectNativeContent(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(3));
        $this->configure(ReadAccess::SPACE);
        $this->becomeUser('User2');
        $this->assertTrue(ReadAccess::canRead($entry));
        $this->assertTrue(Content::findOne($entry->content->id)->canView());
        Membership::deleteAll(['space_id' => 3, 'user_id' => 3]);
        $this->assertFalse(ReadAccess::canRead($entry));
        $this->assertFalse(Content::findOne($entry->content->id)->canView());
        $this->assertFalse(Entry::find()->publishedOrLegacy()->andWhere(['sociolog_entry.id' => $entry->id])->exists());
        $this->becomeUser('User1');
        $this->assertTrue(ReadAccess::canRead($entry));
        $this->becomeUser('Admin');
        $this->assertTrue(ReadAccess::canRead($entry));
    }

    public function testSelectedUsersGroupsAndImmediateRevocation(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(2));
        $reader = User::findOne(3);
        $this->configure(ReadAccess::SELECTED, [$reader->guid]);
        $this->becomeUser('User2');
        $this->assertTrue(ReadAccess::canRead($entry));
        $this->assertTrue(Content::findOne($entry->content->id)->canView());
        $this->configure(ReadAccess::SELECTED, [], [3]);
        $this->assertTrue(ReadAccess::canRead($entry));
        GroupUser::deleteAll(['user_id' => 3, 'group_id' => 3]);
        $this->assertFalse(ReadAccess::canRead($entry));
        $this->assertFalse(Content::findOne($entry->content->id)->canView());
        $this->assertFalse(Entry::find()->publishedOrLegacy()->andWhere(['sociolog_entry.id' => $entry->id])->exists());
    }

    public function testDeniedReaderCannotUseDetailSearchOrSingleContentStream(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(3), 'Secret reading test');
        $this->configure(ReadAccess::SELECTED);
        $this->becomeUser('User2');
        // A Space member who is neither selected nor the author must still be denied.
        $this->assertTrue(Space::findOne(3)->isMember(User::findOne(3)));
        $this->assertFalse(Content::findOne($entry->content->id)->canView());
        $search = new EntrySearch();
        $provider = $search->search(['EntrySearch' => ['query' => 'Secret reading test']]);
        $this->assertSame(0, $provider->getTotalCount());
        $query = StreamQuery::find((int)$entry->content->id);
        $this->assertSame([], $query->all());
        $controller = new EntryController('entry', Yii::$app->getModule('sociolog'));
        $csv = $controller->actionExportCsv();
        $this->assertStringNotContainsString('Secret reading test', $csv->content);
        $params = Yii::$app->request->getQueryParams();
        Yii::$app->request->setQueryParams(['contentId' => $entry->content->id, 'objectModel' => Entry::class, 'objectId' => $entry->id]);
        $oldController = Yii::$app->controller;
        try {
            $comments = new \humhub\modules\comment\controllers\CommentController('comment', Yii::$app->getModule('comment'));
            Yii::$app->controller = $comments;
            $comments->action = $comments->createAction('show');
            $comments->beforeAction($comments->action);
            $this->fail('Restricted content must not expose comments.');
        } catch (\yii\web\ForbiddenHttpException $denied) {
            $this->assertTrue(true);
        } finally {
            Yii::$app->request->setQueryParams($params);
            Yii::$app->controller = $oldController;
        }
        $this->expectException(NotFoundHttpException::class);
        $controller->actionView($entry->id);
    }

    public function testPolicyAppliesToNewEntriesAndCannotBeMadePublicThroughCore(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        Space::findOne(3)->updateAttributes(['visibility' => Space::VISIBILITY_REGISTERED_ONLY]);
        $this->configure(ReadAccess::SELECTED);
        $entry = $this->createEntry(Space::findOne(3));
        $content = Content::findOne($entry->content->id);
        $this->assertInstanceOf(ReadScope::class, $content->getContainer());
        $content->setContainer(Space::findOne(3));
        $content->visibility = Content::VISIBILITY_PUBLIC;
        $content->hidden = 0;
        $this->assertTrue($content->save(false));
        $content->refresh();
        $this->assertSame(Content::VISIBILITY_PRIVATE, (int)$content->visibility);
        $this->assertSame(1, (int)$content->hidden);
        $this->becomeUser('User2');
        $this->assertFalse(Content::findOne($content->id)->canView());
        $this->becomeUser('User1');
        $this->configure(ReadAccess::ALL);
        $this->assertTrue(Content::findOne($content->id)->canView(User::findOne(3)));
        $this->assertSame((int)Space::findOne(3)->contentcontainer_id, (int)Content::findOne($content->id)->contentcontainer_id);
    }

    public function testSettingsRejectMalformedSelectionsAndUnsafeCalendarCombination(): void
    {
        $form = new SettingsForm();
        $form->loadSettings();
        $form->readerMode = ['selected'];
        $form->readerUsers = [['bad']];
        $form->readerGroups = [['3']];
        $this->assertFalse($form->validate(['readerMode', 'readerUsers', 'readerGroups']));
        $form->readerMode = ReadAccess::ALL;
        $form->readerUsers = '';
        $form->readerGroups = '';
        $this->assertTrue($form->validate(['readerUsers', 'readerGroups']));
        $this->assertSame([], $form->readerUsers);
        $this->assertSame([], $form->readerGroups);
        $form->readerMode = ReadAccess::SELECTED;
        $form->showReviewInCalendar = true;
        $this->assertFalse($form->validate(['readerMode']));
        $this->assertSame(ReadAccess::ALL, ReadAccess::mode());
    }
    public function testLegacyRowsFollowReadPolicyWithoutCreatingContent(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $original = $this->createEntry(Space::findOne(2));
        $attributes = $original->getAttributes();
        unset($attributes['id']);
        $attributes['title'] = 'Legacy restricted record';
        Yii::$app->db->createCommand()->insert(Entry::tableName(), $attributes)->execute();
        $legacyId = (int)Yii::$app->db->getLastInsertID();
        $this->configure(ReadAccess::SPACE);
        $this->becomeUser('User2');
        $this->assertFalse(Entry::find()->publishedOrLegacy()->andWhere(['sociolog_entry.id' => $legacyId])->exists());
        $this->configure(ReadAccess::SELECTED, [User::findOne(3)->guid]);
        $this->assertTrue(Entry::find()->publishedOrLegacy()->andWhere(['sociolog_entry.id' => $legacyId])->exists());
        $this->assertFalse(Content::find()->where(['object_model' => Entry::class, 'object_id' => $legacyId])->exists());
    }

    public function testFailedSynchronizationRollsBackSettingsAndContent(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $space = Space::findOne(3);
        $space->updateAttributes(['visibility' => Space::VISIBILITY_REGISTERED_ONLY]);
        $entry = $this->createEntry($space);
        $oldContainer = (int)$entry->content->contentcontainer_id;
        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->set('showReviewInCalendar', false);
        $form = new SettingsForm();
        $form->loadSettings();
        $form->readerMode = ReadAccess::SELECTED;
        $reject = static function ($event): void {
            if ($event->sender->object_model === Entry::class) {
                throw new \RuntimeException('Simulated synchronization failure');
            }
        };
        \yii\base\Event::on(Content::class, Content::EVENT_BEFORE_UPDATE, $reject);
        try {
            $this->assertFalse($form->save());
        } finally {
            \yii\base\Event::off(Content::class, Content::EVENT_BEFORE_UPDATE, $reject);
        }
        $this->assertSame(ReadAccess::ALL, ReadAccess::mode());
        $this->assertSame(ReadAccess::ALL, $settings->getUncached('readerMode', ReadAccess::ALL));
        $this->assertSame($oldContainer, (int)Content::findOne($entry->content->id)->contentcontainer_id);
        $this->assertFalse(ReadScope::find()->where(['entry_id' => $entry->id])->exists());
    }

    public function testSelectionRevocationCannotBeHiddenBySettingsCache(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(2));
        $reader = User::findOne(3);
        $this->configure(ReadAccess::SELECTED, [$reader->guid]);
        $settings = Yii::$app->getModule('sociolog')->settings;
        $this->assertSame([$reader->guid], $settings->getSerialized('readerUsers'));
        $this->assertTrue(ReadAccess::canRead($entry, $reader));
        // A concurrent request has committed a revocation; this instance still
        // holds the old cached setting. Authorization must query the stored ACL.
        Yii::$app->db->createCommand()->update('setting', ['value' => '[]'], ['module_id' => 'sociolog', 'name' => 'readerUsers'])->execute();
        $this->assertSame([$reader->guid], $settings->getSerialized('readerUsers'));
        $this->assertFalse(ReadAccess::canRead($entry, $reader));
        $this->assertFalse(Content::findOne($entry->content->id)->canView($reader));
    }

}
