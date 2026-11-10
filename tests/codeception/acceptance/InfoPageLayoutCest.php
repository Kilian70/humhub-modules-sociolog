<?php

namespace sociolog\acceptance;

use humhub\modules\sociolog\models\SettingsForm;
use PHPUnit\Framework\Assert;
use sociolog\AcceptanceTester;
use Yii;

class InfoPageLayoutCest
{
    private function openSettings(AcceptanceTester $I): void
    {
        $I->amOnPage('/sociolog/admin/index');
        $I->waitForElementVisible('#settingsform-infopagelayout', 20);
        Assert::assertCount(1, $I->grabMultiple('#settingsform-infopagelayout'));
    }

    private function save(AcceptanceTester $I): void
    {
        $I->executeJS('document.querySelector("#settingsform-infopagelayout").dataset.sociologBeforeSave="1";');
        $I->click(['xpath' => '//form[.//select[@id="settingsform-infopagelayout"]]//button[@type="submit"]']);
        $I->waitForJS('var s=document.querySelector("#settingsform-infopagelayout"); return !!s && !s.dataset.sociologBeforeSave;', 20);
        $I->dontSeeElement("form .has-error");
        $I->waitForElementVisible('#settingsform-infopagelayout', 10);
    }

    public function switchLayoutsRetainingUploadedImageAndExistingCards(AcceptanceTester $I): void
    {
        $s = Yii::$app->getModule('sociolog')->settings;
        $s->set('infoPageEnabled', true);
        $s->set('infoPageLayout', SettingsForm::INFO_LAYOUT_CARDS);
        $s->set('infoBoardText', '');
        $s->set('infoProcessText', 'Existing card content remains intact');
        $s->set('notificationRecipientMode', SettingsForm::NOTIFICATION_MODE_ALL);
        $s->set('showReviewInCalendar', false);
        $s->set('preserveEntriesOnUserDelete', false);
        $I->amAdmin();
        $this->openSettings($I);
        Assert::assertSame('cards', $I->grabValueFrom('#settingsform-infopagelayout'));
        $I->dontSeeElement('[data-sociolog-info-layout="board"]');
        $I->selectOption('#settingsform-infopagelayout', 'board');
        $I->waitForElementVisible('#sociolog-info-board .ProseMirror', 10);
        $I->scrollTo('#sociolog-info-board');
        $I->click('#sociolog-info-board .ProseMirror');
        $I->pressKey('#sociolog-info-board .ProseMirror', 'Board text with an image and https://example.org/infoboard ');
        $I->executeJS('window.sociologUploadResult=null; jQuery("#sociolog-info-board-file-upload").on("fileuploaddone.sociologTest fileuploadfail.sociologTest",function(e,d){window.sociologUploadResult={status:d.jqXHR ? d.jqXHR.status : 0,result:d.result || (d.jqXHR ? d.jqXHR.responseText : null)};});');
        // Follow the editor's insert menu so its upload handler inserts the returned file.
        $I->click('#sociolog-info-board .ProseMirror-menu-insert-dropdown');
        $I->waitForElementVisible('#sociolog-info-board .ProseMirror-menu-uploadFile', 5);
        $I->click('#sociolog-info-board .ProseMirror-menu-uploadFile');
        $I->attachFile('#sociolog-info-board-file-upload', 'info-board.png');
        $I->waitForJS('return window.sociologUploadResult !== null;', 20);
        $upload = $I->executeJS('return window.sociologUploadResult;');
        Assert::assertSame(200, $upload['status'], json_encode($upload));
        Assert::assertEmpty($upload['result']['files'][0]['error'] ?? null, json_encode($upload));
        $I->waitForJS('return !!document.querySelector("#sociolog-info-board .ProseMirror img");', 20);
        $I->makeScreenshot('info-board-editor-with-image');
        $this->save($I);
        $board = $I->grabValueFrom('SettingsForm[infoBoardText]');
        Assert::assertStringContainsString('Board text with an image', $board);
        Assert::assertStringContainsString('file-guid:', $board);
        $I->amOnPage('/sociolog/info/index');
        $I->waitForElementVisible('.sociolog-info-board', 15);
        $I->see('Board text with an image', '.sociolog-info-board');
        $I->waitForJS('var i=document.querySelector(".sociolog-info-board img"); return !!i && i.complete && i.naturalWidth > 0;', 20);
        $I->makeScreenshot('info-board-saved');
        $this->openSettings($I);
        $I->selectOption('#settingsform-infopagelayout', 'cards');
        $I->waitForElementVisible('[data-sociolog-info-layout="cards"]', 10);
        Assert::assertSame('Existing card content remains intact', $I->grabValueFrom('SettingsForm[infoProcessText]'));
        Assert::assertSame($board, $I->grabValueFrom('SettingsForm[infoBoardText]'));
        $this->save($I);
        $I->amOnPage('/sociolog/info/index');
        $I->waitForElementVisible('.sociolog-info-grid', 15);
        $I->see('Existing card content remains intact');
        $I->dontSeeElement('.sociolog-info-board');
        $I->makeScreenshot('info-cards-preserved');
        $this->openSettings($I);
        $I->selectOption('#settingsform-infopagelayout', 'board');
        $I->waitForElementVisible('[data-sociolog-info-layout="board"]', 10);
        Assert::assertSame($board, $I->grabValueFrom('SettingsForm[infoBoardText]'));
        $this->save($I);
        $I->amOnPage('/sociolog/info/index');
        $I->waitForElementVisible('.sociolog-info-board', 15);
        $I->waitForJS('var i=document.querySelector(".sociolog-info-board img"); return !!i && i.complete && i.naturalWidth > 0;', 20);
        $I->see('Board text with an image');
        $I->makeScreenshot('info-board-restored-with-image');
    }
}
