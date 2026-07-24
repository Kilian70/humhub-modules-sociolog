<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var humhub\modules\sociolog\models\Entry $model */
/** @var humhub\modules\sociolog\models\ReviewForm $formModel */

$this->title = Yii::t('SociologModule.base', 'Überprüfung dokumentieren');
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
                'Hier können ausschließlich das nächste Überprüfungsdatum angepasst und ein zusätzliches Protokoll verlinkt werden.'
            ) ?>
        </p>

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($formModel, 'reviewDate')->input('date') ?>

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
