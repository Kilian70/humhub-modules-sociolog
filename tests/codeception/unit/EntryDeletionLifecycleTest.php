<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\sociolog\controllers\EntryController;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\ReadScope;
use humhub\modules\sociolog\services\SociologStreamService;
use humhub\modules\space\models\Space;
use sociolog\SociologTestCase;
use Yii;
use yii\base\Event;

class EntryDeletionLifecycleTest extends SociologTestCase
{
    private $previousController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousController = Yii::$app->controller;
        $this->disableNotifications();
        $this->becomeUser('Admin');
        Yii::$app->getModule('sociolog')->settings->set('showReviewInCalendar', false);
        Yii::$app->getModule('sociolog')->settings->set('readerMode', 'all');
        Yii::$app->getModule('sociolog')->settings->setSerialized('deleterUsers', [Yii::$app->user->identity->guid]);
        Space::findOne(4)->updateAttributes(['visibility' => Space::VISIBILITY_ALL]);
    }

    protected function tearDown(): void
    {
        Yii::$app->controller = $this->previousController;
        parent::tearDown();
    }

    private function controller(): EntryController
    {
        $controller = new EntryController('entry', Yii::$app->getModule('sociolog'));
        Yii::$app->controller = $controller;
        return $controller;
    }

    public function testControllerTrashFiresRecordEventsAndCanBeRestored(): void
    {
        $entry = $this->createEntry(Space::findOne(4));
        $scope = ReadScope::findOne(['entry_id' => $entry->id]);
        $this->assertNotNull($scope);
        $other = $this->createEntry(Space::findOne(4), 'Unrelated preserved decision');
        $snapshot = $entry->getAttributes();
        $contentId = $entry->content->id;
        $before = $after = 0;
        $beforeHandler = function ($event) use ($entry, &$before) {if ($event->sender->id == $entry->id) $before++;};
        $afterHandler = function ($event) use ($entry, &$after) {if ($event->sender->id == $entry->id) $after++;};
        Event::on(Entry::class, Entry::EVENT_BEFORE_SOFT_DELETE, $beforeHandler);
        Event::on(Entry::class, Entry::EVENT_AFTER_SOFT_DELETE, $afterHandler);
        try {
            $this->controller()->actionDelete($entry->id);
            $this->assertSame(1, $before);
            $this->assertSame(1, $after);
            $this->assertSame($snapshot, Entry::findOne($entry->id)->getAttributes());
            $content = Content::findOne($contentId);
            $this->assertTrue($content->getStateService()->isDeleted());
            $this->assertFalse(Entry::find()->publishedOrLegacy()->andWhere([Entry::tableName() . '.id' => $entry->id])->exists());
            $this->assertNotNull(ReadScope::findOne($scope->id));
            $this->assertNotNull(ContentContainer::findOne($scope->contentcontainer_id));
            $this->assertTrue($content->getStateService()->update(Content::STATE_PUBLISHED));
            $this->assertTrue(Entry::find()->publishedOrLegacy()->andWhere([Entry::tableName() . '.id' => $entry->id])->exists());
            $this->assertSame($snapshot, Entry::findOne($entry->id)->getAttributes());
            $this->assertSame($other->decision, Entry::findOne($other->id)->decision);
        } finally {
            Event::off(Entry::class, Entry::EVENT_BEFORE_SOFT_DELETE, $beforeHandler);
            Event::off(Entry::class, Entry::EVENT_AFTER_SOFT_DELETE, $afterHandler);
        }
    }

    public function testCancelledRecordDeletionKeepsAllData(): void
    {
        $entry = $this->createEntry(Space::findOne(4));
        $content = $entry->content->getAttributes();
        $scope = ReadScope::findOne(['entry_id' => $entry->id])->getAttributes();
        $cancel = function ($event) use ($entry) {if ($event->sender->id == $entry->id) $event->isValid = false;};
        Event::on(Entry::class, Entry::EVENT_BEFORE_SOFT_DELETE, $cancel);
        try {
            $this->controller()->actionDelete($entry->id);
            $this->assertTrue(Yii::$app->session->hasFlash('danger'));
            $this->assertSame($entry->getAttributes(), Entry::findOne($entry->id)->getAttributes());
            $this->assertSame($content, Content::findOne($entry->content->id)->getAttributes());
            $this->assertSame($scope, ReadScope::findOne(['entry_id' => $entry->id])->getAttributes());
        } finally {
            Event::off(Entry::class, Entry::EVENT_BEFORE_SOFT_DELETE, $cancel);
        }
    }

    public function testUnauthorizedControllerDeletionDoesNotChangeContent(): void
    {
        $entry = $this->createEntry(Space::findOne(4));
        $snapshot = $entry->content->getAttributes();
        $this->becomeUser('User3');
        $this->assertFalse($entry->canDelete(Yii::$app->user->identity));
        $this->controller()->actionDelete($entry->id);
        $this->assertSame($snapshot, Content::findOne($entry->content->id)->getAttributes());
        $this->assertNotNull(Entry::findOne($entry->id));
        $this->assertTrue(Yii::$app->session->hasFlash('warning'));
    }

    public function testHardDeletionCallbackDoesNotRecurseOrUseStaleContent(): void
    {
        $entry = $this->createEntry(Space::findOne(4));
        $scope = ReadScope::findOne(['entry_id' => $entry->id]);
        $contentId = $entry->content->id; // Intentionally load/cache this relation.
        $calls = 0;
        $callback = function ($event) use ($entry, &$calls) {
            if ($event->sender->id == $entry->id) {
                $calls++;
                SociologStreamService::onAfterDelete($event->sender);
            }
        };
        Event::on(Entry::class, Entry::EVENT_AFTER_DELETE, $callback);
        try {
            $this->assertTrue($entry->hardDelete());
            SociologStreamService::onAfterDelete($entry);
            SociologStreamService::onAfterDelete($entry);
            $this->assertSame(1, $calls);
            $this->assertNull(Entry::findOne($entry->id));
            $this->assertNull(Content::findOne($contentId));
            $this->assertNull(ReadScope::findOne($scope->id));
            $this->assertNull(ContentContainer::findOne($scope->contentcontainer_id));
            $this->assertNotNull(Space::findOne(4));
        } finally {
            Event::off(Entry::class, Entry::EVENT_AFTER_DELETE, $callback);
        }
    }

    public function testMisplacedCompatibilityCallbackNeverDeletesLiveOrTrashedEntry(): void
    {
        $entry = $this->createEntry(Space::findOne(4));
        foreach ([false, true] as $trash) {
            if ($trash) $this->assertTrue($entry->softDelete());
            $content = Content::findOne($entry->content->id)->getAttributes();
            SociologStreamService::onAfterDelete($entry);
            $this->assertSame($content, Content::findOne($entry->content->id)->getAttributes());
            $this->assertSame($entry->decision, Entry::findOne($entry->id)->decision);
            $this->assertNotNull(ReadScope::findOne(['entry_id' => $entry->id]));
        }
    }
}
