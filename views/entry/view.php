<?php

use yii\helpers\Html;
use humhub\modules\user\widgets\Image as UserImage;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\EntryFlow;
use humhub\modules\space\models\Space;

/** @var yii\web\View $this */
/** @var humhub\modules\sociolog\models\Entry $model */

$module = Yii::$app->getModule('sociolog');
$decisionDateLabel = $module->getCustomLabel(
    'decisionDateLabel',
    Yii::t('SociologModule.base', 'Beschlussdatum')
);
$topicOwnerLabel = $module->getCustomLabel(
    'topicOwnerLabel',
    Yii::t('SociologModule.base', 'Themenhüter:in')
);
$protocolsLabel = $module->getCustomLabel(
    'protocolsLabel',
    Yii::t('SociologModule.base', 'Protokolle')
);
$showDecisionTypeHeader = (bool)$module->settings->get('showDecisionTypeHeader', true);

// ============================================================
// 🔹 Seitentitel (Single Source of Truth – NULL & undefined safe)
// ============================================================
$rawModuleTitle = $module?->settings->get('moduleTitle');

$moduleTitle = (is_string($rawModuleTitle) && trim($rawModuleTitle) !== '')
    ? trim($rawModuleTitle)
    : Yii::t('SociologModule.base', 'Logbuch');

$entryTitle = trim((string)($model->title ?? ''));

$this->title = $moduleTitle . ' – ' . (
    $entryTitle !== ''
        ? $entryTitle
        : Yii::t('SociologModule.base', 'Eintrag')
);

// ============================================================
// 🔐 Rechte & Hilfsdaten
// ============================================================
$currentView = Yii::$app->request->get('view', 'cards');

$canWrite  = $model->canWrite(Yii::$app->user->identity);
$canDelete = $model->canDelete(Yii::$app->user->identity);
$canMaintainReview = $model->canMaintainReview(Yii::$app->user->identity);

$workflowEnabled = Yii::$app->getModule('sociolog')
    ->settings
    ->get('decisionWorkflowEnabled', true);
$limitedReviewMaintenanceEnabled = (bool)Yii::$app->getModule('sociolog')
    ->settings
    ->get('limitedReviewMaintenanceEnabled', false);

$organColor = Entry::getOrganColor($model->organName);
$organLink  = Entry::getOrganLink($model->getDecisionOrgan());

// ============================================================
// 🔹 Entscheidungsverlauf laden
// ============================================================
$flows = EntryFlow::find()
    ->where(['entry_id' => $model->id])
    ->orderBy(['created_at' => SORT_ASC])
    ->all();
?>


<!-- ============================================================
     🔹 TITEL & AKTIONEN
============================================================ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

  <h1 class="h4 mb-0 d-flex align-items-center">
    <i class="fa-solid fa-book me-2 text-primary" aria-hidden="true"></i>
    <?= Html::encode($entryTitle !== '' ? $entryTitle : Yii::t('SociologModule.base', '(ohne Titel)')) ?>
  </h1>

  <div class="btn-group">

    <?= Html::a(
        '⬅️ ' . Yii::t('SociologModule.base', 'Zurück'),
        ['index', 'view' => $currentView],
        ['class' => 'btn btn-sm btn-outline-secondary']
    ) ?>

    <button type="button"
            class="btn btn-sm btn-outline-secondary"
            data-action="print-entry">
      <i class="fa-solid fa-print me-1"></i>
      <?= Yii::t('SociologModule.base', 'Eintrag drucken') ?>
    </button>

    <?php if ($canWrite): ?>
      <?= Html::a(
          '<i class="fa-solid fa-pen" aria-hidden="true"></i>',
          ['update', 'id' => $model->id, 'view' => $currentView],
          [
              'class' => 'btn btn-sm btn-outline-secondary',
              'title' => Yii::t('SociologModule.base', 'Bearbeiten'),
              'aria-label' => Yii::t('SociologModule.base', 'Eintrag bearbeiten'),
          ]
      ) ?>
    <?php endif; ?>

    <?php if ($canDelete): ?>
      <?= Html::a(
          '<i class="fa-solid fa-trash" aria-hidden="true"></i>',
          ['delete', 'id' => $model->id],
          [
              'class' => 'btn btn-sm btn-outline-danger',
              'title' => Yii::t('SociologModule.base', 'Löschen'),
              'aria-label' => Yii::t('SociologModule.base', 'Eintrag löschen'),
              'data-confirm' => Yii::t('SociologModule.base', 'Diesen Eintrag wirklich löschen?'),
              'data-method' => 'post',
          ]
      ) ?>
    <?php endif; ?>

  </div>
