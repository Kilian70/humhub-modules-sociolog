<?php

use yii\helpers\Html;
use humhub\modules\ui\form\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use humhub\modules\sociolog\models\Organ;
use humhub\modules\space\models\Space;

$this->title = $model->isNewRecord
    ? Yii::t('SociologModule.base', 'Organ erstellen')
    : Yii::t('SociologModule.base', 'Organ bearbeiten');

/* Parent-Optionen laden */
$query = Organ::find()->orderBy(['sort_order' => SORT_ASC]);

// Beim Bearbeiten darf das Organ nicht sein eigener Parent sein
if (!$model->isNewRecord) {
    $query->andWhere(['!=', 'id', $model->id]);
}

$parents = ArrayHelper::map(
    $query->all(),
    'id',
    'name'
);

?>

<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'name')->textInput() ?>

<?= $form->field($model, 'parent_id')->dropDownList(
    $parents,
    ['prompt' => Yii::t('SociologModule.base', 'Kein übergeordnetes Organ')]
) ?>

<?= $form->field($model, 'organ_space_id')->dropDownList(
    ArrayHelper::map(
        Space::find()->orderBy(['name' => SORT_ASC])->all(),
        'id',
        'name'
    ),
    ['prompt' => Yii::t('SociologModule.base', 'Space auswählen')]
) ?>

<?= $form->field($model, 'sort_order')->input('number') ?>

<div class="form-group mt-3 organ-form-actions">
    <?= Html::submitButton(
        $model->isNewRecord ? Yii::t('SociologModule.base', 'Erstellen') : Yii::t('SociologModule.base', 'Speichern'),
        ['class' => 'btn btn-success']
    ) ?>

    <?= Html::a(
        Yii::t('SociologModule.base', 'Abbrechen'),
        ['organs'],
        ['class' => 'btn btn-outline-secondary']
    ) ?>
</div>

<?php ActiveForm::end(); ?>

<?php
$this->registerCss(<<<CSS
.organ-form-actions .btn:focus-visible {
    outline: 3px solid var(--bs-primary, #4b8f29);
    outline-offset: 3px;
    box-shadow: none;
}

.organ-form-actions .btn:focus:not(:focus-visible) {
    outline: none;
    box-shadow: none;
}
CSS);
?>
