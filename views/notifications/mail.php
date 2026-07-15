<?php
/**
 * @var \humhub\modules\sociolog\notifications\EntryCreated|\humhub\modules\sociolog\notifications\EntryUpdated $notification
 * @var \humhub\modules\sociolog\models\Entry $entry
 */

use yii\helpers\Html;
use yii\helpers\Url;

$module = Yii::$app->getModule('sociolog');
$moduleName = $module->settings->get('moduleTitle', 'Logbuch');
$title = Html::encode($entry->title ?? Yii::t('SociologModule.base', 'Eintrag'));
$userName = Html::encode($entry->creator->displayName ?? Yii::t('SociologModule.base', 'Unbekannt'));
$url = Url::to(['/sociolog/entry/view', 'id' => $entry->id], true);
?>

<div style="font-family:Arial, sans-serif; background:#f8f9fa; padding:24px; border-radius:8px;">
    <h2 style="color:#2C3E50; margin-top:0;">
        📜 <?= Yii::t('SociologModule.base', 'Neuer Eintrag im {module}', ['module' => $moduleName]) ?>
    </h2>

    <p style="font-size:16px; color:#333; margin-bottom:8px;">
        <strong><?= $title ?></strong>
    </p>

    <p style="font-size:14px; color:#555; margin-bottom:16px;">
        <?= Yii::t('SociologModule.base', 'Erstellt von {user}', ['user' => $userName]) ?>
    </p>

    <p>
        <a href="<?= Html::encode($url) ?>"
           style="background:#2C3E50; color:#fff; text-decoration:none; padding:10px 18px; border-radius:6px;">
            <?= Yii::t('SociologModule.base', 'Eintrag online ansehen') ?>
        </a>
    </p>
</div>
