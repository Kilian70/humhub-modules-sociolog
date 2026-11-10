<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\file\models\File;
use humhub\modules\sociolog\controllers\AdminController;
use humhub\modules\sociolog\controllers\InfoController;
use humhub\modules\sociolog\models\InfoBoard;
use humhub\modules\sociolog\models\SettingsForm;
use humhub\modules\user\models\User;
use sociolog\SociologTestCase;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class InfoPageLayoutTest extends SociologTestCase
{
    private $oldController;
    private $oldUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oldController = Yii::$app->controller;
        try { $this->oldUrl = Yii::$app->request->getUrl(); } catch (\yii\base\InvalidConfigException $e) { $this->oldUrl = null; }
        Yii::$app->request->setUrl('/index-test.php?r=sociolog%2Finfo%2Findex');
        $this->becomeUser('Admin');
        $s = Yii::$app->getModule('sociolog')->settings;
        $s->set('infoPageEnabled', true);
        $s->set('showReviewInCalendar', false);
        $s->set('notificationRecipientMode', SettingsForm::NOTIFICATION_MODE_ALL);
        $s->set('preserveEntriesOnUserDelete', false);
        $s->delete('infoPageLayout');
        $s->delete('infoBoardText');
    }

    protected function tearDown(): void
    {
        Yii::$app->controller = $this->oldController;
        Yii::$app->request->setUrl($this->oldUrl);
        parent::tearDown();
    }

    private function html(): string
    {
        $c = new class('info', Yii::$app->getModule('sociolog')) extends InfoController {
            public function render($view, $params = []) { return $this->renderPartial($view, $params); }
        };
        $c->setViewPath(Yii::getAlias('@sociolog/views/info'));
        Yii::$app->controller = $c;
        return $c->actionIndex();
    }

    private function form(): SettingsForm
    {
        $form = new SettingsForm();
        $form->loadSettings();
        return $form;
    }

    private function save(SettingsForm $form): void
    {
        $this->assertTrue($form->save(), json_encode($form->errors));
    }

    public function testExistingPageDefaultsToCardsAndOnlySelectedLayoutIsRendered(): void
    {
        $s = Yii::$app->getModule('sociolog')->settings;
        $s->set('infoProcessText', 'Existing card content');
        $s->set('infoBoardText', 'Independent board content');
        $s->set('infoIntroText', 'Shared introduction');
        $this->assertSame(SettingsForm::INFO_LAYOUT_CARDS, $this->form()->infoPageLayout);
        $html = $this->html();
        $this->assertStringContainsString('Existing card content', $html);
        $this->assertStringNotContainsString('Independent board content', $html);
        $this->assertStringContainsString('Shared introduction', $html);
        $s->set('infoPageLayout', SettingsForm::INFO_LAYOUT_BOARD);
        $html = $this->html();
        $this->assertStringContainsString('Independent board content', $html);
        $this->assertStringNotContainsString('Existing card content', $html);
        $this->assertStringContainsString('Shared introduction', $html);
    }

    public function testSwitchingRepeatedlyPreservesAllCardFieldsAndBoardText(): void
    {
        $s = Yii::$app->getModule('sociolog')->settings;
        $values = [];
        foreach (['Process','Permissions','Status','Objection','Review','Documents','Guideline','Examples'] as $section) {
            $values['info'.$section.'Text'] = 'Original '.$section.' content & links';
            $s->set('info'.$section.'Text', $values['info'.$section.'Text']);
        }
        $board = "## Free board\n\n**Text** and [documentation](https://example.org/guide)";
        $form = $this->form();
        $form->infoBoardText = $board;
        $this->save($form);
        $id = InfoBoard::current()->id;
        foreach (['board','cards','board','cards','board'] as $layout) {
            $form = $this->form();
            $form->infoPageLayout = $layout;
            $this->save($form);
            $loaded = $this->form();
            $this->assertSame($layout, $loaded->infoPageLayout);
            $this->assertSame($board, $loaded->infoBoardText);
            $this->assertSame((int)$id, (int)InfoBoard::current()->id);
            foreach ($values as $name => $value) $this->assertSame($value, $loaded->$name);
        }
    }

    public function testUploadedImageIsAttachedAndReadableOnlyToAuthorizedUsersAfterSwitching(): void
    {
        $file = new File(['file_name' => 'info-board.png', 'mime_type' => 'image/png', 'public' => 0,
            'created_by' => Yii::$app->user->id]);
        $this->assertTrue($file->save());
        $file->setStoredFileContent(file_get_contents(codecept_data_dir() . 'info-board.png'));
        $form = $this->form();
        $form->infoPageLayout = 'board';
        $form->infoBoardText = '![Board image](file-guid:'.$file->guid.' "image")';
        $this->save($form);
        $board = InfoBoard::current();
        $file->refresh();
        $this->assertTrue($file->isAssignedTo($board));
        $this->assertTrue($file->canView(User::findOne(2)));
        $this->assertFalse($file->canDelete(User::findOne(2)));
        $this->assertTrue($file->canDelete(User::findOne(1)));
        $this->assertFalse($file->canView(User::findOne(5)));
        $form = $this->form(); $form->infoPageLayout = 'cards'; $this->save($form);
        $this->assertTrue(File::findOne($file->id)->isAssignedTo(InfoBoard::current()));
        $form = $this->form(); $form->infoPageLayout = 'board'; $this->save($form);
        $this->assertStringContainsString($file->guid, $this->html());
        Yii::$app->user->logout();
        $this->assertFalse(File::findOne($file->id)->canView());
    }

    public function testBoardRendersLinksAndEscapesInjectedHtml(): void
    {
        $form = $this->form(); $form->infoPageLayout = 'board';
        $form->infoBoardText = '## Example board\n\n[Read more](https://example.org/guide) <script>alert(42)</script>';
        $this->save($form);
        $html = $this->html();
        $this->assertStringContainsString('https://example.org/guide', $html);
        $this->assertStringNotContainsString('<script>alert(42)</script>', $html);
        $this->assertStringContainsString('sociolog-info-board', $html);
    }

    public function testInvalidLayoutAndOversizedBoardAreRejectedWithoutChangingSettings(): void
    {
        $form = $this->form(); $form->infoPageLayout = '../../other/view';
        $this->assertFalse($form->save());
        $this->assertNull(Yii::$app->getModule('sociolog')->settings->get('infoPageLayout'));
        $form = $this->form(); $form->infoBoardText = str_repeat('x', 15001);
        $this->assertFalse($form->save());
        Yii::$app->getModule('sociolog')->settings->set('infoPageLayout', 'unknown');
        $this->assertSame('cards', $this->form()->infoPageLayout);
        $this->assertStringContainsString('sociolog-info-grid', $this->html());
    }

    public function testUnrelatedSettingCannotExposeFilesAndNonAdminCannotSave(): void
    {
        $s = Yii::$app->getModule('sociolog')->settings;
        $s->set('infoProcessText', 'A card');
        $row = InfoBoard::findOne(['module_id'=>'sociolog','name'=>'infoProcessText']);
        $this->assertFalse($row->canView());
        $this->assertFalse($row->canEdit());
        $this->becomeUser('User1');
        $this->expectException(ForbiddenHttpException::class);
        (new AdminController('admin', Yii::$app->getModule('sociolog')))->actionIndex();
    }

    public function testDisabledPageAndItsImagesAreHiddenFromMembers(): void
    {
        $form = $this->form(); $form->infoBoardText = 'Saved board'; $this->save($form);
        $s = Yii::$app->getModule('sociolog')->settings; $s->set('infoPageEnabled', false);
        $this->assertFalse(InfoBoard::current()->canView(User::findOne(2)));
        $this->assertTrue(InfoBoard::current()->canView(User::findOne(1)));
        $this->expectException(NotFoundHttpException::class);
        $this->html();
    }
}
