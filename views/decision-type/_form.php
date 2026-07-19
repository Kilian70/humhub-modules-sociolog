<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use humhub\modules\sociolog\models\DecisionType;

$previewColor = $model->color ?: '#777777';
$previewTextColor = DecisionType::getAccessibleTextColor($previewColor);

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
                    background:<?= Html::encode($previewColor) ?>;">
        </div>

        <span id="badgePreview"
              class="badge px-3 py-2 fw-semibold"
              style="background:<?= Html::encode($previewColor) ?>;
                     color:<?= Html::encode($previewTextColor) ?>;font-size:14px;">
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
  const channels = [1, 3, 5].map(offset => {
    const value = parseInt(color.substr(offset, 2), 16) / 255;
    return value <= 0.04045
      ? value / 12.92
      : Math.pow((value + 0.055) / 1.055, 2.4);
  });
  const luminance = 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
  const contrastWhite = 1.05 / (luminance + 0.05);
  const contrastBlack = (luminance + 0.05) / 0.05;
  const textColor = contrastWhite >= contrastBlack ? '#fff' : '#000';
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
