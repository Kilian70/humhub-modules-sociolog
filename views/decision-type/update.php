<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model humhub\modules\sociolog\models\DecisionType */

$this->title = Yii::t('SociologModule.base', 'Entscheid-Typ bearbeiten');
$this->params['breadcrumbs'][] = ['label' => Yii::t('SociologModule.base', 'Entscheid-Typen'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<!-- ============================================================
     🔹 Header
============================================================ -->
<div class="decision-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 p-3 rounded"
     style="background: linear-gradient(90deg, #6a5af9, #9b8cff); color: #fff;">
  <h4 class="mb-0 fw-semibold">
    <i class="fa-solid fa-list me-2"></i>
    <?= Html::encode($this->title) ?>
  </h4>

  <?= Html::a(
      '<i class="fa-solid fa-arrow-left me-1"></i> ' . Yii::t('SociologModule.base', 'Zurück'),
      ['index'],
      ['class' => 'btn btn-light btn-sm text-dark']
  ) ?>
</div>

<!-- ============================================================
     🔹 Formular (bindet _form.php ein)
============================================================ -->
<div class="card shadow-sm">
  <div class="card-body">
    <?= $this->render('_form', ['model' => $model]) ?>
  </div>
</div>

<style>
.decision-header h4 {
  margin: 0;
  font-weight: 600;
  font-size: 1.25rem;
}
@media (max-width: 576px) {
  .decision-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}
</style>
