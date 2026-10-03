<?php

use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\user\widgets\UserPickerField;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var humhub\modules\sociolog\models\SpaceWriterForm $model */
/** @var humhub\modules\space\models\Space $space */

$this->title = Yii::t('SociologModule.base', 'Schreibberechtigung für {space}', ['space' => $space->name]);
?>

<div class="panel panel-default">
  <div class="panel-heading"><h1 class="h5 mb-0"><?= Html::encode($this->title) ?></h1></div>
  <div class="panel-body">
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'writerMode')->radioList([
      SpaceConfig::WRITER_MODE_SPACE_ADMINS => Yii::t('SociologModule.base', 'Alle Space-Administrator:innen'),
      SpaceConfig::WRITER_MODE_SELECTED => Yii::t('SociologModule.base', 'Nur ausgewählte zuständige Personen'),
    ])->hint(Yii::t('SociologModule.base', 'Bei der zweiten Variante verlieren nicht ausgewählte Space-Administrator:innen ihr automatisches Schreibrecht im Logbuch.')) ?>

    <div id="sociolog-selected-writers">
      <?= $form->field($model, 'writerUsers')->widget(UserPickerField::class, [
        'maxSelection' => 0,
      ])->hint(Yii::t('SociologModule.base', 'Die ausgewählten Personen müssen Mitglied dieses Spaces sein.')) ?>
    </div>

    <?= Html::submitButton(
      '<i class="fa fa-save me-1" aria-hidden="true"></i>' . Yii::t('SociologModule.base', 'Speichern'),
      ['class' => 'btn btn-primary']
    ) ?>
    <?= Html::a(Yii::t('SociologModule.base', 'Abbrechen'), ['permissions'], ['class' => 'btn btn-default']) ?>

    <?php ActiveForm::end(); ?>
  </div>
</div>

<?php
$selectedMode = json_encode(SpaceConfig::WRITER_MODE_SELECTED);
$this->registerJs(<<<JS
(function () {
    const container = document.getElementById('sociolog-selected-writers');
    const radios = document.querySelectorAll('input[name="SpaceWriterForm[writerMode]"]');

    function updateWriterPickerVisibility() {
        const selected = document.querySelector('input[name="SpaceWriterForm[writerMode]"]:checked');
        container.hidden = !selected || selected.value !== {$selectedMode};
    }

    radios.forEach(function (radio) {
        radio.addEventListener('change', updateWriterPickerVisibility);
    });
    updateWriterPickerVisibility();
})();
JS);
?>
