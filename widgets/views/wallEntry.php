<?php
use yii\helpers\Html;

/** @var humhub\modules\sociolog\models\Entry $entry */
?>

<div class="sociolog-wall-entry">
    <h5 class="mb-1">
        <?= Html::a(
            Html::encode($entry->title),
            $entry->getUrl(),
            [
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
                'class' => 'fw-bold text-decoration-none',
                'aria-label' => $entry->title . ' – ' . Yii::t('SociologModule.base', 'öffnet in neuem Fenster'),
            ]
        ) ?>
    </h5>

<div class="text-muted small mb-2">
    <?= Yii::$app->formatter->asDate($entry->decision_date) ?>

    <?php if ($entry->organName): ?>
        – <?= Html::encode($entry->organName) ?>
    <?php endif; ?>
</div>

    <div class="sociolog-description">
        <?= nl2br(Html::encode($entry->decision)) ?>
    </div>

    <div class="mt-2 small text-muted">
        <?= $entry->getStatusBadge() ?>
    </div>
</div>
