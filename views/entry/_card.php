<?php

use yii\helpers\Html;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\space\models\Space;

/** @var Entry|null $model */
/** @var array $spaceConfigs */

if (!$model instanceof Entry) {
    return;
}

// ============================================================
// 🔹 Grunddaten (NULL-safe)
// ============================================================
$title        = (string) ($model->title ?? '');
$organ        = (string) ($model->organName ?? '');
$decisionDate = $model->decision_date ?: null;
$decisionText = (string) ($model->decision ?? '');

$color = Entry::getOrganColor($organ);

// ============================================================
// 🔐 Rechte (defensiv)
// ============================================================
$user = Yii::$app->user->identity ?? null;

$canWrite  = ($user && method_exists($model, 'canWrite'))
    ? $model->canWrite($user)
    : false;

$canDelete = ($user && method_exists($model, 'canDelete'))
    ? $model->canDelete($user)
    : false;
    

// ============================================================
// 🔗 Ziel-URL (SpaceConfig berücksichtigen)
// ============================================================
// 🔗 Ziel-URL
// ============================================================
$url = $model->id
    ? Yii::$app->urlManager->createUrl(['/sociolog/entry/view', 'id' => $model->id])
    : '#';

$container = $model->content->container ?? null;

if ($container instanceof Space) {

    $config = $spaceConfigs[$container->id] ?? null;

    if ($config && $config->link_mode === 'custom' && !empty($config->link)) {
        $url = $config->link;
    }

}
?>

<div class="sociolog-card sociolog-clickable fade-in shadow-sm"
     role="link"
     tabindex="0"
     data-url="<?= Html::encode($url) ?>"
     style="--oval-color:<?= Html::encode($color) ?>">
     
     <div class="sociolog-bar"
     style="background-color:<?= Html::encode($color) ?>"></div>

<div class="sociolog-oval-inside"></div>

    <!-- ========================================================
         🔹 INHALT
    ========================================================= -->
    <div class="sociolog-content">

        <!-- Titel -->
        <h5 class="fw-semibold mb-1">
            <?= Html::encode($title ?: Yii::t('SociologModule.base', '(ohne Titel)')) ?>
        </h5>

        <!-- Entscheid-Typ (dezente Darstellung) -->
        <?php if ($model->decisionType): ?>
    <?= $model->decisionType->badge ?>
<?php endif; ?>

        <!-- Organ & Beschlussdatum -->
        <div class="text-muted small mt-2">
            <?= $organ !== '' ? Html::encode($organ) : '–' ?>
            <?php if ($decisionDate): ?>
                • <?= Yii::$app->formatter->asDate($decisionDate, 'php:d.m.Y') ?>
            <?php endif; ?>
        </div>

        <!-- Text-Snippet -->
        <?php if (trim($decisionText) !== ''): ?>
            <div class="sociolog-snippet mt-2">
                <?= Html::encode(
                    mb_strimwidth(
                        strip_tags($decisionText),
                        0,
                        140,
                        ' …'
                    )
                ) ?>
            </div>
        <?php endif; ?>

        <!-- Status -->
        <div class="mt-2">
            <?= $model->getStatusBadge() ?>
        </div>

    </div>

<!-- ========================================================
     🔹 AKTIONEN
========================================================= -->
<?php if ($canWrite || $canDelete): ?>
    <div class="sociolog-buttons">

        <?php if ($canWrite): ?>
            <?= Html::a(
                '<i class="fa-solid fa-pen"></i>',
                ['update', 'id' => $model->id],
                [
                    'class' => 'btn btn-sm btn-outline-secondary',
                    'data-pjax' => 0,
                    'tabindex' => -1,
                    'onclick' => 'event.stopPropagation();',
                    'aria-label' => Yii::t('SociologModule.base', 'Eintrag bearbeiten'),
                ]
            ) ?>
        <?php endif; ?>

        <?php if ($canDelete): ?>
            <?= Html::a(
                '<i class="fa-solid fa-trash"></i>',
                ['delete', 'id' => $model->id],
                [
                    'class' => 'btn btn-sm btn-outline-danger',
                    'data-method' => 'post',
                    'data-confirm' => Yii::t(
                        'SociologModule.base',
                        'Diesen Eintrag wirklich löschen?'
                    ),
                    'onclick' => 'event.stopPropagation();',
                    'data-pjax' => 0,
                    'tabindex' => -1,
                    'aria-label' => Yii::t('SociologModule.base', 'Eintrag löschen'),
                ]
            ) ?>
        <?php endif; ?>

    </div>
<?php endif; ?>

</div>
