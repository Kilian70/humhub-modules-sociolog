<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model humhub\modules\sociolog\models\DecisionType */

$this->title = Yii::t('SociologModule.base', 'Neuer Entscheid-Typ');
$this->params['breadcrumbs'][] = [
    'label' => Yii::t('SociologModule.base', 'Entscheid-Typen'),
    'url' => ['index']
];
$this->params['breadcrumbs'][] = $this->title;
?>

<!-- ============================================================
     🔹 Header
============================================================ -->
<div class="decision-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <h1 class="h4 mb-0 fw-semibold text-white">
    <i class="fa-solid fa-list me-2" aria-hidden="true"></i>
    <?= Html::encode($this->title) ?>
  </h1>

  <?= Html::a(
      '<i class="fa-solid fa-arrow-left me-1"></i> ' . Yii::t('SociologModule.base', 'Zurück'),
      ['index'],
      ['class' => 'btn btn-light btn-sm text-dark']
  ) ?>
</div>

<!-- ============================================================
     🔹 Formular (bindet _form.php ein)
============================================================ -->
<div class="card shadow-sm border-0">
  <div class="card-body">
    <?= $this->render('_form', ['model' => $model]) ?>
  </div>
</div>

<!-- ============================================================
     🎨 Styles
============================================================ -->
<style>
:root {
  --decision-gradient-start: #6a5af9;
  --decision-gradient-end: #9b8cff;
}

/* Header */
.decision-header {
  background: linear-gradient(90deg, var(--decision-gradient-start), var(--decision-gradient-end));
  color: #fff;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}

.decision-header h4 {
  font-weight: 600;
  font-size: 1.25rem;
}

.decision-header .btn-light {
  background: rgba(255,255,255,0.85);
  transition: background 0.2s ease;
}
.decision-header .btn-light:hover {
  background: #fff;
}

/* Mobile */
@media (max-width: 576px) {
  .decision-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}
</style>
