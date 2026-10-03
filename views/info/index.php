<?php

use yii\helpers\Html;
use yii\helpers\Url;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\sociolog\helpers\RichTextHelper;

/** @var yii\web\View $this */
/** @var string $title */
/** @var string $introText */
/** @var string $documentUrl */
/** @var array $sections */

$hasDocument = $documentUrl !== '';
$isExternalDocument = $hasDocument && preg_match('#^https?://#i', $documentUrl) === 1;

$renderInfoText = static function (string $text): string {
    return RichText::widget([
        'text' => $text,
        'exclude' => RichTextHelper::EXCLUDED_FEATURES,
    ]);
};
?>

<div class="sociolog-info-page">
    <header class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fa fa-info-circle me-2 text-primary" aria-hidden="true"></i>
                <?= Html::encode($title) ?>
            </h1>

            <?php if (trim($introText) !== ''): ?>
                <div class="lead text-muted mb-0 sociolog-info-intro">
                    <?= $renderInfoText($introText) ?>
                </div>
            <?php endif; ?>
        </div>

        <?= Html::a(
            '<i class="fa fa-arrow-left me-1" aria-hidden="true"></i>'
                . Yii::t('SociologModule.base', 'Zurück zu den Einträgen'),
            ['/sociolog/entry/index'],
            ['class' => 'btn btn-sm btn-outline-secondary']
        ) ?>
    </header>

    <?php if ($hasDocument): ?>
        <aside class="alert alert-info d-flex align-items-start gap-3 mb-4" aria-labelledby="sociolog-document-title">
            <i class="fa fa-book fa-lg mt-1" aria-hidden="true"></i>
            <div>
                <h2 id="sociolog-document-title" class="h5 mb-1">
                    <?= Yii::t('SociologModule.base', 'Einleitungsdokument') ?>
                </h2>
                <p class="mb-2">
                    <?= Yii::t('SociologModule.base', 'Hier findest du die ausführliche Einleitung und die verbindlichen Grundlagen des Logbuchs.') ?>
                </p>
                <?= Html::a(
                    Yii::t('SociologModule.base', 'Dokument öffnen')
                        . ($isExternalDocument
                            ? ' <span class="visually-hidden">('
                                . Yii::t('SociologModule.base', 'öffnet in neuem Fenster')
                                . ')</span>'
                            : ''),
                    $isExternalDocument ? $documentUrl : Url::to($documentUrl),
                    array_filter([
                        'class' => 'btn btn-sm btn-primary',
                        'target' => $isExternalDocument ? '_blank' : null,
                        'rel' => $isExternalDocument ? 'noopener noreferrer' : null,
                    ])
                ) ?>
            </div>
        </aside>
    <?php endif; ?>

    <div class="sociolog-info-grid">
        <?php foreach ($sections as $section): ?>
            <?php if (trim((string)$section['text']) === ''): ?>
                <?php continue; ?>
            <?php endif; ?>

            <article class="card shadow-sm sociolog-info-card <?= Html::encode($section['class']) ?>">
                <div class="card-body">
                    <h2 class="h5">
                        <i class="fa <?= Html::encode($section['icon']) ?> me-2" aria-hidden="true"></i>
                        <?= Html::encode($section['title']) ?>
                    </h2>
                    <div class="sociolog-info-text">
                        <?= $renderInfoText((string)$section['text']) ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
