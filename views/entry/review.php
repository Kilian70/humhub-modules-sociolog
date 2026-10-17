<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\modules\sociolog\helpers\RichTextHelper;
use humhub\modules\sociolog\models\EntryReview;
use humhub\modules\sociolog\models\ReviewForm;

/** @var yii\web\View $this */
/** @var humhub\modules\sociolog\models\Entry $model */
/** @var humhub\modules\sociolog\models\ReviewForm $formModel */

$this->title = Yii::t('SociologModule.base', 'Überprüfung dokumentieren');
$limited = $formModel->scenario === ReviewForm::SCENARIO_LIMITED;
// Jede Überprüfung beginnt mit einem eigenen Feld statt einem alten Editor-Entwurf.
$reviewEditorId = 'sociolog-review-justification-' . bin2hex(random_bytes(8));
$this->registerJs(<<<'JS'
(function () {
    var result = $('#reviewform-result');
    function updateReviewFields() {
        $('.field-reviewform-reviewdate').toggle(result.val() === 'confirmed');
        $('#reviewform-reviewdate').prop('disabled', result.val() !== 'confirmed');
        $('.field-reviewform-replacementurl').toggle(result.val() === 'replaced');
        $('#reviewform-replacementurl').prop('disabled', result.val() !== 'replaced');
    }
    result.on('change', updateReviewFields);
    updateReviewFields();
})();
JS
);
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= Html::encode($this->title) ?></strong>
        <div class="text-muted small mt-1"><?= Html::encode($model->title) ?></div>
    </div>

    <div class="panel-body">
        <p class="text-muted">
            <?= Yii::t(
                'SociologModule.base',
                $limited
                    ? 'Du kannst den Beschluss bestätigen, ausser Kraft setzen oder als ersetzt kennzeichnen. Erfasse eine neue Begründung und bei Bedarf ein zusätzliches Protokoll. Die ursprünglichen Angaben bleiben unverändert.'
                    : 'Der ursprüngliche Beschlusstext und die ursprüngliche Begründung bleiben unverändert. Dokumentiere hier das Ergebnis und eine neue Begründung der Überprüfung.'
            ) ?>
        </p>

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($formModel, 'result')->dropDownList(EntryReview::resultOptions()) ?>

        <?= $form->field($formModel, 'justification', [
            'selectors' => ['input' => '#' . $reviewEditorId . '_input'],
        ])->widget(RichTextField::class, [
            'id' => $reviewEditorId,
            'backupInterval' => 0,
            'exclude' => RichTextHelper::EXCLUDED_FEATURES,
            'pluginOptions' => ['maxHeight' => '320px'],
        ]) ?>

        <?= $form->field($formModel, 'reviewDate')->input('date')->hint(
            Yii::t('SociologModule.base', 'Nur erforderlich, wenn der Entscheid bestehen bleibt.')
        ) ?>

        <?= $form->field($formModel, 'replacementUrl')->input('url', [
            'maxlength' => true,
            'placeholder' => 'https://',
        ])->hint(Yii::t('SociologModule.base', 'Bei „Ersetzt“ ist der Link zum neuen Beschluss erforderlich.')) ?>

        <fieldset class="mt-4">
            <legend class="h5">
                <?= Yii::t('SociologModule.base', 'Neues Protokoll (optional)') ?>
            </legend>

            <?= $form->field($formModel, 'protocolTitle')->textInput([
                'maxlength' => true,
            ]) ?>

            <?= $form->field($formModel, 'protocolUrl')->input('url', [
                'maxlength' => true,
                'placeholder' => 'https://',
            ]) ?>
        </fieldset>

        <div class="mt-4">
            <?= Html::submitButton(
                Yii::t('SociologModule.base', 'Überprüfung speichern'),
                ['class' => 'btn btn-primary']
            ) ?>
            <?= Html::a(
                Yii::t('SociologModule.base', 'Abbrechen'),
                ['view', 'id' => $model->id],
                ['class' => 'btn btn-default']
            ) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
