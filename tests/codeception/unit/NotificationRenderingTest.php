<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\sociolog\notifications\EntryCreated;
use humhub\modules\sociolog\notifications\EntryUpdated;
use humhub\modules\space\models\Space;
use sociolog\SociologTestCase;
use Yii;
use yii\helpers\Html;

class NotificationRenderingTest extends SociologTestCase
{
    public function testWebTitlesAndModuleNamesAreEncodedExactlyOnce(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(4));
        $values = ['A & B', 'Änderung "Haus" <Entscheid>', '<img src=x onerror="alert(1)">', 'Literal &amp; bleibt Text'];
        foreach ($values as $value) {
            $entry->title = $value;
            Yii::$app->getModule('sociolog')->settings->set('moduleTitle', $value);
            foreach ([EntryCreated::class, EntryUpdated::class] as $class) {
                $notification = new $class(['source' => $entry]);
                $html = $notification->render();
                $dom = new \DOMDocument();
                @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
                $xpath = new \DOMXPath($dom);
                $title = $xpath->query('//div[contains(@style,"font-weight:600")]')->item(0);
                $subtitle = $xpath->query('//div[contains(@style,"color:#555")]')->item(0);
                $this->assertSame($value, trim($title->textContent));
                $this->assertStringStartsWith($value . ' · ', trim($subtitle->textContent));
                $this->assertStringContainsString(Html::encode($value), $html);
                $this->assertSame(0, $xpath->query('//*[@onerror or @onclick] | //script')->length);
                $links = $xpath->query('//a[@target="_blank"]');
                $this->assertSame(1, $links->length);
                $this->assertStringContainsString('noopener', $links->item(0)->getAttribute('rel'));
                $this->assertStringContainsString('id=' . $entry->id, $links->item(0)->getAttribute('href'));
            }
        }
    }

    public function testMissingSourceUsesSafeUnencodedFallbackText(): void
    {
        Yii::$app->getModule('sociolog')->settings->set('moduleTitle', 'Haus & Verein <Test>');
        foreach ([EntryCreated::class, EntryUpdated::class] as $class) {
            $html = (new $class())->render();
            $this->assertStringContainsString('Haus &amp; Verein &lt;Test&gt;', $html);
            $this->assertStringNotContainsString('&amp;amp;', $html);
            $this->assertStringNotContainsString('<Test>', $html);
        }
    }

    public function testMailSubjectsKeepPlainTextCharacters(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(4));
        $entry->title = 'Änderung "A & B" <Text>';
        Yii::$app->getModule('sociolog')->settings->set('moduleTitle', 'Haus & Verein');
        foreach ([EntryCreated::class, EntryUpdated::class] as $class) {
            $subject = (new $class(['source' => $entry]))->getMailSubject();
            $this->assertStringContainsString($entry->title, $subject);
            $this->assertStringContainsString('Haus & Verein', $subject);
            $this->assertStringNotContainsString('&amp;', $subject);
        }
    }
}
