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
                    ? 'Du kannst das nächste Überprüfungsdatum setzen, die Überprüfung begründen und ein neues Protokoll verlinken. Die ursprünglichen Angaben bleiben unverändert.'
                    : 'Der ursprüngliche Beschlusstext und die ursprüngliche Begründung bleiben unverändert. Dokumentiere hier das Ergebnis und eine neue Begründung der Überprüfung.'
            ) ?>
        </p>

        <?php $form = ActiveForm::begin(); ?>

        <?php if (!$limited): ?>
            <?= $form->field($formModel, 'result')->dropDownList(EntryReview::resultOptions()) ?>
        <?php endif; ?>

        <?= $form->field($formModel, 'justification', [
            'selectors' => ['input' => '#' . $reviewEditorId . '_input'],
        ])->widget(RichTextField::class, [
            'id' => $reviewEditorId,
            'backupInterval' => 0,
            'exclude' => RichTextHelper::EXCLUDED_FEATURES,
            'pluginOptions' => ['maxHeight' => '320px'],
        ]) ?>

        <?= $form->field($formModel, 'reviewDate')->input('date')->hint($limited ? '' :
            Yii::t('SociologModule.base', 'Nur erforderlich, wenn der Entscheid bestehen bleibt.')
        ) ?>

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
