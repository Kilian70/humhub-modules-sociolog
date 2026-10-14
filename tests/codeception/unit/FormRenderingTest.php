<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\space\models\Space;
use sociolog\SociologTestCase;
use Yii;

class FormRenderingTest extends SociologTestCase
{
    public function testNativeActiveFormAndGeneratedReturnUrls(): void
    {
        $this->disableNotifications();
        $this->becomeUser('User1');
        $entry = $this->createEntry(Space::findOne(4));
        $original = Yii::$app->request->getQueryParams();
        try {
            $originalUrl = Yii::$app->request->getUrl();
        } catch (\yii\base\InvalidConfigException $exception) {
            $originalUrl = null;
        }
        Yii::$app->request->setUrl('/index.php?r=sociolog%2Fentry%2Fupdate&id=' . $entry->id);
        try {
            foreach (['cards', 'table', 'invalid/view'] as $mode) {
                Yii::$app->request->setQueryParams(['view' => $mode]);
                Yii::$app->session->set('sociologViewMode', 'table');
                $html = Yii::$app->view->renderFile(Yii::getAlias('@sociolog/views/entry/_form.php'), ['model' => $entry]);
                // Optional fixture for the separate browser tests; no application data.
                if ($scriptFile = getenv('SOCIOLOG_BROWSER_FORM_SCRIPT')) {
                    foreach (Yii::$app->view->js[\yii\web\View::POS_READY] ?? [] as $script) {
                        if (str_contains($script, 'click.sociologProtocols')) {
                            file_put_contents($scriptFile, $script);
                        }
                    }
                }
                $dom = new \DOMDocument();
                @$dom->loadHTML($html);
                $form = $dom->getElementsByTagName('form')->item(0);
                $this->assertNotNull($form);
                $this->assertFalse($form->hasAttribute('data-ui-init'));
                $this->assertFalse($form->hasAttribute('data-ui-widget'));
                $this->assertSame('post', strtolower($form->getAttribute('method')));
                $this->assertStringContainsString('view=' . ($mode === 'cards' ? 'cards' : 'table'), $form->getAttribute('data-sociolog-return-url'));
                $this->assertStringContainsString('sociolog', $form->getAttribute('data-sociolog-return-url'));
                $this->assertStringNotContainsString('invalid/view', $form->getAttribute('data-sociolog-return-url'));
                $this->assertStringContainsString('Entry[title]', $html);
            }
        } finally {
            Yii::$app->request->setQueryParams($original);
            Yii::$app->request->setUrl($originalUrl);
        }
    }
}
