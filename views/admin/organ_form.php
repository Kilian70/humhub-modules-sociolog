<?php

use yii\helpers\Html;
use humhub\modules\ui\form\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use humhub\modules\sociolog\models\Organ;
use humhub\modules\space\models\Space;

$this->title = $model->isNewRecord ? 'Organ erstellen' : 'Organ bearbeiten';

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
    ['prompt' => 'Kein Parent']
) ?>

<?= $form->field($model, 'organ_space_id')->dropDownList(
    ArrayHelper::map(
        Space::find()->orderBy(['name' => SORT_ASC])->all(),
        'id',
        'name'
    ),
    ['prompt' => 'Space auswählen']
) ?>

<?= $form->field($model, 'sort_order')->input('number') ?>

<div class="form-group mt-3">
    <?= Html::submitButton(
        $model->isNewRecord ? 'Erstellen' : 'Speichern',
        ['class' => 'btn btn-success']
    ) ?>

    <?= Html::a(
        'Abbrechen',
        ['organs'],
        ['class' => 'btn btn-default']
    ) ?>
</div>

<?php ActiveForm::end(); ?>