</div>

<!-- ============================================================
     🔹 INHALT
============================================================ -->
<div class="card shadow-sm sociolog-view">
  <div class="card-body">

    <!-- Entscheid-Typ -->
    <?php if ($showDecisionTypeHeader && $model->decisionType): ?>
      <?php
        $typeColor = $model->decisionType->color ?: '#6c757d';
        $typeTextColor = \humhub\modules\sociolog\models\DecisionType::getAccessibleTextColor($typeColor);
      ?>
      <div class="mb-3">
        <span class="badge text-uppercase"
              style="background-color: <?= Html::encode($typeColor) ?>; color: <?= Html::encode($typeTextColor) ?>; border-radius: 6px;">
          <?= Html::encode($model->decisionType->name) ?>
        </span>

        <?php if ($model->decisionType->description): ?>
          <small class="text-muted ms-2">
            <?= Html::encode($model->decisionType->description) ?>
          </small>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Organ -->
    <p>
      <i class="fa-solid fa-sitemap me-2 text-secondary"></i>
      <strong><?= Yii::t('SociologModule.base', 'Zuständiges Organ') ?>:</strong>
      <?php if ($organLink): ?>
        <?= Html::a(
    Html::encode($model->organName),
    $organLink,
    [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
        'class' => 'fw-semibold organ-link-black',
        'aria-label' => $model->organName . ' – ' . Yii::t('SociologModule.base', 'öffnet in neuem Fenster'),
    ]
) ?>
      <?php else: ?>
        <span class="fw-semibold organ-link-black">
          <?= Html::encode($model->organName) ?>
        </span>
      <?php endif; ?>
    </p>
    
<!-- ============================================================
 🔹 WORKFLOW / WEITERLEITUNG
============================================================ -->
<?php
$currentOrgan = $model->getOrganName();

$nextSpaceId = $model->getNextOrgan();
$nextOrgan = null;

if ($nextSpaceId) {
    $nextSpace = Space::findOne($nextSpaceId);
    $nextOrgan = $nextSpace?->name;
}

$previousOrgan = $model->getPreviousOrgan();

$workflowEnabled = Yii::$app->getModule('sociolog')
    ->settings
    ->get('decisionWorkflowEnabled', true);
?>

<?php if ($workflowEnabled && $currentOrgan): ?>

<div class="alert alert-light border mt-3">

  <strong>
    <?= Yii::t('SociologModule.base', 'Aktueller Entscheidungsstand') ?>
  </strong>

  <div class="mt-2">

    <div>
      <strong><?= Yii::t('SociologModule.base', 'Aktuell beim') ?>:</strong>
      <?= Html::encode($currentOrgan) ?>
    </div>

    <?php if ($workflowEnabled && $model->forwarded_to): ?>
      <div>
        <strong><?= Yii::t('SociologModule.base', 'Weitergeleitet an') ?>:</strong>
        <?= Html::encode($model->forwarded_to) ?>

        <?php if ($model->forwarded_at): ?>
          <small class="text-muted">
            (<?= Yii::$app->formatter->asDate($model->forwarded_at) ?>)
          </small>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($model->published_at): ?>
      <div>
        <strong><?= Yii::t('SociologModule.base', 'Veröffentlicht am') ?>:</strong>
        <?= Yii::$app->formatter->asDate($model->published_at) ?>
      </div>
    <?php endif; ?>

  </div>

