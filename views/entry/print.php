<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model humhub\modules\sociolog\models\Entry */

if (!$model) {
    return;
}
?>

<div class="print-container">

    <!-- ============================================================
         TITEL
    ============================================================ -->
    <h2><?= Html::encode(trim($model->title) ?: Yii::t('SociologModule.base', '(ohne Titel)')) ?></h2>


    <!-- ============================================================
         ENTSCHEID-TYP
    ============================================================ -->
    <?php if ($model->decisionType): ?>
        <?php
            $typeName  = $model->decisionType->name ?: Yii::t('SociologModule.base', 'Unbekannt');
            $typeColor = $model->decisionType->color ?: '#777777';
        ?>
        <p>
            <strong><?= Yii::t('SociologModule.base', 'Entscheid-Typ') ?>:</strong>
            <span class="print-type-badge"
                  style="background-color: <?= Html::encode($typeColor) ?>;">
                <?= Html::encode($typeName) ?>
            </span>
        </p>

        <?php if (!empty($model->decisionType->description)): ?>
            <p class="print-muted">
                <em><?= Html::encode($model->decisionType->description) ?></em>
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ============================================================
         ORGAN & THEMENHÜTER:IN
    ============================================================ -->
    <p>
        <strong><?= Yii::t('SociologModule.base', 'Organ') ?>:</strong>
        <?= Html::encode($model->organ ?: '–') ?>
    </p>

    <?php if (!empty($model->topic_owner)): ?>
        <p>
            <strong><?= Yii::t('SociologModule.base', 'Themenhüter:in') ?>:</strong>
            <?= Html::encode($model->topic_owner) ?>
        </p>
    <?php endif; ?>

    <!-- ============================================================
         DATEN
    ============================================================ -->
    <?php if ($model->decision_date): ?>
        <p>
            <strong><?= Yii::t('SociologModule.base', 'Beschlussdatum') ?>:</strong>
            <?= Yii::$app->formatter->asDate($model->decision_date, 'php:d.m.Y') ?>
        </p>
    <?php endif; ?>

    <?php if ($model->effective_date): ?>
        <p>
            <strong><?= Yii::t('SociologModule.base', 'Inkrafttreten') ?>:</strong>
            <?= Yii::$app->formatter->asDate($model->effective_date, 'php:d.m.Y') ?>
        </p>
    <?php endif; ?>

    <?php if ($model->review_date): ?>
        <p>
            <strong><?= Yii::t('SociologModule.base', 'Überprüfung ab') ?>:</strong>
            <?= Yii::$app->formatter->asDate($model->review_date, 'php:d.m.Y') ?>
        </p>
    <?php endif; ?>

    <hr>

    <!-- ============================================================
         BESCHLUSS
    ============================================================ -->
    <h4><?= Yii::t('SociologModule.base', 'Beschluss') ?></h4>
    <div class="print-text">
        <?= nl2br(Html::encode($model->decision ?: '–')) ?>
    </div>

    <!-- ============================================================
         BEGRÜNDUNG
    ============================================================ -->
    <?php if (!empty($model->description)): ?>
        <h4><?= Yii::t('SociologModule.base', 'Begründung') ?></h4>
        <div class="print-text">
            <?= nl2br(Html::encode($model->description)) ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         PROTOKOLL
    ============================================================ -->
    <?php if (!empty($model->protocols)): ?>
        <div class="print-link">
            <strong><?= Yii::t('SociologModule.base', 'Protokolle') ?>:</strong>
            <ul>
                <?php foreach ($model->protocols as $protocol): ?>
                    <li>
                        <?php if ($protocol->safeUrl !== null): ?>
                            <?= Html::a(
                                Html::encode($protocol->title),
                                $protocol->safeUrl,
                                ['target' => '_blank', 'rel' => 'noopener noreferrer']
                            ) ?>
                        <?php else: ?>
                            <?= Html::encode($protocol->title) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <hr>

    <!-- ============================================================
         META (ERSTELLT / BEARBEITET)
    ============================================================ -->
    <p class="print-muted">
        <strong><?= Yii::t('SociologModule.base', 'Erstellt') ?>:</strong>
        <?= $model->created_at
            ? Yii::$app->formatter->asDatetime($model->created_at)
            : '–' ?>
        <?php if ($model->creator): ?>
            – <?= Html::encode($model->creator->displayName) ?>
        <?php endif; ?>
    </p>

    <p class="print-muted">
        <strong><?= Yii::t('SociologModule.base', 'Zuletzt bearbeitet') ?>:</strong>
        <?= $model->updated_at
            ? Yii::$app->formatter->asDatetime($model->updated_at)
            : '–' ?>
        <?php if ($model->editor): ?>
            – <?= Html::encode($model->editor->displayName) ?>
        <?php endif; ?>
    </p>

</div>

<?php
/* ============================================================
   DRUCK-STYLES (lokal)
============================================================ */
$this->registerCss(<<<CSS
.print-container {
    font-family: system-ui, -apple-system, "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #000;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.print-type-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    color: #fff;
    vertical-align: middle;
}

.print-muted {
    color: #555;
    margin-top: 4px;
}

.print-text {
    margin-bottom: 20px;
    white-space: normal;
}

.print-container h2 {
    margin-bottom: 15px;
    page-break-inside: avoid;
}

.print-container h4 {
    margin-top: 25px;
    margin-bottom: 10px;
    font-size: 16px;
    font-weight: 600;
    page-break-inside: avoid;
}

.print-link {
    margin-top: 20px;
}

hr {
    border: 0;
    border-top: 1px solid #ccc;
    margin: 25px 0;
}

@media print {
    body {
        background: #fff !important;
        margin: 0;
        padding: 10mm;
    }

    a {
        color: #000 !important;
        text-decoration: underline !important;
        word-break: break-all;
    }

    .no-print {
        display: none !important;
    }

    h2, h4 {
        color: #000 !important;
    }
}
CSS);
