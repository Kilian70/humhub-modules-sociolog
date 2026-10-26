<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\notification\renderer\MailRenderer;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\sociolog\notifications\EntryCreated;
use humhub\modules\sociolog\notifications\EntryUpdated;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use sociolog\SociologTestCase;
use Yii;

class MailAndEditCompatibilityTest extends SociologTestCase
{
    private function renderForm(Entry $entry): string
    {
        try { $url = Yii::$app->request->getUrl(); }
        catch (\yii\base\InvalidConfigException $exception) { $url = null; }
        Yii::$app->request->setUrl('/index.php?r=sociolog%2Fentry%2F' . ($entry->isNewRecord ? 'create' : 'update&id=' . $entry->id));
        try {
            return Yii::$app->view->renderFile(Yii::getAlias('@sociolog/views/entry/_form.php'), ['model' => $entry]);
        } finally { Yii::$app->request->setUrl($url); }
    }

    public function testNativeHtmlAndPlainMailRenderersKeepLiteralTitlesAndCorrectEvent(): void
    {
        if (\humhub\modules\sociolog\notifications\NotificationCompatibility::usesRecordApi()) {
            $this->markTestSkipped('Legacy templates apply only to the Stable notification API; record API has separate tests.');
        }
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(4));
        $renderer = new MailRenderer();
        foreach (['A & B', 'Ä "Haus" <Entscheid>', 'Literal &amp; bleibt Text', '<img src=x onerror="alert(1)">'] as $value) {
            $entry->title = $value;
            Yii::$app->getModule('sociolog')->settings->set('moduleTitle', $value);
            foreach ([EntryCreated::class, EntryUpdated::class] as $class) {
                $notification = new $class(['source' => $entry, 'originator' => Yii::$app->user->identity]);
                $this->assertTrue($notification->saveRecord(User::findOne(3)));
                $html = $renderer->render($notification);
                $plain = $renderer->renderText($notification);
                $this->assertStringContainsString($value, $plain);
                $this->assertStringContainsString($notification->getTitle(), $plain);
                $this->assertStringContainsString($value, $notification->text());
                $this->assertStringContainsString('id=' . $entry->id, $plain);
                $dom = new \DOMDocument();
                @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
                $xpath = new \DOMXPath($dom);
                $this->assertStringContainsString($notification->getTitle(), $dom->textContent);
                $this->assertStringContainsString($value, $dom->textContent);
                $this->assertSame(0, $xpath->query('//*[@onerror or @onclick] | //script')->length);
            }
        }
    }

    public function testMailContentHandlesMissingSourceWithoutBrokenLink(): void
    {
        if (\humhub\modules\sociolog\notifications\NotificationCompatibility::usesRecordApi()) {
            $this->markTestSkipped('Legacy templates apply only to the Stable notification API; record API has separate tests.');
        }
        Yii::$app->getModule('sociolog')->settings->set('moduleTitle', 'Haus & Verein <Test>');
        foreach ([EntryCreated::class, EntryUpdated::class] as $class) {
            $notification = new $class();
            $this->assertStringContainsString('Haus &amp; Verein &lt;Test&gt;', $notification->html());
            $this->assertStringContainsString($notification->getDescription(), $notification->text());
            $this->assertStringNotContainsString('href=', $notification->html());
        }
    }

    public function testManagerKeepsExistingOrganWithoutGainingCreateOrMoveRights(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(4));
        $config = SpaceConfig::findOne(['space_id' => 4]) ?: new SpaceConfig(['space_id' => 4]);
        $config->enabled = 1;
        $config->writer_mode = SpaceConfig::WRITER_MODE_SELECTED;
        $config->setWriterUserGuids([Yii::$app->user->identity->guid]);
        $this->assertTrue($config->save());
        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->setSerialized('writerUsers', []);
        $settings->setSerialized('writerGroups', []);
        $settings->setSerialized('managerUsers', [User::findOne(3)->guid]);
        $settings->setSerialized('managerGroups', []);
        Entry::resetRuntimeCaches();
        $this->becomeUser('User2');
        $this->assertTrue($entry->canWrite());
        $this->assertFalse(Entry::canCreateGlobal(null, 4));
        $this->assertArrayNotHasKey(4, Entry::getWritableOrgansForUser());
        $html = $this->renderForm($entry);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $selected = $xpath->query('//select[@name="Entry[organ]"]/option[@value="4" and @selected]');
        $this->assertSame(1, $selected->length);
        $this->assertFalse(Entry::canCreateGlobal(null, 4));
        $new = new Entry();
        $html = $this->renderForm($new);
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(0, $xpath->query('//select[@name="Entry[organ]"]/option[@value="4"]')->length);
    }
}
