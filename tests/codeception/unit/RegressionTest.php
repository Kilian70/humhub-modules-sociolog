<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\sociolog\controllers\EntryController;
use humhub\modules\sociolog\models\DecisionType;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\EntryReview;
use humhub\modules\sociolog\models\EntrySearch;
use humhub\modules\sociolog\models\Organ;
use humhub\modules\sociolog\models\Protocol;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\sociolog\models\SpaceWriterForm;
use humhub\modules\sociolog\services\SociologImportService;
use humhub\modules\sociolog\services\SociologUserDeletionService;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use sociolog\SociologTestCase;
use Yii;

class RegressionTest extends SociologTestCase
{
    public function testSavingDefaultLabelsDoesNotFixAdministratorsLanguage(): void
    {
        $module = Yii::$app->getModule('sociolog');
        $defaults = [
            'decisionDateLabel' => 'Beschlussdatum',
            'topicOwnerLabel' => 'Themenhüter:in',
            'protocolsLabel' => 'Protokolle',
            'pendingStatusLabel' => 'Nicht in Kraft',
        ];
        foreach ($defaults as $setting => $label) {
            $module->settings->delete($setting);
        }
        $language = Yii::$app->language;
        try {
            Yii::$app->language = 'en-US';
            $form = new \humhub\modules\sociolog\models\SettingsForm();
            $form->loadSettings();
            foreach ($defaults as $setting => $label) {
                $this->assertSame($label, $form->$setting);
            }
            $this->assertTrue($form->save(), json_encode($form->getErrors()));
            $this->assertSame('Decision date', $module->getCustomLabel('decisionDateLabel', 'Beschlussdatum'));
            Yii::$app->language = 'de';
            $this->assertSame('Beschlussdatum', $module->getCustomLabel('decisionDateLabel', 'Beschlussdatum'));
            $module->settings->set('decisionDateLabel', 'Datum des Vereinsbeschlusses');
            Yii::$app->language = 'en-US';
            $this->assertSame('Datum des Vereinsbeschlusses', $module->getCustomLabel('decisionDateLabel', 'Beschlussdatum'));
        } finally {
            Yii::$app->language = $language;
        }
    }

    public function testModuleTranslationsRespectGermanAndEnglishLocales(): void
    {
        Yii::$app->getModule('sociolog');
        foreach (['de', 'de-DE', 'de-CH'] as $language) {
            $this->assertSame('Beschlussdatum', Yii::t('SociologModule.base', 'Beschlussdatum', [], $language));
            $this->assertSame('Themenhüter:in', Yii::t('SociologModule.base', 'Themenhüter:in', [], $language));
            $this->assertSame('NICHT IN KRAFT', Yii::t('SociologModule.base', 'NOT IN FORCE', [], $language));
            $this->assertSame('Einträge erstellen', Yii::t('SociologModule.permissions', 'Einträge erstellen', [], $language));
        }
        foreach (['en', 'en-US', 'en-GB'] as $language) {
            $this->assertSame('Decision date', Yii::t('SociologModule.base', 'Beschlussdatum', [], $language));
            $this->assertSame('Topic owner', Yii::t('SociologModule.base', 'Themenhüter:in', [], $language));
            $this->assertSame('Not in force', Yii::t('SociologModule.base', 'NOT IN FORCE', [], $language));
            $this->assertSame('Create entries', Yii::t('SociologModule.permissions', 'Einträge erstellen', [], $language));
        }
    }

    public function testModuleInstallationRejectsUnsupportedPrefixBeforeMigrations(): void
    {
        $db = Yii::$app->db;
        $prefix = $db->tablePrefix;
        $enabledBefore = (new \yii\db\Query())->from('module_enabled')->all();
        $db->tablePrefix = 'regression_';
        try {
            $module = Yii::$app->getModule('sociolog');
            try {
                $module->enable();
                $this->fail('Prefix must be checked before module registration.');
            } catch (\yii\base\InvalidConfigException $expected) {
                $this->assertSame($enabledBefore, (new \yii\db\Query())->from('module_enabled')->all());
            }
            $service = $module->getMigrationService();
            try {
                $service->migrateUp();
                $this->fail('Unsupported prefix must be rejected before historical migrations run.');
            } catch (\yii\base\InvalidConfigException $expected) {
                $this->assertStringContainsString('Tabellenpräfix', $expected->getMessage());
                $this->assertNull($db->schema->getTableSchema('regression_sociolog_entry', true));
            }
        } finally {
            $db->tablePrefix = $prefix;
        }
    }

