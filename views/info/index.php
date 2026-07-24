<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var string $title */
/** @var string $introText */
/** @var string $documentUrl */
/** @var array $sections */

$hasDocument = $documentUrl !== '';
$isExternalDocument = $hasDocument && preg_match('#^https?://#i', $documentUrl) === 1;

/**
 * Gibt Informationstexte sicher aus und macht enthaltene E-Mail-Adressen
 * automatisch anklickbar. Andere HTML-Eingaben bleiben vollständig escaped.
 */
$renderInfoText = static function (string $text): string {
    $parts = preg_split(
        '/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/iu',
        trim($text),
        -1,
        PREG_SPLIT_DELIM_CAPTURE
    );

    if ($parts === false) {
        return nl2br(Html::encode(trim($text)));
    }

    $html = '';

    foreach ($parts as $part) {
        if (filter_var($part, FILTER_VALIDATE_EMAIL) !== false) {
            $html .= Html::mailto(Html::encode($part), $part);
        } else {
            $html .= Html::encode($part);
        }
    }

    return nl2br($html);
};
?>

<div class="sociolog-info-page">
    <header class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fa-solid fa-circle-info me-2 text-primary" aria-hidden="true"></i>
                <?= Html::encode($title) ?>
            </h1>

            <?php if (trim($introText) !== ''): ?>
                <p class="lead text-muted mb-0 sociolog-info-intro">
                    <?= $renderInfoText($introText) ?>
                </p>
            <?php endif; ?>
        </div>

        <?= Html::a(
            '<i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>'
                . Yii::t('SociologModule.base', 'Zurück zu den Einträgen'),
            ['/sociolog/entry/index'],
            ['class' => 'btn btn-sm btn-outline-secondary']
        ) ?>
    </header>

    <?php if ($hasDocument): ?>
        <aside class="alert alert-info d-flex align-items-start gap-3 mb-4" aria-labelledby="sociolog-document-title">
            <i class="fa-solid fa-book-open fa-lg mt-1" aria-hidden="true"></i>
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
                        <i class="fa-solid <?= Html::encode($section['icon']) ?> me-2" aria-hidden="true"></i>
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
