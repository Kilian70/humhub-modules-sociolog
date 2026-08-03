<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var humhub\modules\sociolog\models\Entry $model */

$module = Yii::$app->getModule('sociolog');
$moduleTitle = $module ? $module->getName() : 'Logbuch';
$viewMode = Yii::$app->request->get('view', 'cards');

/* ============================================================
   Seitentitel
============================================================ */
$this->title = $moduleTitle . ' – ' . Yii::t('SociologModule.base', 'Eintrag bearbeiten');
?>

<div class="card shadow-sm">

  <!-- ============================================================
       HEADER
  ============================================================ -->
  <div class="card-header d-flex justify-content-between align-items-center">
    <h1 class="h5 mb-0">
      <?= Html::encode($moduleTitle) ?>
      <span class="text-muted ms-1">
        / <?= Yii::t('SociologModule.base', 'Eintrag bearbeiten') ?>
      </span>
    </h1>

    <?= Html::a(
  '<i class="fa fa-arrow-left me-1"></i> ' . Yii::t('SociologModule.base', 'Zurück'),
  ['index', 'view' => $viewMode],
  ['class' => 'btn btn-sm btn-outline-secondary']
) ?>
  </div>

  <!-- ============================================================
       BODY
  ============================================================ -->
  <div class="card-body">
    <?= $this->render('_form', ['model' => $model]) ?>
  </div>

</div>
