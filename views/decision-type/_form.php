<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model humhub\modules\sociolog\models\DecisionType */
?>

<div class="decision-type-form card shadow-sm border-0">
  <div class="card-body">

    <?php $form = ActiveForm::begin(['options' => ['class' => 'row g-4']]); ?>

    <!-- 🔹 Bezeichnung -->
    <div class="col-12 col-md-6">
      <?= $form->field($model, 'name')
          ->textInput([
              'maxlength' => true,
              'placeholder' => Yii::t('SociologModule.base', 'z. B. Grundsatzentscheid'),
              'id' => 'decisiontype-name',
          ])
          ->label(Yii::t('SociologModule.base', 'Bezeichnung des Entscheid-Typs')) ?>
    </div>

    <!-- 🔹 Farbe mit Live-Vorschau -->
    <div class="col-12 col-md-6">
      <?= $form->field($model, 'color')
          ->input('color', [
              'id' => 'colorPicker',
              'style' => 'height:45px;width:100%;padding:0;cursor:pointer;border:none;',
          ])
          ->label(Yii::t('SociologModule.base', 'Farbe')) ?>

      <div class="mt-2 d-flex align-items-center gap-3 flex-wrap">
        <div id="colorBox"
             style="width:48px;height:48px;border-radius:8px;border:1px solid #ccc;
                    background:<?= Html::encode($model->color ?: '#777') ?>;">
        </div>

        <span id="badgePreview"
              class="badge px-3 py-2 fw-semibold"
              style="background:<?= Html::encode($model->color ?: '#777') ?>;
                     color:#fff;font-size:14px;">
          <?= Html::encode($model->name ?: Yii::t('SociologModule.base', 'Vorschau')) ?>
        </span>
      </div>
    </div>

    <!-- 🔹 Beschreibung -->
    <div class="col-12">
      <?= $form->field($model, 'description')
          ->textarea([
              'rows' => 3,
              'placeholder' => Yii::t('SociologModule.base', 'Kurze Erklärung des Entscheid-Typs …')
          ])
          ->label(Yii::t('SociologModule.base', 'Beschreibung'))
          ->hint(Yii::t('SociologModule.base', 'Optional – wird in den Ansichten angezeigt.')) ?>
    </div>

    <!-- 🔹 Sortierung -->
    <div class="col-12 col-md-4">
      <?= $form->field($model, 'sort_order')
          ->input('number', ['min' => 1, 'max' => 999])
          ->label(Yii::t('SociologModule.base', 'Sortierreihenfolge'))
          ->hint(Yii::t('SociologModule.base', 'Niedrigere Zahl = weiter oben')) ?>
    </div>

    <!-- 🔹 Buttons -->
    <div class="col-12 d-flex justify-content-end gap-2 mt-4 flex-wrap">
      <?= Html::submitButton(
          '<i class="fa-solid fa-floppy-disk me-1"></i> ' .
          Yii::t('SociologModule.base', 'Speichern'),
          ['class' => 'btn btn-primary']
      ) ?>

      <?= Html::a(
          '<i class="fa-solid fa-arrow-left me-1"></i> ' .
          Yii::t('SociologModule.base', 'Zurück'),
          ['index'],
          ['class' => 'btn btn-outline-secondary']
      ) ?>
    </div>

    <?php ActiveForm::end(); ?>

  </div>
</div>

<?php
// ============================================================
// 🎨 Live-Vorschau: Farbe + Name
// ============================================================
$this->registerJs(<<<JS
function updateBadgeColor(color) {
  const textColor = (() => {
    const r = parseInt(color.substr(1,2),16);
    const g = parseInt(color.substr(3,2),16);
    const b = parseInt(color.substr(5,2),16);
    return (r+g+b)/3 < 128 ? '#fff' : '#000';
  })();
  $('#colorBox').css('background-color', color);
  $('#badgePreview').css({'background-color': color, 'color': textColor});
}

$('#colorPicker').on('input', function() {
  updateBadgeColor($(this).val());
});

$('#decisiontype-name').on('input', function() {
  const name = $(this).val().trim() || 'Vorschau';
  $('#badgePreview').text(name);
});
JS);
?>

<style>
@media (max-width: 576px) {
  .decision-type-form .card-body { padding: 1rem 0.75rem; }
  #colorBox { width: 40px; height: 40px; }
  #badgePreview { font-size: 12px; padding: 4px 8px; }
}
</style>
