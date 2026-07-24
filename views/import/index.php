<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var humhub\modules\sociolog\models\ImportUploadForm $model */
/** @var array|null $preview */
/** @var string|null $token */

$this->title = Yii::t('SociologModule.base', 'Historische Einträge importieren');
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= Html::encode($this->title) ?></strong>
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            <?= Yii::t(
                'SociologModule.base',
                'Der Import läuft in zwei Schritten. Zuerst wird die CSV-Datei geprüft. Erst danach kann der Import ausdrücklich bestätigt werden.'
            ) ?>
        </div>

        <details class="mb-3">
            <summary>
                <strong><?= Yii::t('SociologModule.base', 'Aufbau der CSV-Importdatei') ?></strong>
            </summary>
            <div class="well mt-2 mb-0">
                <p>
                    <?= Yii::t(
                        'SociologModule.base',
                        'Verwende die Vorlage und lasse die Spaltennamen unverändert. Jede weitere Zeile entspricht einem historischen Logbuch-Eintrag.'
                    ) ?>
                </p>
                <ul>
                    <li><code>source_sheet</code>: <?= Yii::t('SociologModule.base', 'Name der ursprünglichen Tabelle oder Quelle') ?></li>
                    <li><code>source_row</code>: <?= Yii::t('SociologModule.base', 'Zeilennummer oder eindeutige Kennzeichnung in der Quelle') ?></li>
                    <li><code>target_organ</code>: <?= Yii::t('SociologModule.base', 'Exakter Name eines im Logbuch aktivierten Ziel-Spaces') ?></li>
                    <li><code>decision_type</code>: <?= Yii::t('SociologModule.base', 'Exakter Name einer vorhandenen Entscheidungsart') ?></li>
                    <li><code>title</code>: <?= Yii::t('SociologModule.base', 'Titel des Eintrags') ?></li>
                    <li><code>decision</code>: <?= Yii::t('SociologModule.base', 'Vollständiger Beschlusstext') ?></li>
                    <li><code>decision_date</code>: <?= Yii::t('SociologModule.base', 'Veröffentlichungsdatum im Format JJJJ-MM-TT') ?></li>
                    <li><code>review_date</code>: <?= Yii::t('SociologModule.base', 'Optionales Überprüfungsdatum im Format JJJJ-MM-TT') ?></li>
                </ul>
                <p class="text-muted small">
                    <?= Yii::t(
                        'SociologModule.base',
                        'Die Prüfung zeigt fehlende Ziel-Spaces, unbekannte Entscheidungsarten, ungültige Daten und bereits vorhandene Einträge an. Erst eine fehlerfreie Vorschau kann importiert werden.'
                    ) ?>
                </p>
                <?= Html::a(
                    '<i class="fa-solid fa-download me-1" aria-hidden="true"></i> '
                        . Yii::t('SociologModule.base', 'CSV-Vorlage herunterladen'),
                    ['template'],
                    ['class' => 'btn btn-default btn-sm', 'data-pjax' => 0]
                ) ?>
            </div>
        </details>

        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
        <?= $form->field($model, 'file')->fileInput(['accept' => '.csv,text/csv']) ?>
        <?= Html::submitButton(
            Yii::t('SociologModule.base', 'Datei prüfen'),
            ['class' => 'btn btn-primary']
        ) ?>
        <?= Html::a(
            Yii::t('SociologModule.base', 'Zurück zu den Einstellungen'),
            ['/sociolog/admin/index'],
            ['class' => 'btn btn-default']
        ) ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php if (is_array($preview)): ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong><?= Yii::t('SociologModule.base', 'Serverseitige Importprüfung') ?></strong>
        </div>
        <div class="panel-body">
            <p>
                <span class="label label-success">
                    <?= Yii::t('SociologModule.base', '{count} importbereit', ['count' => $preview['ready']]) ?>
                </span>
                <span class="label label-warning">
                    <?= Yii::t('SociologModule.base', '{count} Duplikate', ['count' => $preview['duplicates']]) ?>
                </span>
                <span class="label label-danger">
                    <?= Yii::t('SociologModule.base', '{count} Fehler', ['count' => $preview['errors']]) ?>
                </span>
            </p>

            <div class="table-responsive">
                <table class="table table-striped table-condensed">
                    <thead>
                    <tr>
                        <th><?= Yii::t('SociologModule.base', 'Status') ?></th>
                        <th><?= Yii::t('SociologModule.base', 'Quelle') ?></th>
                        <th><?= Yii::t('SociologModule.base', 'Zielorgan') ?></th>
                        <th><?= Yii::t('SociologModule.base', 'Titel') ?></th>
                        <th><?= Yii::t('SociologModule.base', 'Veröffentlichungsdatum') ?></th>
                        <th><?= Yii::t('SociologModule.base', 'Hinweis') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($preview['rows'] as $row): ?>
                        <tr>
                            <td>
                                <?php if ($row['ready']): ?>
                                    <span class="label label-success"><?= Yii::t('SociologModule.base', 'Bereit') ?></span>
                                <?php elseif ($row['duplicate']): ?>
                                    <span class="label label-warning"><?= Yii::t('SociologModule.base', 'Duplikat') ?></span>
                                <?php else: ?>
                                    <span class="label label-danger"><?= Yii::t('SociologModule.base', 'Fehler') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= Html::encode($row['sourceSheet'] . ' / ' . $row['sourceRow']) ?></td>
                            <td><?= Html::encode($row['targetOrgan']) ?></td>
                            <td><?= Html::encode($row['title']) ?></td>
                            <td><?= Html::encode($row['decisionDate']) ?></td>
                            <td><?= Html::encode(implode(' ', $row['errors'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($preview['ready'] > 0 && $preview['errors'] === 0): ?>
                <?= Html::beginForm(['run'], 'post') ?>
                <?= Html::hiddenInput('token', $token) ?>
                <div class="alert alert-warning">
                    <?= Yii::t(
                        'SociologModule.base',
                        'Mit der Bestätigung werden ausschließlich die importbereiten Einträge gespeichert. Duplikate werden übersprungen. Alte Einträge lösen keine Benachrichtigungen oder Kalendertermine aus.'
                    ) ?>
                </div>
                <?= Html::submitButton(
                    Yii::t('SociologModule.base', '{count} Einträge jetzt importieren', ['count' => $preview['ready']]),
                    [
                        'class' => 'btn btn-danger',
                        'data-confirm' => Yii::t('SociologModule.base', 'Historischen Import jetzt endgültig ausführen?'),
                    ]
                ) ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
