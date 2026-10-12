<?php

use humhub\helpers\Html;
use humhub\modules\sociolog\models\Entry;

/** @var Entry $model */
if (!(bool)Yii::$app->getModule('sociolog')->settings->get('showObjectionDeadline', false)
    || !$model->shouldDisplayObjectionDeadline()) {
    return;
}

$deadline = $model->getObjectionDeadlineTimestamp();
$date = $model->getObjectionDeadlineDate();
if ($deadline === null && $date === null) {
    return;
}
?>
<div class="sociolog-objection-deadline mt-2">
    <strong><?= Html::encode(Yii::t('SociologModule.base', 'Schwerwiegender Einwand möglich bis')) ?>:</strong>
    <?= Html::encode($deadline !== null
        ? Yii::$app->formatter->asDatetime($deadline, 'php:d.m.Y, H:i:s T')
        : Yii::$app->formatter->asDate($date, 'php:d.m.Y')) ?>
    <?php if ($deadline === null): ?>
        <div class="small text-muted">
            <?= Html::encode(Yii::t('SociologModule.base', 'Die Uhrzeit der Veröffentlichung wurde bei diesem älteren Eintrag nicht erfasst.')) ?>
        </div>
    <?php endif; ?>
</div>
