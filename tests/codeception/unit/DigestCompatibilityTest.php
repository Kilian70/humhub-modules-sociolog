<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\activity\components\MailSummary;
use humhub\modules\activity\models\MailSummaryForm;
use humhub\modules\sociolog\components\LogbookMailSummary;
use humhub\modules\sociolog\models\SettingsForm;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use sociolog\SociologTestCase;
use Yii;

class DigestCompatibilityTest extends SociologTestCase
{
    private function setupEntry(): array
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->set('showReviewInCalendar', false);
        $settings->set('readerMode', 'all');
        $reader = User::findOne(3);
        $prefs = Yii::$app->getModule('activity')->settings->user($reader);
        foreach (['mailSummaryLast' => 0, 'mailSummaryLimitSpaces' => '', 'mailSummaryActivitySuppress' => ''] as $k => $v) {
            $prefs->set($k, $v);
        }
        $entry = $this->createEntry(Space::findOne(2), 'Digest & Ä <script>literal</script>');
        return [$entry, $reader];
    }

    private function mode(string $mode, array $users = []): void
    {
        $form = new SettingsForm();
        $form->loadSettings();
        $form->readerMode = $mode;
        $form->readerUsers = $users;
        $form->readerGroups = [];
        $this->assertTrue($form->save(), json_encode($form->errors));
    }

    public function testRecipientAclIsIndependentOfWorkerIdentityAndRevocation(): void
    {
        [$entry, $reader] = $this->setupEntry();
        $digest = Yii::createObject(['class' => MailSummary::class, 'user' => $reader, 'interval' => MailSummary::INTERVAL_DAILY]);
        $this->assertInstanceOf(LogbookMailSummary::class, $digest);
        $this->logout();
        $this->assertCount(1, $digest->getLogbookEntries());
        $this->mode('space');
        $this->assertCount(0, $digest->getLogbookEntries());
        $this->mode('selected', [$reader->guid]);
        $this->assertCount(1, $digest->getLogbookEntries());
        $this->mode('selected');
        $this->assertCount(0, $digest->getLogbookEntries());
        $digest->user = null;
        $this->assertSame([], $digest->getLogbookEntries());
        $reader->status = User::STATUS_SOFT_DELETED;
        $digest->user = $reader;
        $this->assertSame([], $digest->getLogbookEntries());
    }

    public function testNativeSpacePreferencesUseOriginalSpaceAndSuppressions(): void
    {
        [$entry, $reader] = $this->setupEntry();
        $this->mode('selected', [$reader->guid]);
        $digest = new LogbookMailSummary(['user' => $reader, 'interval' => 2]);
        $prefs = Yii::$app->getModule('activity')->settings->user($reader);
        $prefs->set('mailSummaryLimitSpaces', Space::findOne(2)->contentContainerRecord->guid);
        $prefs->set('mailSummaryLimitSpacesMode', MailSummaryForm::LIMIT_MODE_INCLUDE);
        $this->assertCount(1, $digest->getLogbookEntries());
        $prefs->set('mailSummaryLimitSpacesMode', MailSummaryForm::LIMIT_MODE_EXCLUDE);
        $this->assertCount(0, $digest->getLogbookEntries());
        $prefs->set('mailSummaryLimitSpaces', '');
        $prefs->set('mailSummaryActivitySuppress', 'humhub\\modules\\content\\activities\\ContentCreated');
        $this->assertCount(0, $digest->getLogbookEntries());
    }

    public function testNativeDeliveryContainsSafeHtmlPlaintextAndDoesNotRepeatEntries(): void
    {
        [$entry, $reader] = $this->setupEntry();
        Yii::$app->controller = new \humhub\modules\dashboard\controllers\DashboardController('dashboard', Yii::$app->getModule('dashboard'));
        $originalMailer = Yii::$app->mailer;
        $mailer = new \humhub\components\mail\Mailer(['useFileTransport' => true, 'viewPath' => '@humhub/views/mail']);
        Yii::$app->set('mailer', $mailer);
        $oldTransport = $mailer->useFileTransport;
        $oldPath = $mailer->fileTransportPath;
        $path = Yii::getAlias('@runtime') . '/b2-test-' . bin2hex(random_bytes(5));
        $mailer->useFileTransport = true;
        $mailer->fileTransportPath = $path;
        try {
            $digest = Yii::createObject(['class' => MailSummary::class, 'user' => $reader, 'interval' => 2]);
            $this->assertTrue($digest->send());
            $files = glob($path . '/*');
            $this->assertCount(1, $files);
            $raw = quoted_printable_decode(file_get_contents($files[0]));
            $this->assertStringContainsString('&lt;script&gt;literal&lt;/script&gt;', $raw);
            $this->assertStringContainsString('Digest & Ä <script>literal</script>', $raw);
            $this->assertStringContainsString('sociolog', $raw);
            $this->assertSame([], $digest->getLogbookEntries());
        } finally {
            $mailer->useFileTransport = $oldTransport;
            $mailer->fileTransportPath = $oldPath;
            Yii::$app->set('mailer', $originalMailer);
        }
    }

    public function testOldDeletedAndOwnEntriesAreExcluded(): void
    {
        [$entry, $reader] = $this->setupEntry();
        $digest = new LogbookMailSummary(['user' => $reader, 'interval' => 2]);
        $digest->user = User::findOne($entry->created_by);
        $this->assertCount(0, $digest->getLogbookEntries());
        $digest->user = $reader;
        $entry->content->updateAttributes(['created_at' => '2000-01-01 00:00:00']);
        $this->assertCount(0, $digest->getLogbookEntries());
        $entry->content->updateAttributes(['created_at' => date('Y-m-d H:i:s'), 'state' => \humhub\modules\content\models\Content::STATE_DELETED]);
        $this->assertCount(0, $digest->getLogbookEntries());
    }

    public function testGroupSelectionAndFreshRequestBootstrap(): void
    {
        [$entry, $reader] = $this->setupEntry();
        $group = new \humhub\modules\user\models\Group(['name' => 'Synthetic digest readers']);
        $this->assertTrue($group->save());
        $group->addUser($reader);
        $form = new SettingsForm();
        $form->loadSettings();
        $form->readerMode = 'selected';
        $form->readerUsers = [];
        $form->readerGroups = [(int)$group->id];
        $this->assertTrue($form->save());
        Yii::$container->clear(MailSummary::class);
        Yii::$app->trigger(\yii\base\Application::EVENT_BEFORE_REQUEST);
        $digest = Yii::createObject(['class' => MailSummary::class, 'user' => $reader, 'interval' => 3]);
        $this->assertInstanceOf(LogbookMailSummary::class, $digest);
        $this->assertCount(1, $digest->getLogbookEntries());
        $digest->maxActivityCount = 0;
        $this->assertSame([], $digest->getLogbookEntries());
        Yii::$container->clear(MailSummary::class);
        Yii::$app->queue->trigger(\yii\queue\Queue::EVENT_BEFORE_EXEC, new \yii\queue\ExecEvent([
            'job' => new \humhub\modules\activity\jobs\SendMailSummary(['interval' => 2]),
        ]));
        $this->assertInstanceOf(LogbookMailSummary::class, Yii::createObject(MailSummary::class));
    }
}
