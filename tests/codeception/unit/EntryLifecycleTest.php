<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\sociolog\models\Entry;
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

        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->setSerialized('writerUsers', [$outsideUser->guid]);
        $settings->setSerialized('deleterUsers', [$outsideUser->guid]);

        $this->assertTrue($entry->canWrite($outsideUser));
        $this->assertTrue($entry->canDelete($outsideUser));

        $settings->setSerialized('writerUsers', []);
        $settings->setSerialized('deleterUsers', []);

        $this->assertFalse($entry->canWrite($outsideUser));
        $this->assertFalse($entry->canDelete($outsideUser));
    }
}
