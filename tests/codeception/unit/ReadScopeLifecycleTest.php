<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\ReadScope;
use humhub\modules\sociolog\services\ReadScopeCleanupService;
use humhub\modules\space\models\Space;
use sociolog\SociologTestCase;
use Yii;

class ReadScopeLifecycleTest extends SociologTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->disableNotifications();
        $this->becomeUser('User1');
        Yii::$app->getModule('sociolog')->settings->set('readerMode', 'selected');
    }

    private function entry(): Entry
    {
        $entry = $this->createEntry(Space::findOne(4));
        $this->assertNotNull(ReadScope::findOne(['entry_id' => $entry->id]));
        return $entry;
    }

    public function testPermanentDeletionCleansOnlyItsOwnContainer(): void
    {
        $entry = $this->entry();
        $other = $this->entry();
        $scope = ReadScope::findOne(['entry_id' => $entry->id]);
        $otherScope = ReadScope::findOne(['entry_id' => $other->id]);
        $original = $scope->original_container_id;
        $contentId = $entry->content->id;
        $this->assertTrue($entry->hardDelete());
        $this->assertNull(ReadScope::findOne($scope->id));
        $this->assertNull(ContentContainer::findOne($scope->contentcontainer_id));
        $this->assertNull(Content::findOne($contentId));
        $this->assertNotNull(ContentContainer::findOne($original));
        $this->assertNotNull(Entry::findOne($other->id));
        $this->assertNotNull(ReadScope::findOne($otherScope->id));
        $this->assertNotNull(ContentContainer::findOne($otherScope->contentcontainer_id));
    }

    public function testTrashAndRestoreKeepScopeAndAccess(): void
    {
        $entry = $this->entry();
        $scope = ReadScope::findOne(['entry_id' => $entry->id]);
        $content = $entry->content;
        $this->assertTrue($content->softDelete());
        $this->assertFalse(ReadScopeCleanupService::deleteForEntry((int)$entry->id));
        $this->assertNotNull(ReadScope::findOne($scope->id));
        $this->assertNotNull(ContentContainer::findOne($scope->contentcontainer_id));
        $this->assertTrue($content->getStateService()->update(Content::STATE_PUBLISHED));
        $this->assertTrue($scope->canAccessPrivateContent(Yii::$app->user->identity));
        $this->assertSame((int)$scope->contentcontainer_id, (int)Content::findOne($content->id)->contentcontainer_id);
    }

    public function testExistingOrphansAreReportedButNotSwept(): void
    {
        $orphanEntry = $this->entry();
        $orphan = ReadScope::findOne(['entry_id' => $orphanEntry->id]);
        // Simulate a historical defect in this synthetic DB only.
        Entry::deleteAll(['id' => $orphanEntry->id]);
        $live = $this->entry();
        $liveScope = ReadScope::findOne(['entry_id' => $live->id]);
        $this->assertTrue($live->content->softDelete());
        $before = [ReadScope::find()->count(), ContentContainer::find()->count(), Content::find()->count()];
        $ids = array_column(ReadScopeCleanupService::orphanQuery()->all(), 'id');
        $this->assertContains($orphan->id, $ids);
        $this->assertNotContains($liveScope->id, $ids);
        $this->assertSame($before, [ReadScope::find()->count(), ContentContainer::find()->count(), Content::find()->count()]);
        $new = $this->entry();
        $this->assertTrue($new->hardDelete());
        $this->assertNotNull(ReadScope::findOne($orphan->id));
        $this->assertNotNull(ContentContainer::findOne($orphan->contentcontainer_id));
    }

    public function testForeignContentPreventsContainerDeletion(): void
    {
        $entry = $this->entry();
        $scope = ReadScope::findOne(['entry_id' => $entry->id]);
        $other = $this->entry();
        // Simulate a foreign content reference without running ACL resynchronization.
        Content::updateAll(['contentcontainer_id' => $scope->contentcontainer_id], ['id' => $other->content->id]);
        $this->assertTrue($entry->hardDelete());
        $this->assertNotNull(ReadScope::findOne($scope->id));
        $this->assertNotNull(ContentContainer::findOne($scope->contentcontainer_id));
        $this->assertNotNull(Content::findOne($other->content->id));
        $this->assertNotNull(Entry::findOne($other->id));
    }

    public function testWrongOrMissingContainerIsRetained(): void
    {
        foreach ([Space::findOne(4)->contentcontainer_id, null] as $containerId) {
            $entry = $this->entry();
            $scope = ReadScope::findOne(['entry_id' => $entry->id]);
            ReadScope::updateAll(['contentcontainer_id' => $containerId], ['id' => $scope->id]);
            $this->assertTrue($entry->hardDelete());
            $this->assertNotNull(ReadScope::findOne($scope->id));
            $this->assertNotNull(Space::findOne(4));
            $this->assertNotNull(ContentContainer::findOne(Space::findOne(4)->contentcontainer_id));
        }
    }

    public function testEntryWithoutScopeCanStillBeDeleted(): void
    {
        Yii::$app->getModule('sociolog')->settings->set('readerMode', 'all');
        $space = Space::findOne(2);
        $space->updateAttributes(['visibility' => Space::VISIBILITY_NONE]);
        $entry = $this->createEntry($space);
        $this->assertNull(ReadScope::findOne(['entry_id' => $entry->id]));
        $this->assertTrue($entry->hardDelete());
        $this->assertNotNull(Space::findOne(2));
    }
}
