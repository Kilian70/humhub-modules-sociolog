<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\sociolog\controllers\EntryController;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\EntryReview;
use humhub\modules\sociolog\models\Protocol;
use humhub\modules\sociolog\models\ReviewForm;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use sociolog\SociologTestCase;
use Yii;
use yii\web\ForbiddenHttpException;

class ReviewMaintenanceTest extends SociologTestCase
{
    private $oldController;
    private $oldBody;
    private $oldMethod;
    private $oldUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oldController = Yii::$app->controller;
        $this->oldBody = Yii::$app->request->getBodyParams();
        $this->oldMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        try { $this->oldUrl = Yii::$app->request->getUrl(); } catch (\yii\base\InvalidConfigException $e) { $this->oldUrl = null; }
        Yii::$app->request->setUrl('/index-test.php?r=sociolog%2Fentry%2Freview');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Yii::$app->request->setBodyParams([]);
        $this->disableNotifications();
        $this->becomeUser('User1');
        $settings = Yii::$app->getModule('sociolog')->settings;
        foreach (['lockPublishedEntries' => true, 'limitedReviewMaintenanceEnabled' => true,
            'decisionWorkflowEnabled' => false, 'showReviewInCalendar' => false, 'readerMode' => 'all'] as $key => $value) {
            $settings->set($key, $value);
        }
        foreach (['managerUsers', 'managerGroups', 'writerUsers', 'writerGroups'] as $key) {
            $settings->setSerialized($key, []);
        }
        $config = SpaceConfig::findOne(['space_id' => 4]) ?? new SpaceConfig(['space_id' => 4]);
        $config->enabled = 1;
        $config->writer_mode = SpaceConfig::WRITER_MODE_SELECTED;
        $config->setWriterUserGuids([Yii::$app->user->identity->guid]);
        $this->assertTrue($config->save(), json_encode($config->getErrors()));
        Entry::resetRuntimeCaches();
    }

    protected function tearDown(): void
    {
        Yii::$app->controller = $this->oldController;
        Yii::$app->request->setBodyParams($this->oldBody);
        Yii::$app->request->setUrl($this->oldUrl);
        if ($this->oldMethod === null) unset($_SERVER['REQUEST_METHOD']);
        else $_SERVER['REQUEST_METHOD'] = $this->oldMethod;
        parent::tearDown();
    }

    private function entry(): Entry
    {
        $entry = $this->createEntry(Space::findOne(4), 'Published decision');
        $entry->updateAttributes(['published_at' => '2020-01-01', 'effective_date' => '2020-01-12',
            'review_date' => date('Y-m-d'), 'status' => Entry::STATUS_REVIEW, 'description' => 'Original justification']);
        return $entry;
    }

    private function controller(): EntryController
    {
        $controller = new class('entry', Yii::$app->getModule('sociolog')) extends EntryController {
            public function render($view, $params = []) { return $this->renderPartial($view, $params); }
        };
        $controller->setViewPath(Yii::getAlias('@sociolog/views/entry'));
        Yii::$app->controller = $controller;
        return $controller;
    }

    private function post(array $values): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams(['ReviewForm' => $values,
            'Entry' => ['title' => 'Tampered title', 'description' => 'Tampered original justification', 'organ' => 1]]);
    }

    public function testDueReviewIsAccessibleWithoutDecisionWorkflow(): void
    {
        $entry = $this->entry();
        $this->assertFalse($entry->canWrite());
        $this->assertTrue($entry->canMaintainReview());
        $controller = $this->controller();
        $html = $controller->actionView($entry->id);
        $this->assertStringContainsString('Überprüfung dokumentieren', $html);
        $html = $controller->actionReview($entry->id);
        $this->assertStringContainsString('ReviewForm[justification]', $html);
        $this->assertStringContainsString('ReviewForm[reviewDate]', $html);
        $this->assertStringContainsString('ReviewForm[result]', $html);
        $this->assertStringContainsString('ReviewForm[replacementUrl]', $html);
        $this->assertStringNotContainsString('Entry[description]', $html);
        $scripts = implode("\n", Yii::$app->view->js[\yii\web\View::POS_READY] ?? []);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $input = (new \DOMXPath($dom))->query('//textarea[@name="ReviewForm[justification]"]')->item(0);
        $this->assertNotNull($input);
        $this->assertStringContainsString('"input":"#' . $input->getAttribute('id') . '"', $scripts);
        $this->assertStringContainsString('data-backup-interval="0"', $html);
    }

    public function testLimitedPostPreservesOriginalAndExistingProtocols(): void
    {
        $entry = $this->entry();
        $before = $entry->getAttributes();
        $old = new Protocol(['entry_id' => $entry->id, 'title' => 'Original protocol', 'url' => 'https://example.org/original']);
        $this->assertTrue($old->save());
        $old->refresh();
        $oldAttributes = $old->getAttributes();
        $next = date('Y-m-d', strtotime('+1 year'));
        $this->post(['result' => EntryReview::RESULT_CONFIRMED, 'justification' => 'New review justification',
            'reviewDate' => $next, 'protocolTitle' => 'Additional protocol', 'protocolUrl' => 'https://example.org/new']);
        $this->controller()->actionReview($entry->id);
        $entry->refresh();
        $this->assertSame($next, $entry->review_date);
        $this->assertSame(Entry::STATUS_VALID, $entry->status);
        foreach ($before as $key => $value) {
            if (!in_array($key, ['review_date', 'status'], true)) $this->assertSame($value, $entry->$key, $key);
        }
        $old->refresh();
        $this->assertSame($oldAttributes, $old->getAttributes());
        $review = EntryReview::findOne(['entry_id' => $entry->id]);
        $this->assertNotNull($review);
        $this->assertSame(EntryReview::RESULT_CONFIRMED, $review->result);
        $this->assertSame('New review justification', $review->justification);
        $this->assertSame((int)Yii::$app->user->id, (int)$review->created_by);
        $this->assertSame($before['review_date'], $review->previous_review_date);
        $this->assertSame($next, $review->next_review_date);
        $this->assertSame('https://example.org/new', $review->protocol->url);
        $this->assertFalse($entry->canMaintainReview());
    }

    public function testGetWithFormBodyNeverSavesReview(): void
    {
        $entry = $this->entry();
        $before = $entry->getAttributes();
        $this->post(['justification' => 'Must not save on GET', 'reviewDate' => date('Y-m-d', strtotime('+1 year'))]);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->controller()->actionReview($entry->id);
        $entry->refresh();
        $this->assertSame($before, $entry->getAttributes());
        $this->assertFalse(EntryReview::find()->where(['entry_id' => $entry->id])->exists());
    }

    public function testLimitedInputRequiresNewJustificationFutureDateAndValidLink(): void
    {
        foreach ([['justification' => ''], ['reviewDate' => date('Y-m-d')], ['reviewDate' => '2026-02-30'],
            ['protocolTitle' => 'Missing link'], ['protocolTitle' => 'Invalid link', 'protocolUrl' => 'javascript:alert(1)']] as $invalid) {
            $entry = $this->entry();
            $before = $entry->getAttributes();
            $this->post(array_merge(['justification' => 'Reason', 'reviewDate' => date('Y-m-d', strtotime('+1 year'))], $invalid));
            $this->controller()->actionReview($entry->id);
            $entry->refresh();
            $this->assertSame($before, $entry->getAttributes());
            $this->assertFalse(EntryReview::find()->where(['entry_id' => $entry->id])->exists());
        }
    }

    public function testMaintenanceRequiresEnabledOptionDueDateAndCurrentResponsibility(): void
    {
        $entry = $this->entry();
        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->set('limitedReviewMaintenanceEnabled', false);
        $this->assertFalse($entry->canMaintainReview());
        $settings->set('limitedReviewMaintenanceEnabled', true);
        $entry->review_date = date('Y-m-d', strtotime('+1 day'));
        $this->assertFalse($entry->canMaintainReview());
        $entry->review_date = null;
        $this->assertFalse($entry->canMaintainReview());
        $entry->review_date = date('Y-m-d');
        $membership = Membership::findOne(['space_id' => 4, 'user_id' => Yii::$app->user->id]);
        $membership->updateAttributes(['status' => Membership::STATUS_INVITED]);
        $this->assertFalse($entry->canMaintainReview());
        $this->expectException(ForbiddenHttpException::class);
        $this->controller()->actionReview($entry->id);
    }

    public function testForeignUserCannotReviewAndLimitedUserCannotUseUpdate(): void
    {
        $entry = $this->entry();
        $this->post(['title' => 'Changed']);
        $this->controller()->actionUpdate($entry->id);
        $entry->refresh();
        $this->assertSame('Published decision', $entry->title);
        $this->becomeUser('User3');
        $this->assertFalse($entry->canMaintainReview());
        $this->expectException(ForbiddenHttpException::class);
        $this->controller()->actionReview($entry->id);
    }

    public function testLimitedReviewNeverReactivatesProtectedStatus(): void
    {
        $entry = $this->entry();
        $entry->updateAttributes(['status' => Entry::STATUS_EXPIRED]);
        $this->post(['justification' => 'Review recorded without reactivation', 'reviewDate' => date('Y-m-d', strtotime('+1 year'))]);
        $this->controller()->actionReview($entry->id);
        $entry->refresh();
        $this->assertSame(Entry::STATUS_EXPIRED, $entry->status);
    }

    public function testReviewAlwaysOpensNewJustificationWhenLimitedOptionIsDisabled(): void
    {
        $entry = $this->entry();
        Yii::$app->getModule('sociolog')->settings->setSerialized('managerUsers', [Yii::$app->user->identity->guid]);
        $this->assertTrue($entry->canWrite());
        Yii::$app->getModule('sociolog')->settings->set('limitedReviewMaintenanceEnabled', false);
        $html = $this->controller()->actionReview($entry->id);
        $this->assertStringContainsString('ReviewForm[result]', $html);
        $this->assertStringContainsString('ReviewForm[justification]', $html);
        $this->post(['result' => EntryReview::RESULT_CONFIRMED, 'justification' => 'New reason with option disabled', 'reviewDate' => date('Y-m-d', strtotime('+1 year'))]);
        $this->controller()->actionReview($entry->id);
        $entry->refresh();
        $this->assertSame('Original justification', $entry->description);
        $this->assertSame('New reason with option disabled', EntryReview::findOne(['entry_id' => $entry->id])->justification);
    }
    public function testLimitedReviewCanRepealWithoutNextDate(): void
    {
        $entry = $this->entry();
        $before = $entry->getAttributes();
        $this->post(['result' => EntryReview::RESULT_REPEALED, 'justification' => 'No longer needed',
            'reviewDate' => '2020-01-01', 'replacementUrl' => 'https://example.org/ignored']);
        $this->controller()->actionReview($entry->id);
        $entry->refresh();
        $this->assertSame(Entry::STATUS_EXPIRED, $entry->status);
        $this->assertNull($entry->review_date);
        foreach ($before as $key => $value) {
            if (!in_array($key, ['review_date', 'status'], true)) $this->assertSame($value, $entry->$key, $key);
        }
        $review = EntryReview::findOne(['entry_id' => $entry->id]);
        $this->assertSame(EntryReview::RESULT_REPEALED, $review->result);
        $this->assertSame('No longer needed', $review->justification);
        $this->assertNull($review->replacement_url);
        $this->assertNull($review->next_review_date);
        $this->assertFalse($entry->canMaintainReview());
        $this->assertNull(\humhub\modules\sociolog\services\SociologStatusService::calculateStatus($entry->getAttributes(), new \DateTimeImmutable('+3 years')));
    }

    public function testLimitedReviewCanReplaceWithLinkAndPreservesOriginal(): void
    {
        $entry = $this->entry();
        $before = $entry->getAttributes();
        Yii::$app->getModule('sociolog')->settings->set('extendedStatusesEnabled', false);
        $link = 'https://example.org/index.php?r=sociolog%2Fentry%2Fview&id=123';
        $this->post(['result' => EntryReview::RESULT_REPLACED, 'justification' => 'New decision supersedes this one',
            'replacementUrl' => $link, 'protocolTitle' => 'Minutes', 'protocolUrl' => 'https://example.org/minutes']);
        $controller = $this->controller();
        $controller->actionReview($entry->id);
        $entry->refresh();
        $this->assertSame(Entry::STATUS_REPLACED, $entry->status);
        $this->assertNull($entry->review_date);
        foreach ($before as $key => $value) {
            if (!in_array($key, ['review_date', 'status'], true)) $this->assertSame($value, $entry->$key, $key);
        }
        $review = EntryReview::findOne(['entry_id' => $entry->id]);
        $this->assertSame(EntryReview::RESULT_REPLACED, $review->result);
        $this->assertSame($link, $review->replacement_url);
        $this->assertSame($link, $review->safeReplacementUrl);
        $this->assertSame('https://example.org/minutes', $review->protocol->url);
        $this->assertNull($review->next_review_date);
        $this->assertFalse($entry->canMaintainReview());
        $html = $controller->actionView($entry->id);
        $this->assertStringContainsString(Yii::t('SociologModule.base', 'Neuen Beschluss öffnen'), $html);
        $this->assertStringContainsString(\yii\helpers\Html::encode($link), $html);
        $this->assertNull(\humhub\modules\sociolog\services\SociologStatusService::calculateStatus($entry->getAttributes(), new \DateTimeImmutable('+3 years')));
    }

    public function testInvalidResultsReasonsAndReplacementLinksDoNotWrite(): void
    {
        foreach ([['result' => 'valid'], ['replacementUrl' => ''],
            ['replacementUrl' => 'javascript:alert(1)'], ['replacementUrl' => '/relative/path'],
            ['replacementUrl' => 'https://' . str_repeat('a', 1000) . '.org'],
            ['justification' => ''], ['justification' => '   ']] as $invalid) {
            $entry = $this->entry();
            $before = $entry->getAttributes();
            $this->post(array_merge(['result' => EntryReview::RESULT_REPLACED, 'justification' => 'Reason',
                'replacementUrl' => 'https://example.org/new'], $invalid));
            $this->controller()->actionReview($entry->id);
            $entry->refresh();
            $this->assertSame($before, $entry->getAttributes());
            $this->assertFalse(EntryReview::find()->where(['entry_id' => $entry->id])->exists());
        }
    }

    public function testManagerCanReplaceWhenLimitedMaintenanceIsDisabled(): void
    {
        $entry = $this->entry();
        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->setSerialized('managerUsers', [Yii::$app->user->identity->guid]);
        $settings->set('limitedReviewMaintenanceEnabled', false);
        $this->post(['result' => EntryReview::RESULT_REPLACED, 'justification' => 'Manager replacement',
            'replacementUrl' => 'https://example.org/replacement']);
        $this->controller()->actionReview($entry->id);
        $entry->refresh();
        $this->assertSame(Entry::STATUS_REPLACED, $entry->status);
        $this->assertNull($entry->review_date);
        $this->assertSame('Original justification', $entry->description);
    }

    public function testUnsafeStoredReplacementUrlIsNeverRendered(): void
    {
        $entry = $this->entry();
        $review = new EntryReview(['entry_id' => $entry->id, 'result' => EntryReview::RESULT_REPLACED,
            'justification' => 'Old imported record', 'replacement_url' => 'javascript:alert(42)', 'created_at' => time()]);
        $this->assertFalse($review->validate());
        $this->assertTrue($review->save(false));
        $this->assertNull($review->safeReplacementUrl);
        $html = $this->controller()->actionView($entry->id);
        $this->assertStringNotContainsString('javascript:alert(42)', $html);
        $this->assertStringNotContainsString(Yii::t('SociologModule.base', 'Neuen Beschluss öffnen'), $html);
    }

    public function testReplacementDeniedWhenOptionDisabledAndUserHasNoFullRights(): void
    {
        $entry = $this->entry();
        Yii::$app->getModule('sociolog')->settings->set('limitedReviewMaintenanceEnabled', false);
        $this->post(['result' => EntryReview::RESULT_REPLACED, 'justification' => 'Not allowed',
            'replacementUrl' => 'https://example.org/new']);
        try {
            $this->controller()->actionReview($entry->id);
            $this->fail('Replacement must be denied.');
        } catch (ForbiddenHttpException $e) {
            $entry->refresh();
            $this->assertSame(Entry::STATUS_REVIEW, $entry->status);
            $this->assertFalse(EntryReview::find()->where(['entry_id' => $entry->id])->exists());
        }
    }
}
