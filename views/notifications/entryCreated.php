<?php
/**
 * Sociolog – Glocken-Benachrichtigung mit Profilbild
 *
 * @var \humhub\modules\notification\components\BaseNotification $object
 * @var \humhub\modules\sociolog\models\Entry $source
 */

use yii\helpers\Html;
use yii\helpers\Url;
use humhub\modules\user\widgets\Image;

$entry = $source ?? $object->source ?? null;
$module = Yii::$app->getModule('sociolog');
$moduleName = Html::encode($module->settings->get('moduleTitle', 'Logbuch'));

$title = Html::encode($entry->title ?? Yii::t('SociologModule.base', 'Eintrag im {module}', ['module' => $moduleName]));
$user  = $entry->creator ?? null;
$userName = Html::encode($user?->displayName ?? Yii::t('SociologModule.base', 'Unbekannt'));

$url = $entry
    ? Url::to(['/sociolog/entry/view', 'id' => $entry->id], true)
    : '#';
?>

<div style="display:flex; align-items:flex-start; gap:10px; font-size:0.95rem; line-height:1.45;">
    <!-- 👤 Profilbild -->
    <div style="flex-shrink:0;">
        <?= Image::widget([
            'user' => $user,
            'width' => 36,
            'height' => 36,
            'showTooltip' => false,
            'link' => false,
        ]) ?>
    </div>

    <!-- Textbereich -->
    <div style="flex:1;">
        <div style="font-weight:600; color:#222; margin-bottom:2px;">
            <?= Html::encode($title) ?>
        </div>
        <div style="color:#555; font-size:0.9rem;">
            <?= Html::encode($moduleName) ?> · <?= $userName ?>
        </div>
        <?php if ($url && $url !== '#'): ?>
            <div style="margin-top:4px;">
                <a href="<?= Html::encode($url) ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="<?= Html::encode(Yii::t('SociologModule.base', 'Eintrag ansehen') . ' – ' . Yii::t('SociologModule.base', 'öffnet in neuem Fenster')) ?>"
                   style="color:#0d6efd; text-decoration:none; font-weight:500;">
                    <?= Yii::t('SociologModule.base', 'Eintrag ansehen') ?> →
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
