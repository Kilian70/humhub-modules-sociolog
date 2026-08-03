<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model humhub\modules\sociolog\models\Entry */

$module = Yii::$app->getModule('sociolog');
$moduleTitle = $module->getName();
$viewMode = Yii::$app->request->get('view', 'cards');

// Seitentitel (mehrsprachig, HumHub-konform)
$this->title = $moduleTitle . ' – ' . Yii::t('SociologModule.base', 'Neuer Eintrag');
?>

<div class="card shadow-sm">

  <!-- HEADER -->
  <div class="card-header d-flex justify-content-between align-items-center">
    <h1 class="h5 mb-0">
      <?= Html::encode($moduleTitle) ?>
      <span class="text-muted ms-1">/ <?= Yii::t('SociologModule.base', 'Neuer Eintrag') ?></span>
    </h1>

    <?php if (!Yii::$app->request->isAjax): ?>
      <?= Html::a(
        '<i class="fa fa-arrow-left me-1"></i> ' . Yii::t('SociologModule.base', 'Zurück'),
        ['index', 'view' => $viewMode],
        ['class' => 'btn btn-sm btn-outline-secondary']
      ) ?>
    <?php endif; ?>
  </div>

  <!-- BODY -->
  <div class="card-body">
    <?= $this->render('_form', ['model' => $model]) ?>
  </div>

</div>