</div>

<?php endif; ?>


<!-- ============================================================
 🔹 BUTTONS
============================================================ -->

<?php if ($workflowEnabled && $canWrite && $nextOrgan): ?>

<div class="mt-3">

<?= Html::a(
    '<i class="fa-solid fa-share me-1"></i> '
    . Yii::t('SociologModule.base', 'Weiterleiten an')
    . ' ' . $nextOrgan,
    ['forward', 'id' => $model->id],
    [
        'class' => 'btn btn-warning btn-sm',
        'data-method' => 'post'
    ]
) ?>

</div>

<?php endif; ?>


<?php if ($workflowEnabled && $canWrite && $previousOrgan): ?>

<div class="mt-2">

<?= Html::a(
    '<i class="fa-solid fa-rotate-left me-1"></i> '
    . Yii::t('SociologModule.base', 'Entscheid zurückgeben'),
    ['return', 'id' => $model->id],
    [
        'class' => 'btn btn-secondary btn-sm',
        'data-method' => 'post'
    ]
) ?>

</div>

<?php endif; ?>


<?php if ($workflowEnabled && $canWrite && $model->forwarded_to): ?>

<div class="mt-2">

<?= Html::a(
    '<i class="fa-solid fa-check me-1"></i> '
    . Yii::t('SociologModule.base', 'Entscheid übernehmen'),
    ['take-over', 'id' => $model->id],
    [
        'class' => 'btn btn-success btn-sm',
        'data-method' => 'post'
    ]
) ?>

</div>

<?php endif; ?>


<?php if ($workflowEnabled && $canWrite): ?>

<div class="mt-2">

<?= Html::a(
    '<i class="fa-solid fa-gavel me-1"></i> '
    . Yii::t('SociologModule.base', 'Beschluss fassen'),
    ['decide', 'id' => $model->id],
    [
        'class' => 'btn btn-success btn-sm',
        'data-method' => 'post'
    ]
) ?>

</div>

<?php endif; ?>


<?php if ($workflowEnabled && $canMaintainReview && $model->decision_date): ?>

<div class="mt-2">

<?= Html::a(
    '<i class="fa-solid fa-search me-1"></i> '
    . Yii::t('SociologModule.base', 'Überprüfung dokumentieren'),
    ['review', 'id' => $model->id],
    [
        'class' => 'btn btn-info btn-sm',
        'data-method' => $limitedReviewMaintenanceEnabled ? null : 'post'
    ]
) ?>

</div>

<?php endif; ?>

<!-- ============================================================
 🔹 ENTSCHEIDUNGSVERLAUF
============================================================ -->

<?php if ($workflowEnabled && !empty($flows)): ?>

<div class="alert alert-light border mt-3">

<strong>
<?= Yii::t('SociologModule.base', 'Entscheidungsverlauf') ?>
</strong>

<ul class="mt-2 mb-0">

<?php foreach ($flows as $flow): ?>

<li>

<?= Yii::$app->formatter->asDatetime($flow->created_at) ?>
→ <?= Html::encode($flow->getLabel()) ?>

</li>

<?php endforeach; ?>

</ul>

</div>

<?php endif; ?>

    <!-- Themenhüter:in -->
    <p>
      <i class="fa-solid fa-user-shield me-2 text-secondary"></i>
      <strong><?= Html::encode($topicOwnerLabel) ?>:</strong>
      <?= $model->topic_owner ? Html::encode($model->topic_owner) : '–' ?>
    </p>

    <!-- Beschluss -->
    <h6 class="mt-4 mb-1">
      <i class="fa-solid fa-scroll me-2 text-primary"></i>
      <strong><?= Yii::t('SociologModule.base', 'Beschluss') ?>:</strong>
    </h6>
    <p class="ps-4"><?= nl2br(Html::encode($model->decision)) ?></p>

    <!-- Begründung -->
    <?php if ($model->description): ?>
      <h6 class="mt-4 mb-1">
        <i class="fa-solid fa-quote-left me-2 text-primary"></i>
        <strong><?= Yii::t('SociologModule.base', 'Begründung') ?>:</strong>
      </h6>
      <p class="ps-4"><?= nl2br(Html::encode($model->description)) ?></p>
    <?php endif; ?>
    
    <!-- ============================================================
 🔹 PROTOKOLLE
