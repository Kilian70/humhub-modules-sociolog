<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\notification\components\BaseNotification;
use humhub\modules\notification\components\NotificationCategory;
use humhub\modules\notification\models\Notification;
use humhub\modules\sociolog\notifications\EntryCreated;
use humhub\modules\sociolog\notifications\EntryUpdated;
use humhub\modules\sociolog\notifications\NotificationCompatibility;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use sociolog\SociologTestCase;
use Yii;

class NotificationCompatibilityTest extends SociologTestCase
{
    public function testNativeCategoryAndDiscoveryPreservePublicClassNames(): void
    {
        $modern = (new \ReflectionMethod(BaseNotification::class, 'category'))->isStatic();
        $this->assertSame($modern, NotificationCompatibility::usesRecordApi());
        $classes = Yii::$app->getModule('sociolog')->getNotifications();
        foreach ([EntryCreated::class, EntryUpdated::class] as $class) {
            $category = $modern ? $class::category() : (new $class())->getCategory();
            $this->assertInstanceOf(NotificationCategory::class, $category);
            $this->assertSame('sociolog', $category->id);
            $this->assertSame($modern, (new \ReflectionMethod($class, 'category'))->isStatic());
            $this->assertFalse((new \ReflectionClass($class))->isAbstract());
            $this->assertContains($class, $classes);
        }
        $this->assertCount(2, $classes);
    }

    public function testRecordApiUsesNativeMessagesAndMailSubjects(): void
    {
        if (!NotificationCompatibility::usesRecordApi()) {
            $this->markTestSkipped('Record-bound API is unavailable on this Stable version.');
        }
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(4));
        $entry->updateAttributes(['title' => 'A & B <literal>']);
        $actor = Yii::$app->user->identity;
        $recipient = User::findOne(['username' => 'User2']);
        foreach ([EntryCreated::class, EntryUpdated::class] as $class) {
            NotificationCompatibility::send($class, $entry, $actor, $recipient);
            $record = Notification::find()->where(['class' => $class, 'user_id' => $recipient->id])->orderBy(['id' => SORT_DESC])->one();
            $this->assertNotNull($record);
            $object = \humhub\modules\notification\components\NotificationManager::load($record);
            $this->assertSame($class, get_class($object));
            $this->assertTrue($object->canReceive($recipient));
            $params = (new \ReflectionMethod($object, 'getMessageParams'))->invoke($object);
            $this->assertSame('A & B <literal>', $params['title']);
            $subject = (new \ReflectionMethod($object, 'getMailSubject'))->invoke($object, $params);
            $this->assertStringContainsString('A & B <literal>', $subject);
            $this->assertStringNotContainsString('&amp;', $subject);
        }
    }
}
