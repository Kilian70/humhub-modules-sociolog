<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\EntryReview;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use sociolog\SociologTestCase;
use Yii;

class EntryLifecycleTest extends SociologTestCase
{
    public function testCreateEditAndSoftDelete(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');

        $space = Space::findOne(4);
        $this->assertNotNull($space);

        $entry = $this->createEntry($space);
        $this->assertNotNull($entry->content);
        $this->assertSame((int)$space->contentcontainer_id, (int)$entry->content->contentcontainer_id);
        $this->assertSame(Entry::STATUS_PENDING, $entry->status);

        $entry->title = 'Updated runtime test entry';
        $this->assertTrue($entry->save());
        $this->assertSame('Updated runtime test entry', Entry::findOne($entry->id)->title);

        $content = $entry->content;
        $this->assertTrue($content->softDelete());
        $this->assertTrue($content->refresh());
        $this->assertTrue($content->getStateService()->isDeleted());
        $this->assertFalse(
            Entry::find()->publishedOrLegacy()->andWhere([Entry::tableName() . '.id' => $entry->id])->exists()
        );
    }

    public function testConfiguredWriteAndDeleteRights(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');

        $space = Space::findOne(4);
        $entry = $this->createEntry($space, 'Permission runtime test entry');
        $outsideUser = User::findOne(4);
        $this->assertNotNull($outsideUser);

        // Den Space für diesen Test bewusst auf eine andere zuständige Person
        // begrenzen, damit keine Fixture- oder Vorlaufrechte das Ergebnis
        // der global konfigurierten Rechte verfälschen.
        $spaceConfig = SpaceConfig::findOne(['space_id' => (int)$space->id]) ?: new SpaceConfig([
            'space_id' => (int)$space->id,
            'link_mode' => 'about',
        ]);
        $spaceConfig->enabled = 1;
        $spaceConfig->global_write = 0;
        $spaceConfig->can_delete = 0;
        $spaceConfig->is_organ_space = 0;
        $spaceConfig->writer_mode = SpaceConfig::WRITER_MODE_SELECTED;
        $spaceConfig->setWriterUserGuids([Yii::$app->user->identity->guid]);
        $this->assertTrue($spaceConfig->save(), json_encode($spaceConfig->getErrors()));
        Entry::resetRuntimeCaches();

        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->setSerialized('managerUsers', []);
        $settings->setSerialized('managerGroups', []);
        $settings->setSerialized('writerGroups', []);
        $settings->setSerialized('deleterGroups', []);
        $settings->setSerialized('writerUsers', [$outsideUser->guid]);
        $settings->setSerialized('deleterUsers', [$outsideUser->guid]);

        $this->assertTrue($entry->canWrite($outsideUser));
        $this->assertTrue($entry->canDelete($outsideUser));

        $settings->setSerialized('writerUsers', []);
        $settings->setSerialized('deleterUsers', []);

        $this->assertFalse($entry->canWrite($outsideUser));
        $this->assertFalse($entry->canDelete($outsideUser));
    }

    public function testReviewHistoryPersistsWithoutChangingDecisionText(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');

        $space = Space::findOne(4);
        $entry = $this->createEntry($space, 'Review history runtime test');
        $decision = $entry->decision;

        $review = new EntryReview([
            'entry_id' => (int)$entry->id,
            'result' => EntryReview::RESULT_CONFIRMED,
            'justification' => 'Confirmed by the automated runtime test.',
            'previous_review_date' => date('Y-m-d'),
            'next_review_date' => date('Y-m-d', strtotime('+1 year')),
            'created_at' => time(),
            'created_by' => (int)Yii::$app->user->id,
        ]);

        $this->assertTrue($review->save(), json_encode($review->getErrors()));
        $this->assertSame($decision, Entry::findOne($entry->id)->decision);
        $this->assertCount(1, Entry::findOne($entry->id)->reviews);
    }

    public function testSelectedWritersReplaceAutomaticSpaceAdminAccess(): void
    {
        $this->becomeUser('User1');
        $space = Space::findOne(4);
        $selectedUser = Yii::$app->user->identity;
        $otherUser = User::findOne(4);

        $config = SpaceConfig::findOne(['space_id' => (int)$space->id]) ?: new SpaceConfig([
            'space_id' => (int)$space->id,
            'enabled' => 1,
            'global_write' => 0,
            'can_delete' => 0,
            'is_organ_space' => 0,
            'link_mode' => 'about',
        ]);
        $config->enabled = 1;
        $config->writer_mode = SpaceConfig::WRITER_MODE_SELECTED;
        $config->setWriterUserGuids([$selectedUser->guid]);
        $this->assertTrue($config->save(), json_encode($config->getErrors()));

        Entry::resetRuntimeCaches();
        $this->assertArrayHasKey((int)$space->id, Entry::getWritableOrgansForUser($selectedUser));

        $config->setWriterUserGuids([$otherUser->guid]);
        $this->assertTrue($config->save(), json_encode($config->getErrors()));
        Entry::resetRuntimeCaches();
        $this->assertArrayNotHasKey((int)$space->id, Entry::getWritableOrgansForUser($selectedUser));
    }
}
