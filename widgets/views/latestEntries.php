<?php
use yii\helpers\Html;
use yii\helpers\Url;
use humhub\modules\sociolog\assets\SociologAsset;

/** @var humhub\modules\sociolog\models\Entry[] $entries */

$module = Yii::$app->getModule('sociolog');
SociologAsset::register($this);
$showDecisionTypeHeader = $module
  ? (bool)$module->settings->get('showDecisionTypeHeader', true)
  : true;
?>

<style>
/* ======================================================
   Sociolog-Widget – modernisiert für Bootstrap 5 (2025)
====================================================== */
.sociolog-widget-card {
  position: relative;
  border-radius: 18px;
  background: #fff;
  overflow: hidden;
  margin-bottom: 18px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  opacity: 0;
  transform: translateY(10px);
  animation: fadeInUp .5s ease forwards;
  transition: transform .25s ease, box-shadow .25s ease;
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
.sociolog-widget-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 7px 18px rgba(0,0,0,0.15);
}
.sociolog-widget-bar {
  height: 5px;
  width: 100%;
  border-radius: 5px 5px 0 0;
}
.sociolog-widget-inner {
  position: relative;
  z-index: 2;
  padding: 15px 18px 20px 18px;
}
.sociolog-widget-oval {
  position: absolute;
  top: 38px;
  right: -42px;
  width: 70%;
  height: 70%;
  border-radius: 50%;
  border: 3px solid var(--oval-color);
  opacity: .18;
  z-index: 1;
}
.sociolog-widget-title {
  font-size: 15px;
  font-weight: 600;
  margin-bottom: 6px;
  line-height: 1.3;
}
.sociolog-widget-short {
  font-size: 13px;
  color: #444;
  margin-bottom: 6px;
}
.sociolog-widget-type {
  display: inline-block;
  padding: 4px 9px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  color: #fff;
}

/* Mobile Optimierung */
@media (max-width: 576px) {
  .sociolog-widget-inner { padding: 13px 14px 18px 14px; }
  .sociolog-widget-title { font-size: 14px; }
  .sociolog-widget-short { font-size: 12.5px; }
}

@media (prefers-reduced-motion: reduce) {
  .sociolog-widget-card {
    opacity: 1;
    transform: none;
    animation: none;
    transition: none;
  }

  .sociolog-widget-card:hover {
    transform: none;
  }
}
</style>

<div class="card shadow-sm mb-3">
  <div class="card-header d-flex align-items-center bg-body-tertiary fw-semibold">
    <i class="fa fa-book me-2 text-primary"></i>
    <?= Yii::t('SociologModule.base', 'Neueste Beschlüsse') ?>
  </div>

  <div class="card-body p-3">
    <?php if (empty($entries)): ?>
      <p class="text-muted mb-0">
        <?= Yii::t('SociologModule.base', 'Keine Einträge vorhanden.') ?>
      </p>
    <?php endif; ?>

    <?php foreach ($entries as $entry): ?>
      <?php
        $colorOrgan = Html::encode($entry->getOrganColor($entry->organName) ?: '#0d6efd');
        $typeColor  = $entry->decisionType->color ?? '#555';
        $typeTextColor = \humhub\modules\sociolog\models\DecisionType::getAccessibleTextColor($typeColor);
        $url   = Url::to(['/sociolog/entry/view', 'id' => $entry->id]);
        $date  = Yii::$app->formatter->asDate($entry->decision_date, 'php:d.m.Y');
        $short = Html::encode(mb_strimwidth(strip_tags((string)$entry->decision), 0, 130, ' …'));
      ?>
      <div class="sociolog-widget-card" style="--oval-color: <?= $colorOrgan ?>;">
        <div class="sociolog-widget-bar" style="background: <?= $colorOrgan ?>;"></div>
        <div class="sociolog-widget-oval"></div>

        <div class="sociolog-widget-inner">
          <div class="sociolog-widget-title">
            <?= Html::a(Html::encode($entry->title), $url, [
              'class' => 'text-decoration-none text-dark'
            ]) ?>
          </div>

          <?php if ($showDecisionTypeHeader && $entry->decisionType): ?>
            <div class="mb-1">
              <span class="sociolog-widget-type" style="background: <?= Html::encode($typeColor) ?>; color: <?= Html::encode($typeTextColor) ?>;">
                <?= Html::encode($entry->decisionType->name) ?>
              </span>
            </div>
          <?php endif; ?>

          <div class="text-muted small mb-1">
            <?= Html::encode($entry->organName) ?> • <?= Html::encode($date) ?>
          </div>

          <div class="sociolog-widget-short"><?= $short ?></div>
          <div><?= $entry->statusBadge ?></div>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="mt-3 text-center">
      <?= Html::a(
        '<i class="fa fa-arrow-right me-1"></i> ' . Yii::t('SociologModule.base', 'Alle Beschlüsse anzeigen'),
        ['/sociolog/entry/index'],
        ['class' => 'btn btn-outline-primary btn-sm']
      ) ?>
    </div>
  </div>
</div>