============================================================ -->

<?php if (!empty($model->protocols)): ?>

<h6 class="mt-4 mb-1">
  <i class="fa-solid fa-file-lines me-2 text-primary"></i>
  <strong><?= Html::encode($protocolsLabel) ?>:</strong>
</h6>

<ul class="ps-4 mb-3">

<?php foreach ($model->protocols as $protocol): ?>

<li>
<i class="fa-solid fa-file-lines me-1 text-secondary"></i>
<?php if ($protocol->safeUrl !== null): ?>
    <?= Html::a(
        Html::encode($protocol->title ?: Yii::t('SociologModule.base','Protokoll')),
        $protocol->safeUrl,
        [
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
            'aria-label' => ($protocol->title ?: Yii::t('SociologModule.base', 'Protokoll')) . ' – ' . Yii::t('SociologModule.base', 'öffnet in neuem Fenster'),
        ]
    ) ?>
<?php else: ?>
    <?= Html::encode($protocol->title ?: Yii::t('SociologModule.base','Protokoll')) ?>
<?php endif; ?>
</li>

<?php endforeach; ?>

</ul>

<?php endif; ?>

    <!-- Daten -->
    <div class="row mt-4">
      <div class="col-md-4 mb-2">
        <strong><?= Html::encode($decisionDateLabel) ?>:</strong><br>
        <?= $model->decision_date
            ? Yii::$app->formatter->asDate($model->decision_date)
            : '–' ?>
      </div>

      <div class="col-md-4 mb-2">
        <strong><?= Yii::t('SociologModule.base', 'Inkrafttreten') ?>:</strong><br>
        <?= $model->effective_date
            ? Yii::$app->formatter->asDate($model->effective_date)
            : Yii::t('SociologModule.base', '(nicht gesetzt)') ?>
      </div>

      <div class="col-md-4 mb-2">
        <strong><?= Yii::t('SociologModule.base', 'Überprüfung ab') ?>:</strong><br>
        <?= $model->review_date
            ? Yii::$app->formatter->asDate($model->review_date)
            : '–' ?>
      </div>
    </div>

    <!-- Status -->
    <p class="mt-3">
      <strong><?= Yii::t('SociologModule.base', 'Status') ?>:</strong>
      <?= $model->getStatusBadge() ?>
    </p>

    <hr class="my-4">

    <!-- ============================================================
         🔹 META-INFORMATIONEN
    ============================================================ -->
    <div class="d-flex flex-wrap gap-4 small text-muted">

      <div class="d-flex align-items-center gap-2">
        <strong><?= Yii::t('SociologModule.base', 'Erstellt von') ?>:</strong>
        <?= $model->creator
            ? UserImage::widget(['user' => $model->creator, 'width' => 22]) .
              Html::encode($model->creator->displayName)
            : '–' ?>
      </div>

      <div>
        <strong><?= Yii::t('SociologModule.base', 'Erstellt am') ?>:</strong>
        <?= $model->created_at
            ? Yii::$app->formatter->asDatetime($model->created_at)
            : '–' ?>
      </div>

      <div class="d-flex align-items-center gap-2">
        <strong><?= Yii::t('SociologModule.base', 'Bearbeitet von') ?>:</strong>
        <?= $model->editor
            ? UserImage::widget(['user' => $model->editor, 'width' => 22]) .
              Html::encode($model->editor->displayName)
            : '–' ?>
      </div>

      <div>
        <strong><?= Yii::t('SociologModule.base', 'Bearbeitet am') ?>:</strong>
        <?= $model->updated_at
            ? Yii::$app->formatter->asDatetime($model->updated_at)
            : '–' ?>
      </div>

    </div>

  </div>
</div>
