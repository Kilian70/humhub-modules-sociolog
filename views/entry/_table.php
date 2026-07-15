<?php

use yii\helpers\Html;
use yii\helpers\Url;
use humhub\modules\sociolog\models\Entry;

/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var humhub\modules\sociolog\models\EntrySearch $searchModel */

// ============================================================
// ✅ View-Modus: Request → Session → Default
// ============================================================
$requestedView = Yii::$app->request->get('view');

if (in_array($requestedView, ['cards', 'table'], true)) {
    $viewMode = $requestedView;
    Yii::$app->session->set('sociologViewMode', $viewMode);
} else {
    $viewMode = Yii::$app->session->get('sociologViewMode', 'cards');
}

$user = Yii::$app->user->identity ?? null;
?>

<div class="card shadow-sm">
  <div class="card-body">

    <!-- ========================================================
         🔹 Toolbar
    ========================================================= -->
    <div class="d-flex justify-content-end mb-2">
      <button id="printTable" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-print me-1"></i>
        <?= Yii::t('SociologModule.base', 'Tabelle drucken') ?>
      </button>
    </div>

    <!-- ========================================================
         🔹 Tabelle
    ========================================================= -->
    <table id="sociologTable"
           class="table table-striped table-sm align-middle w-100">

      <thead class="table-light">
        <tr>
          <th><?= Yii::t('SociologModule.base', 'Titel') ?></th>
          <th><?= Yii::t('SociologModule.base', 'Status') ?></th>
          <th><?= Yii::t('SociologModule.base', 'Organ') ?></th>
          <th><?= Yii::t('SociologModule.base', 'Beschluss') ?></th>
          <th><?= Yii::t('SociologModule.base', 'Inkrafttreten') ?></th>
          <th class="text-center"
              title="<?= Yii::t('SociologModule.base', 'Themenhüter:in') ?>">
            <i class="fa-solid fa-user-shield"></i>
          </th>
          <th class="text-center"
              title="<?= Yii::t('SociologModule.base', 'Bearbeiten') ?>">
            <i class="fa-solid fa-pen"></i>
          </th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($dataProvider->models as $model): ?>
          <?php if (!$model instanceof Entry) continue; ?>

          <?php
            // ----------------------------------------------------
            // 🔹 Defensive Feldaufbereitung
            // ----------------------------------------------------
            $title        = (string) ($model->title ?? '');
            $organ        = (string) ($model->organ ?? '');
            $decision     = (string) ($model->decision ?? '');
            $effective    = $model->effective_date ?: null;
            $reviewDate   = $model->review_date ?: null;

            $canWrite = ($user && method_exists($model, 'canWrite'))
                ? $model->canWrite($user)
                : false;
          ?>

          <tr data-entry-id="<?= (int)$model->id ?>">

            <!-- Titel -->
            <td>
              <?= Html::a(
                  Html::encode($title ?: Yii::t('SociologModule.base', '(ohne Titel)')),
                  Url::to([
                      '/sociolog/entry/view',
                      'id'   => $model->id,
                      'view' => $viewMode,
                  ])
              ) ?>
            </td>

            <!-- Status -->
            <td>
              <?= $model->getStatusBadge() ?>

              <?php if ($reviewDate && strtotime($reviewDate) < time()): ?>
                <div class="text-warning small mt-1">
                  ⚠️ <?= Yii::t('SociologModule.base', 'Überprüfung fällig seit') ?>
                  <?= Yii::$app->formatter->asDate($reviewDate) ?>
                </div>
              <?php endif; ?>
            </td>

            <!-- Organ -->
            <td>
			  <?= Html::encode($model->organName ?: '–') ?>
			</td>

            <!-- Beschluss -->
            <td>
              <?= Html::encode(
                  mb_strimwidth(strip_tags($decision), 0, 120, ' …')
              ) ?>
            </td>

            <!-- Inkrafttreten -->
            <td>
              <?= $effective
                  ? Yii::$app->formatter->asDate($effective, 'php:d.m.Y')
                  : '–' ?>
            </td>

            <!-- Themenhüter:in -->
            <td class="text-center">
              <?= Html::encode($model->topic_owner ?: '–') ?>
            </td>

            <!-- Bearbeiten -->
            <td class="text-center">
              <?php if ($canWrite): ?>
                <?= Html::a(
                    '<i class="fa-solid fa-pen"></i>',
                    Url::to([
                        '/sociolog/entry/update',
                        'id'   => $model->id,
                        'view' => $viewMode,
                    ]),
                    [
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'title' => Yii::t('SociologModule.base', 'Bearbeiten'),
                        'data-pjax' => 0,
                    ]
                ) ?>
              <?php endif; ?>
            </td>

          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  </div>
</div>
