<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\SettingsForm;
use humhub\modules\space\models\Space;
use sociolog\SociologTestCase;
use Yii;

class ObjectionDeadlineTest extends SociologTestCase
{
    public function testTenFullDaysAndExactExpiryBoundary(): void
    {
        // Crossing the autumn clock change still grants 240 hours.
        $published = (new \DateTimeImmutable('2026-10-21 14:00:00', new \DateTimeZone('Europe/Berlin')))->getTimestamp();
        $entry = new Entry(['published_at' => '2026-10-21', 'published_timestamp' => $published]);
        $deadline = $published + 864000;
        $this->assertSame($deadline, $entry->getObjectionDeadlineTimestamp());
        $this->assertFalse($entry->isObjectionPeriodExpired($deadline - 1));
        $this->assertTrue($entry->isObjectionPeriodExpired($deadline));
        $this->assertTrue($entry->isObjectionPeriodExpired($deadline + 1));
        Yii::$app->getModule('sociolog')->settings->set('defaultEffectiveDays', 42);
        Yii::$app->getModule('sociolog')->settings->set('effectiveDateAddExtraDay', false);
        $this->assertSame($deadline, $entry->getObjectionDeadlineTimestamp());
    }

    public function testLegacyDateDoesNotInventPublicationTime(): void
    {
        $entry = new Entry(['published_at' => '2024-02-25']);
        $this->assertNull($entry->getObjectionDeadlineTimestamp());
        $this->assertSame('2024-03-06', $entry->getObjectionDeadlineDate());
        $this->assertFalse($entry->isObjectionPeriodExpired(PHP_INT_MAX));
        $entry->published_at = '2026-02-31';
        $this->assertNull($entry->getObjectionDeadlineDate());
        $entry->published_at = null;
        $entry->published_timestamp = time();
        $this->assertNull($entry->getObjectionDeadlineTimestamp());
        $this->assertNull($entry->getObjectionDeadlineDate());
    }

    public function testPublicationIsCapturedOnceAndCannotBePosted(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $start = time();
        $entry = $this->createEntry(Space::findOne(1));
        $this->assertGreaterThanOrEqual($start, (int)$entry->published_timestamp);
        $this->assertLessThanOrEqual(time(), (int)$entry->published_timestamp);
        $original = (int)$entry->published_timestamp;
        $entry->setAttributes(['published_timestamp' => 1, 'title' => 'Edited without extending deadline']);
        $this->assertSame($original, (int)$entry->published_timestamp);
        $this->assertTrue($entry->save());
        $entry->refresh();
        $this->assertSame($original, (int)$entry->published_timestamp);
        // Simulate an old row after the additive migration.
        Yii::$app->db->createCommand()->update(Entry::tableName(), ['published_timestamp' => null], ['id' => $entry->id])->execute();
        $entry->refresh();
        $entry->title = 'Legacy edit';
        $this->assertTrue($entry->save());
        $entry->refresh();
        $this->assertNull($entry->published_timestamp);
    }

    public function testReopenedDecisionClearsDeadlineAndNewDecisionRestartsIt(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(1));
        $entry->reopenDecision();
        $this->assertNull($entry->published_timestamp);
        $this->assertNull($entry->getObjectionDeadlineTimestamp());
        $this->assertTrue($entry->save(false));
        $entry->refresh();
        $this->assertNull($entry->published_timestamp);
        $entry->makeDecision();
        $this->assertNull($entry->published_timestamp);
        $this->assertTrue($entry->save());
        $entry->refresh();
        $this->assertNotNull($entry->getObjectionDeadlineTimestamp());
    }

    public function testOptionalDisplayAndLegacyHint(): void
    {
        Yii::$app->language = 'de';
        $settings = Yii::$app->getModule('sociolog')->settings;
        $entry = new Entry(['published_at' => '2026-10-11', 'published_timestamp' => time()]);
        $view = Yii::$app->view;
        $file = '@sociolog/views/entry/_objectionDeadline.php';
        $settings->set('showObjectionDeadline', false);
        $this->assertSame('', $view->renderFile($file, ['model' => $entry]));
        $settings->set('showObjectionDeadline', true);
        $html = $view->renderFile($file, ['model' => $entry]);
        $this->assertStringContainsString('Schwerwiegender Einwand', $html);
        $this->assertStringNotContainsString('Einwandsfrist abgelaufen', $html);
        $entry->published_timestamp = time() - Entry::OBJECTION_PERIOD_SECONDS;
        $this->assertStringContainsString('Einwandsfrist abgelaufen', $view->renderFile($file, ['model' => $entry]));
        $entry->published_timestamp = null;
        $this->assertStringContainsString('Uhrzeit', $view->renderFile($file, ['model' => $entry]));
        $entry->published_at = null;
        $this->assertSame('', $view->renderFile($file, ['model' => $entry]));
    }

    public function testDisplaySettingRoundTripsAndRejectsMalformedValues(): void
    {
        $form = new SettingsForm();
        $form->loadSettings();
        $form->showObjectionDeadline = ['1'];
        $this->assertFalse($form->validate(['showObjectionDeadline']));
        $form->showObjectionDeadline = true;
        $this->assertTrue($form->validate(['showObjectionDeadline']));
        $this->assertTrue($form->save(), json_encode($form->getErrors()));
        $loaded = new SettingsForm();
        $loaded->loadSettings();
        $this->assertTrue((bool)$loaded->showObjectionDeadline);
        $loaded->showObjectionDeadline = false;
        $this->assertTrue($loaded->save(), json_encode($loaded->getErrors()));
        $disabled = new SettingsForm();
        $disabled->loadSettings();
        $this->assertFalse((bool)$disabled->showObjectionDeadline);
    }
}