    public function testDateValidationRejectsInvalidAndArrayValues(): void
    {
        $entry = new Entry();
        foreach (['2026-02-30', '2026-13-01', 'tomorrow', ['2026-01-01']] as $value) {
            foreach (['decision_date', 'effective_date', 'review_date'] as $attribute) {
                $entry->$attribute = $value;
                $this->assertFalse($entry->validate([$attribute]), $attribute . ': ' . json_encode($value));
                $this->assertTrue($entry->hasErrors($attribute));
            }
        }
        $entry->decision_date = '2024-02-29';
        $this->assertTrue($entry->validate(['decision_date']));
    }

    public function testHiddenTypeCannotBeSavedButExistingTypeCanBeRetained(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->set('fixedDecisionTypeId', 0);
        $settings->setSerialized('hiddenDecisionTypeIds', []);
        $entry = $this->createEntry(Space::findOne(4));
        $settings->setSerialized('hiddenDecisionTypeIds', [(int)$entry->decision_type_id]);
        try {
            $entry->title = 'Existing hidden type retained';
            $this->assertTrue($entry->save(), json_encode($entry->getErrors()));
            $new = new Entry($entry->getAttributes(['title', 'decision', 'organ', 'decision_type_id', 'decision_date']));
            $new->setContentContainer(Space::findOne(4));
            $this->assertFalse($new->save());
            $this->assertTrue($new->hasErrors('decision_type_id'));
            $new->decision_type_id = 999999;
            $this->assertFalse($new->validate(['decision_type_id']));
        } finally {
            $settings->setSerialized('hiddenDecisionTypeIds', []);
        }
    }

    public function testEffectiveDateUsesPublicationAndConfiguredExtraDay(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $settings = Yii::$app->getModule('sociolog')->settings;
        foreach ([true => 11, false => 10] as $extra => $days) {
            $settings->set('effectiveDateAddExtraDay', (bool)$extra);
            $entry = new Entry([
                'title' => 'Publication date regression', 'decision' => 'Decision', 'organ' => 4,
                'decision_type_id' => DecisionType::find()->select('id')->scalar(),
                'decision_date' => '2020-01-01', 'status' => Entry::STATUS_AUTO,
            ]);
            $entry->setContentContainer(Space::findOne(4));
            $this->assertTrue($entry->save(), json_encode($entry->getErrors()));
            $this->assertSame(date('Y-m-d'), $entry->published_at);
            $this->assertSame(date('Y-m-d', strtotime('+' . $days . ' days')), $entry->effective_date);
            $this->assertSame(Entry::STATUS_PENDING, $entry->status);
        }
        $settings->set('effectiveDateAddExtraDay', true);
    }

    public function testFormerInvitedAndDisabledMembersHaveNoSelectedWriterAccess(): void
    {
        $this->becomeUser('User1');
        $user = Yii::$app->user->identity;
        $config = new SpaceConfig(['space_id' => 4, 'enabled' => 1, 'writer_mode' => SpaceConfig::WRITER_MODE_SELECTED]);
        $config->setWriterUserGuids([$user->guid]);
        $this->assertTrue($config->allowsSelectedWriter($user));
        $membership = Membership::findOne(['space_id' => 4, 'user_id' => $user->id]);
        $membership->updateAttributes(['status' => Membership::STATUS_INVITED]);
        $this->assertFalse($config->allowsSelectedWriter($user));
        $form = new SpaceWriterForm($config, ['writerMode' => SpaceConfig::WRITER_MODE_SELECTED, 'writerUsers' => [$user->guid]]);
        $this->assertFalse($form->validate());
        $membership->delete();
        $this->assertFalse($config->allowsSelectedWriter($user));
        $user->status = User::STATUS_DISABLED;
        $this->assertFalse($config->allowsSelectedWriter($user));
    }

