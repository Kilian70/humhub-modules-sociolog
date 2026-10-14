<?php
/** @var \humhub\modules\sociolog\notifications\EntryCreated|\humhub\modules\sociolog\notifications\EntryUpdated $notification */
/** @var \humhub\modules\sociolog\models\Entry|null $entry */

use yii\helpers\Html;

$title = Html::encode($entry->title ?? Yii::t('SociologModule.base', 'Eintrag'));
$url = $entry ? $notification->getUrl() : null;
?>

<div style="font-family:Arial, sans-serif; background:#f8f9fa; padding:24px; border-radius:8px;">
    <h2 style="color:#2C3E50; margin-top:0;">
        📜 <?= Html::encode($notification->getTitle()) ?>
    </h2>

    <p style="font-size:16px; color:#333; margin-bottom:8px;">
        <strong><?= $title ?></strong>
    </p>

    <p style="font-size:14px; color:#555; margin-bottom:16px;">
        <?= Html::encode($notification->getDescription()) ?>
    </p>

    <?php if ($url): ?>
    <p>
        <a href="<?= Html::encode($url) ?>"
           style="background:#2C3E50; color:#fff; text-decoration:none; padding:10px 18px; border-radius:6px;">
            <?= Yii::t('SociologModule.base', 'Eintrag online ansehen') ?>
        </a>
    </p>
    <?php endif; ?>
</div>