    public function testSearchRejectsMalformedInputsWithoutThrowing(): void
    {
        foreach (['query', 'organ', 'year', 'decision_type_id', 'status'] as $attribute) {
            $search = new EntrySearch();
            $provider = $search->search(['EntrySearch' => [$attribute => ['malformed']]]);
            $this->assertTrue($search->hasErrors($attribute));
            $this->assertSame(0, $provider->getTotalCount());
            $this->assertNull($search->$attribute);
        }
    }

    public function testStatusSearchKeepsProtectedAndForwardedStates(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        foreach ([Entry::STATUS_EXPIRED, Entry::STATUS_OBJECTION, Entry::STATUS_REPLACED] as $status) {
            $entry = $this->createEntry(Space::findOne(4), 'Protected ' . $status);
            $entry->updateAttributes(['status' => $status, 'effective_date' => '2020-01-01', 'review_date' => '2021-01-01']);
            foreach ([Entry::STATUS_PENDING, Entry::STATUS_VALID, Entry::STATUS_REVIEW, $status] as $filter) {
                $provider = (new EntrySearch())->search(['EntrySearch' => ['status' => $filter]]);
                $provider->query->andWhere([Entry::tableName() . '.id' => $entry->id]);
                $this->assertSame($filter === $status ? 1 : 0, (int)$provider->query->count());
            }
        }
        $entry = $this->createEntry(Space::findOne(4), 'Forwarded status regression');
        $entry->updateAttributes(['status' => Entry::STATUS_PENDING, 'forwarded_to' => 'Target', 'effective_date' => '2020-01-01', 'review_date' => '2021-01-01']);
        foreach ([Entry::STATUS_PENDING => 1, Entry::STATUS_REVIEW => 0, Entry::STATUS_EXPIRED => 0] as $filter => $expected) {
            $provider = (new EntrySearch())->search(['EntrySearch' => ['status' => $filter]]);
            $provider->query->andWhere([Entry::tableName() . '.id' => $entry->id]);
            $this->assertSame($expected, (int)$provider->query->count());
        }
        $entry->save(false);
        $this->assertSame(Entry::STATUS_PENDING, $entry->status);
    }

    public function testImplicitReviewOfLeapDayUsesCalendarAnniversary(): void
    {
        $status = \humhub\modules\sociolog\services\SociologStatusService::calculateStatus(
            ['status' => Entry::STATUS_VALID, 'effective_date' => '2024-02-29'],
            new \DateTimeImmutable('2026-02-28')
        );
        $this->assertSame(Entry::STATUS_REVIEW, $status);
    }

    public function testSearchAndDailyRunUseTheSameDateFallbacks(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $today = date('Y-m-d');
        $future = date('Y-m-d', strtotime('+1 month'));
        $rows = [
            [Entry::STATUS_VALID, '2020-01-01', null, null, Entry::STATUS_REVIEW],
            [Entry::STATUS_VALID, null, null, '2020-01-01', Entry::STATUS_REVIEW],
            [Entry::STATUS_VALID, $future, $today, null, Entry::STATUS_REVIEW],
            [Entry::STATUS_REVIEW, $future, null, null, Entry::STATUS_PENDING],
            [Entry::STATUS_PENDING, $today, $future, null, Entry::STATUS_VALID],
            [Entry::STATUS_AUTO, null, null, null, Entry::STATUS_AUTO],
        ];
        foreach ($rows as [$stored, $effective, $review, $published, $expected]) {
            $entry = $this->createEntry(Space::findOne(4), 'Consistent status regression');
            $entry->updateAttributes(['status' => $stored, 'effective_date' => $effective, 'review_date' => $review, 'published_at' => $published]);
            $provider = (new EntrySearch())->search(['EntrySearch' => ['status' => $expected]]);
            $provider->query->andWhere([Entry::tableName() . '.id' => $entry->id]);
            $this->assertSame(1, (int)$provider->query->count());
            \humhub\modules\sociolog\services\SociologStatusService::run();
            $entry->refresh();
            $this->assertSame($expected, $entry->status);
        }
    }

    public function testOrganStoresIntegerTimestamps(): void
    {
        $this->becomeUser('User1');
        $organ = new Organ(['name' => 'Timestamp regression']);
        $before = time();
        $this->assertTrue($organ->save(), json_encode($organ->getErrors()));
        $organ->refresh();
        $this->assertGreaterThanOrEqual($before, (int)$organ->created_at);
        $this->assertLessThanOrEqual(time(), (int)$organ->updated_at);
    }

    public function testUnchangedProtocolsRetainReviewReferenceAndRemovalIsRejected(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(4));
        $protocol = new Protocol(['entry_id' => $entry->id, 'title' => 'Reviewed protocol', 'url' => 'https://example.org/review']);
        $this->assertTrue($protocol->save());
        $review = new EntryReview(['entry_id' => $entry->id, 'protocol_id' => $protocol->id, 'result' => EntryReview::RESULT_CONFIRMED, 'justification' => 'Confirmed', 'created_at' => time()]);
        $this->assertTrue($review->save());
        $method = new \ReflectionMethod(EntryController::class, 'replaceProtocols');
        $method->setAccessible(true);
        $controller = new EntryController('entry', Yii::$app->getModule('sociolog'));
        $request = Yii::$app->request;
        $body = $request->getBodyParams();
        try {
            $request->setBodyParams(['protocol_title' => [$protocol->title], 'protocol_url' => [$protocol->url]]);
            $method->invoke($controller, (int)$entry->id);
            $review->refresh();
            $this->assertSame((int)$protocol->id, (int)$review->protocol_id);
            $this->assertSame(1, (int)Protocol::find()->where(['entry_id' => $entry->id])->count());
            $request->setBodyParams([]);
            try {
                $method->invoke($controller, (int)$entry->id);
                $this->fail('A referenced protocol must not be removed.');
            } catch (\DomainException $expected) {
                $this->assertNotNull(Protocol::findOne($protocol->id));
            }
        } finally {
            $request->setBodyParams($body);
        }
    }

    public function testHistoricalImportUpdatesActualContentRecord(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $title = 'Historical import regression ' . bin2hex(random_bytes(6));
        SociologImportService::import(['rows' => [[
            'ready' => true, 'spaceId' => 4, 'decisionTypeId' => DecisionType::find()->select('id')->scalar(),
            'title' => $title, 'decision' => 'Historical decision', 'decisionDate' => '2020-03-04', 'reviewDate' => null,
        ]]]);
        $entry = Entry::findOne(['title' => $title]);
        $this->assertNotNull($entry);
        $this->assertNotNull($entry->content->id);
        $this->assertStringStartsWith('2020-03-04', $entry->content->created_at);
        $this->assertSame('2020-03-15', $entry->effective_date);
    }

    public function testReviewOnlyAuthorIsTransferredToArchiveAccount(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(4));
        $deletedUser = User::findOne(3);
        $archiveUser = User::findOne(1);
        $review = new EntryReview(['entry_id' => $entry->id, 'result' => EntryReview::RESULT_CONFIRMED, 'justification' => 'Archive regression', 'created_at' => time(), 'created_by' => $deletedUser->id]);
        $this->assertTrue($review->save());
        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->set('preserveEntriesOnUserDelete', true);
        $settings->set('archiveUserId', $archiveUser->id);
        try {
            SociologUserDeletionService::preserveEntriesForUser($deletedUser);
            $review->refresh();
            $this->assertSame((int)$archiveUser->id, (int)$review->created_by);
        } finally {
            $settings->set('preserveEntriesOnUserDelete', false);
        }
    }
}
