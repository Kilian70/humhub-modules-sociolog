<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

use yii\widgets\ActiveForm;

use humhub\modules\user\models\User;
use humhub\modules\user\models\Group;
use humhub\modules\sociolog\models\DecisionType;

$this->title = Yii::t('SociologModule.base', 'Sociolog – Einstellungen');
$this->params['breadcrumbs'][] = $this->title;

// Benutzeroptionen
$allUsers = User::find()
    ->where(['!=', 'status', User::STATUS_SOFT_DELETED])
    ->orderBy(['username' => SORT_ASC])
    ->all();

$userOptions = [];
foreach ($allUsers as $u) {
    $userOptions[$u->id] = $u->displayName . ' (ID ' . $u->id . ')';
}

// Gruppenoptionen
$allGroups = Group::find()->orderBy(['name' => SORT_ASC])->all();
$groupOptions = [];
foreach ($allGroups as $g) {
    $groupOptions[$g->id] = $g->name . ' (ID ' . $g->id . ')';
}

$decisionTypeOptions = [0 => Yii::t('SociologModule.base', 'Keine feste Entscheidungsart')]
    + ArrayHelper::map(
        DecisionType::find()->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all(),
        'id',
        'name'
    );
$allDecisionTypeOptions = ArrayHelper::map(
    DecisionType::find()->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all(),
    'id',
    'name'
);

// Einstellungen laden (nur Anzeige)
$settings = Yii::$app->getModule('sociolog')->settings;
$pendingStatusLabel = Yii::$app->getModule('sociolog')->getCustomLabel(
    'pendingStatusLabel',
    Yii::t('SociologModule.base', 'Nicht in Kraft')
);

$organs = trim((string)$settings->get('organs'));
$globalOrgans = trim((string)$settings->get('globalOrgans'));

$organeMitSchreibrecht = $organs !== '' ? preg_split('/[\r\n,]+/', $organs) : [];
$globaleOrganeMitSchreibrecht = $globalOrgans !== '' ? preg_split('/[\r\n,]+/', $globalOrgans) : [];

?>

<!-- ============================================================
     Header
============================================================ -->
<div class="sociolog-admin-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 p-3 rounded"
     style="background:linear-gradient(90deg,#6a5af9,#9b8cff);color:#fff;">

  <h1 class="h4 mb-0 fw-semibold">
    <i class="fa-solid fa-gear me-2" aria-hidden="true"></i> <?= Html::encode($this->title) ?>
  </h1>

  <div class="d-flex gap-2">

    <?= Html::a(
        '<i class="fa-solid fa-sitemap me-1"></i> ' .
        Yii::t('SociologModule.base', 'Spaces & Bereiche'),
        ['/sociolog/admin/spaces'],
        ['class'=>'btn btn-light btn-sm text-dark']
    ) ?>

    <?= Html::a(
        '<i class="fa-solid fa-arrow-left me-1"></i> ' .
        Yii::t('SociologModule.base', 'Zurück'),
        ['/admin/module'],
        ['class'=>'btn btn-light btn-sm text-dark']
    ) ?>

  </div>

</div>

<!-- ============================================================
     Formular: Sociolog-Einstellungen
============================================================ -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <?php $form = ActiveForm::begin([
        'id' => 'sociolog-settings-form',
        'options' => ['class' => 'row g-4']
    ]); ?>

    <!-- Inkrafttreten & Integrationen -->
    <div class="col-md-4">
      <?= $form->field($model, 'defaultEffectiveDays')
          ->input('number', ['min' => 0])
          ->label(Yii::t('SociologModule.base', 'Inkrafttreten nach (Tagen)'))
          ->hint(Yii::t('SociologModule.base', 'Automatische Berechnung des Inkrafttretens nach Erstellung.')) ?>

      <?= $form->field($model, 'effectiveDateAddExtraDay')->checkbox([
          'uncheck' => 0,
      ])->hint(Yii::t(
          'SociologModule.base',
          'Aktiviert entspricht dem bisherigen Verhalten (+ Fristtage und anschließend ein weiterer Tag).'
      )) ?>
    </div>

<div class="col-md-8">

    <?= $form->field($model, 'showReviewInCalendar')->checkbox([
        'label' => Yii::t('SociologModule.base', 'Überprüfungsdaten im Kalender anzeigen'),
        'uncheck' => 0,
    ]) ?>

    <small class="text-muted d-block mb-3">
        <?= Yii::t('SociologModule.base', 'Wenn aktiviert, werden alle Überprüfungsdaten automatisch als Termine im Kalender angezeigt.') ?>
    </small>


    <?= $form->field($model, 'decisionWorkflowEnabled')->checkbox([
        'label' => Yii::t(
            'SociologModule.base',
            'Soziokratischen Entscheidungsprozess aktivieren (Weitergabe + Entscheidungs-Buttons + Entscheidungsverlauf)'
        ),
        'uncheck' => 0,
    ]) ?>

    <small class="text-muted d-block mb-3">
        <?= Yii::t(
            'SociologModule.base',
            'Wenn deaktiviert, werden Entscheidungen ohne Weitergabe erstellt – wie in der ursprünglichen Modulversion.'
        ) ?>
    </small>


    <div class="form-group mt-4">
        <label class="form-label fw-semibold">
            <?= Yii::t('SociologModule.base', 'Stream-Integration') ?>
        </label>
        <div class="form-text text-muted">
            <?= Yii::t('SociologModule.base', 'Die Anzeige von Einträgen im Stream ist dauerhaft aktiviert, um Transparenz und Benachrichtigungen sicherzustellen.') ?>
        </div>
    </div>

</div>

    <!-- Modulname / Dashboard -->
    <div class="col-md-8">
      <?= $form->field($model, 'moduleTitle')->textInput(['maxlength' => true])
          ->label(Yii::t('SociologModule.base', 'Modulname')) ?>
    </div>

    <div class="col-md-4">
      <?= $form->field($model, 'latestEntriesLimit')
          ->input('number', ['min' => 1, 'max' => 50])
          ->label(Yii::t('SociologModule.base', 'Einträge im Dashboard')) ?>
    </div>

    <!-- Widget-Position -->
    <div class="col-md-4">
      <?= $form->field($model, 'widgetSortOrder')
          ->input('number', ['min' => 0, 'max' => 300])
          ->label(Yii::t('SociologModule.base', 'Widget-Position'))
          ->hint(Yii::t('SociologModule.base', 'Je kleiner die Zahl, desto weiter oben erscheint das Logbuch-Widget im Dashboard.')) ?>
    </div>

    <!-- Farben -->


    <div class="col-md-4">
      <?= $form->field($model, 'organColors')->textarea([
          'rows' => 4,
          'placeholder' => "Hausverein:#FF8800\nLeitungskreis:#007BFF\nVermietung:#28A745",
      ])
          ->label(Yii::t('SociologModule.base', 'Organfarben'))
          ->hint(Yii::t('SociologModule.base', 'Optional: Eintrag im Format Organname:#Farbe pro Zeile (z. B. Hausverein:#FF8800).')) ?>
    </div>

<!-- ============================================================
     Infofelder – Organe
============================================================ -->

<div class="col-12">

<div class="p-3 rounded" style="background:#eef6ff;border-left:5px solid #007bff;">

<strong>
<i class="fa-solid fa-layer-group text-primary me-1"></i>
<?= Yii::t('SociologModule.base', 'Logbuch-Organe verwalten') ?>
</strong>

<br><br>

<small class="text-muted">
<?= Yii::t(
'SociologModule.base',
'Hier wird die Organisationsstruktur des Logbuchs definiert (Verein → Hausverein → Leitungskreis → BK → BG). Spaces können danach den Organen zugeordnet werden.'
) ?>
</small>

<br><br>

<?= Html::a(
'<i class="fa-solid fa-sitemap me-1"></i> ' .
Yii::t('SociologModule.base', 'Organe verwalten'),
['/sociolog/admin/organs'],
['class'=>'btn btn-sm btn-primary']
) ?>

</div>

</div>

<!-- ============================================================
     Globale Schreibrechte (optional)
============================================================ -->

<?php if (!empty($globaleSpaces)): ?>
<div class="col-12">

<div class="p-3 rounded" style="background:#e9f0ff;border-left:5px solid #6a5af9;">

<strong>
<i class="fa-solid fa-pen-to-square text-primary me-1"></i>
<?= Yii::t('SociologModule.base', 'Globale Schreibrechte') ?>
</strong>

<br>

<?php
$names = array_map(function ($space) {
    return $space->name;
}, $globaleSpaces);

echo Html::encode(implode(', ', $names));
?>

<br><br>

<small class="text-muted">
<?= Yii::t(
'SociologModule.base',
'Diese Spaces dürfen in allen Organen des Logbuchs schreiben. Die Einstellung erfolgt in "Spaces & Organe".'
) ?>
</small>

</div>

</div>
<?php endif; ?>

<!-- ============================================================
     🔐 Benutzer- und Gruppenrechte
============================================================ -->


<!-- Benutzer mit Schreibrecht -->
<div class="col-md-6">

    <?= $form->field($model, 'writerUsers')->widget(
        \humhub\modules\user\widgets\UserPickerField::class,
        [
            'maxSelection' => 0,
        ]
    ) ?>

</div>


<!-- Benutzer mit Löschrecht -->
<div class="col-md-6">

    <?= $form->field($model, 'deleterUsers')->widget(
        \humhub\modules\user\widgets\UserPickerField::class,
        [
            'maxSelection' => 0,
        ]
    ) ?>

</div>

<!-- Logbuch-Verantwortliche -->
<div class="col-md-6">

    <?= $form->field($model, 'managerUsers')->widget(
        \humhub\modules\user\widgets\UserPickerField::class,
        [
            'maxSelection' => 0,
        ]
    )->hint(Yii::t(
        'SociologModule.base',
        'Diese Personen dürfen veröffentlichte Einträge bearbeiten, wenn der Veröffentlichungsschutz aktiviert ist.'
    )) ?>

</div>


<!-- Gruppen mit Schreibrecht -->
<div class="col-md-6">

  <h2 class="h6 fw-semibold text-primary">
    <i class="fa-solid fa-users me-1"></i>
    <?= Yii::t('SociologModule.base', 'Gruppen mit Schreibrecht') ?>
  </h2>

  <?= Html::activeCheckboxList(
      $model,
      'writerGroups',
      $groupOptions,
      [
          'class' => 'form-check',
          'separator' => '<br>',
      ]
  ) ?>

  <p class="form-text small">
    <?= Yii::t('SociologModule.base',
        'Mitglieder dieser Gruppen dürfen Einträge erstellen und – sofern nicht gesperrt – bearbeiten.'
    ) ?>
  </p>

</div>


<!-- Gruppen mit Löschrecht -->
<div class="col-md-6">

  <h2 class="h6 fw-semibold text-danger">
    <i class="fa-solid fa-users-slash me-1"></i>
    <?= Yii::t('SociologModule.base', 'Gruppen mit Löschrecht') ?>
  </h2>

  <?= Html::activeCheckboxList(
      $model,
      'deleterGroups',
      $groupOptions,
      [
          'class' => 'form-check',
          'separator' => '<br>',
      ]
  ) ?>

  <p class="form-text small">
    <?= Yii::t('SociologModule.base',
        'Mitglieder dieser Gruppen dürfen Einträge löschen (zusätzlich zu Administrator:innen).'
    ) ?>
  </p>

</div>

<!-- Verantwortliche Gruppen -->
<div class="col-md-6">

  <h2 class="h6 fw-semibold text-primary">
    <i class="fa-solid fa-user-shield me-1" aria-hidden="true"></i>
    <?= Yii::t('SociologModule.base', 'Verantwortliche Gruppen') ?>
  </h2>

  <?= Html::activeCheckboxList(
      $model,
      'managerGroups',
      $groupOptions,
      [
          'class' => 'form-check',
          'separator' => '<br>',
      ]
  ) ?>

  <p class="form-text small">
    <?= Yii::t(
        'SociologModule.base',
        'Mitglieder dieser Gruppen dürfen veröffentlichte Einträge bearbeiten, wenn der Veröffentlichungsschutz aktiviert ist.'
    ) ?>
  </p>

</div>

<div class="col-12">
  <div class="p-3 rounded border">
    <?= $form->field($model, 'lockPublishedEntries')->checkbox([
        'uncheck' => 0,
    ])->hint(Yii::t(
        'SociologModule.base',
        'Wenn deaktiviert, gelten weiterhin die bisherigen Bearbeitungsrechte. Systemadministratoren behalten immer Zugriff.'
    )) ?>

    <?= $form->field($model, 'statusManagersOnly')->checkbox([
        'uncheck' => 0,
    ])->hint(Yii::t(
        'SociologModule.base',
        'Die automatische Statuspflege bleibt unabhängig davon aktiv.'
    )) ?>

    <?= $form->field($model, 'extendedStatusesEnabled')->checkbox([
        'uncheck' => 0,
    ])->hint(Yii::t(
        'SociologModule.base',
        'Die zusätzlichen Status werden nie durch die automatische tägliche Statusprüfung überschrieben.'
    )) ?>
  </div>
</div>

    <!-- Benachrichtigungen -->
    <div class="col-12 mt-3">
      <h2 class="h6 fw-semibold text-info">
        <i class="fa-solid fa-bell me-1"></i>
        <?= Yii::t('SociologModule.base','Benachrichtigungen bei neuen oder geänderten Einträgen') ?>
      </h2>
      <?= $form->field($model, 'notifyGroups')->checkboxList(
          ArrayHelper::map(Group::find()->orderBy('name')->all(), 'id', 'name'),
          ['separator' => '<br>']
      )->label(false) ?>
      <p class="form-text small text-muted mb-0">
        <?= Yii::t('SociologModule.base','Nur Mitglieder dieser Gruppen erhalten eine Mitteilung, wenn ein Eintrag erstellt oder geändert wird.') ?><br>
        <?= Yii::t('SociologModule.base','Wenn keine Gruppe ausgewählt ist, erhalten alle aktiven Benutzer Benachrichtigungen (Standardverhalten).') ?>
      </p>
    </div>

    <!-- Entscheidungstypen -->
    <div class="col-12 mt-4">
      <h2 class="h6 fw-semibold text-secondary">
        <i class="fa-solid fa-list-check me-1"></i>
        <?= Yii::t('SociologModule.base','Entscheidungstypen') ?>
      </h2>
      <p class="form-text small">
        <?= Yii::t('SociologModule.base','Diese Typen werden in den Einträgen als „Art der Entscheidung“ angezeigt (z. B. Grundsatzentscheid, Prozessentscheid …).') ?>
      </p>
      <div class="p-3 rounded" style="background:#f8f9fa;border:1px solid #ddd;">
        <?= Html::a(
            '<i class="fa-solid fa-sliders me-1"></i> ' . Yii::t('SociologModule.base','Entscheidungstypen verwalten'),
            ['/sociolog/decision-type/index'],
            ['class'=>'btn btn-outline-secondary btn-sm']
        ) ?>
        <div class="mt-3">
          <?= $form->field($model, 'hiddenDecisionTypeIds')->checkboxList(
              $allDecisionTypeOptions,
              ['separator' => '<br>']
          )->hint(Yii::t(
              'SociologModule.base',
              'Markierte Typen werden bei neuen Einträgen und in den Filtern ausgeblendet. Bestehende Einträge bleiben unverändert und weiterhin bearbeitbar. Eine gleichzeitig ausgeblendete feste Entscheidungsart wird automatisch aufgehoben.'
          )) ?>
        </div>
      </div>
    </div>

    <!-- Optionale Formularvorgaben -->
    <div class="col-12 mt-4">
      <fieldset class="card border-secondary p-3">
        <legend class="h5 fw-semibold mb-3">
          <i class="fa-solid fa-sliders me-1" aria-hidden="true"></i>
          <?= Yii::t('SociologModule.base', 'Formular und Bezeichnungen') ?>
        </legend>

        <p class="form-text mb-4">
          <?= Yii::t(
              'SociologModule.base',
              'Alle Optionen sind standardmässig so eingestellt, dass das bisherige Verhalten erhalten bleibt.'
          ) ?>
        </p>

        <div class="row g-3">
          <div class="col-md-6">
            <?= $form->field($model, 'autoPublicationDate')->checkbox([
                'uncheck' => 0,
            ])->hint(Yii::t(
                'SociologModule.base',
                'Wenn aktiviert, wird bei neuen Einträgen automatisch das aktuelle Datum verwendet.'
            )) ?>
          </div>

          <div class="col-md-6">
            <?= $form->field($model, 'reviewDateRequiredForNewEntries')->checkbox([
                'uncheck' => 0,
            ])->hint(Yii::t(
                'SociologModule.base',
                'Bestehende Einträge ohne Überprüfungsdatum bleiben weiterhin bearbeitbar.'
            )) ?>
          </div>

          <div class="col-md-6">
            <?= $form->field($model, 'limitedReviewMaintenanceEnabled')->checkbox([
                'uncheck' => 0,
            ])->hint(Yii::t(
                'SociologModule.base',
                'Sobald „Überprüfung ab“ erreicht ist, dürfen zuständige Personen nur das nächste Überprüfungsdatum setzen und ein zusätzliches Protokoll verlinken. Andere Felder bleiben gesperrt.'
            )) ?>
          </div>

          <div class="col-md-6">
            <?= $form->field($model, 'fixedDecisionTypeId')->dropDownList(
                $decisionTypeOptions
            )->hint(Yii::t(
                'SociologModule.base',
                'Optional wird die gewählte Entscheidungsart bei neuen Einträgen fest vorgegeben.'
            )) ?>
          </div>

          <div class="col-md-6">
            <?= $form->field($model, 'showDecisionTypeHeader')->checkbox([
                'uncheck' => 0,
            ])->hint(Yii::t(
                'SociologModule.base',
                'Wenn deaktiviert, werden das farbige Typ-Schild auf Karten sowie die Typzeile in der Detailansicht ausgeblendet. Der gespeicherte Entscheidungstyp bleibt erhalten.'
            )) ?>
          </div>

          <div class="col-md-6">
            <?= $form->field($model, 'decisionDateLabel')->textInput([
                'maxlength' => true,
            ]) ?>
          </div>

          <div class="col-md-6">
            <?= $form->field($model, 'topicOwnerLabel')->textInput([
                'maxlength' => true,
            ]) ?>
          </div>

          <div class="col-md-6">
            <?= $form->field($model, 'topicOwnerPlaceholder')->textInput([
                'maxlength' => true,
                'placeholder' => Yii::t('SociologModule.base', 'Welche Gruppe setzt den Entscheid um?'),
            ]) ?>
          </div>

          <div class="col-md-6">
            <?= $form->field($model, 'protocolsLabel')->textInput([
                'maxlength' => true,
            ]) ?>
          </div>

          <div class="col-md-6">
            <?= $form->field($model, 'pendingStatusLabel')->textInput([
                'maxlength' => true,
            ]) ?>
          </div>
        </div>
      </fieldset>
    </div>

    <!-- Optionale Informationsseite -->
    <div class="col-12 mt-4">
      <fieldset class="card border-info p-3">
        <legend class="h5 fw-semibold text-info mb-3">
          <i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>
          <?= Yii::t('SociologModule.base', 'Informationsseite') ?>
        </legend>

        <?= $form->field($model, 'infoPageEnabled')->checkbox([
            'label' => Yii::t('SociologModule.base', 'Informationsseite im Logbuch anzeigen'),
            'uncheck' => 0,
        ]) ?>

        <p class="form-text mb-4">
          <?= Yii::t(
              'SociologModule.base',
              'Die Informationsseite ist vollständig von den Einträgen getrennt. Wenn sie deaktiviert ist, bleibt das Logbuch unverändert.'
          ) ?>
        </p>

        <div class="row g-3">
            <div class="col-md-6">
              <?= $form->field($model, 'infoPageTitle')->textInput([
                  'maxlength' => true,
                  'placeholder' => Yii::t('SociologModule.base', 'So funktioniert das Logbuch'),
              ]) ?>
            </div>

            <div class="col-md-6">
              <?= $form->field($model, 'infoDocumentUrl')->textInput([
                  'maxlength' => true,
                  'placeholder' => 'https://… oder /seite/pfad',
              ])->hint(Yii::t(
                  'SociologModule.base',
                  'Optionaler Link zu einem PDF, einer HumHub-Seite oder einem Dokument.'
              )) ?>
            </div>

            <div class="col-12">
              <?= $form->field($model, 'infoIntroText')->textarea([
                  'rows' => 3,
                  'maxlength' => 5000,
              ]) ?>
            </div>

            <div class="col-md-6">
              <?= $form->field($model, 'infoProcessText')->textarea([
                  'rows' => 5,
                  'maxlength' => 5000,
              ]) ?>
            </div>

            <div class="col-md-6">
              <?= $form->field($model, 'infoPermissionsText')->textarea([
                  'rows' => 5,
                  'maxlength' => 5000,
              ]) ?>
            </div>

            <div class="col-md-6">
              <?= $form->field($model, 'infoStatusText')->textarea([
                  'rows' => 5,
                  'maxlength' => 5000,
              ]) ?>
            </div>

            <div class="col-md-6">
              <?= $form->field($model, 'infoObjectionText')->textarea([
                  'rows' => 5,
                  'maxlength' => 5000,
              ]) ?>
            </div>

            <div class="col-md-6">
              <?= $form->field($model, 'infoReviewText')->textarea([
                  'rows' => 5,
                  'maxlength' => 5000,
              ]) ?>
            </div>

            <div class="col-md-6">
              <?= $form->field($model, 'infoDocumentsText')->textarea([
                  'rows' => 5,
                  'maxlength' => 5000,
              ]) ?>
            </div>

            <div class="col-md-6">
              <?= $form->field($model, 'infoGuidelineText')->textarea([
                  'rows' => 5,
                  'maxlength' => 5000,
              ]) ?>
            </div>

            <div class="col-md-6">
              <?= $form->field($model, 'infoExamplesText')->textarea([
                  'rows' => 5,
                  'maxlength' => 5000,
              ]) ?>
            </div>
        </div>
      </fieldset>
    </div>

    <!-- Buttons -->
    <div class="col-12 text-end mt-4">
      <?= Html::submitButton(
          '<i class="fa-solid fa-floppy-disk me-1"></i> ' . Yii::t('SociologModule.base','Speichern'),
          ['class'=>'btn btn-primary']
      ) ?>
      <?= Html::a(
          '<i class="fa-solid fa-arrow-left me-1"></i> ' . Yii::t('SociologModule.base','Zurück'),
          ['/admin/module'],
          ['class'=>'btn btn-outline-secondary']
      ) ?>
    </div>

    <?php ActiveForm::end(); ?>
  </div>
</div>

<!-- Wartung / Manuelle Statusprüfung -->
<div class="card shadow-sm mb-4">
  <div class="card-body">

    <h2 class="h5 fw-semibold text-warning mb-2">
      <i class="fa-solid fa-rotate me-1"></i>
      <?= Yii::t('SociologModule.base', 'Wartung') ?>
    </h2>

    <p class="text-muted mb-3">
      <?= Yii::t(
          'SociologModule.base',
          'Hier kannst du die automatische Statusprüfung manuell auslösen. Es wird exakt dieselbe Logik wie beim täglichen Cron verwendet.'
      ) ?>
    </p>

    <?= Html::a(
        '<i class="fa-solid fa-play me-1"></i> ' . Yii::t('SociologModule.base', 'Status jetzt prüfen'),
        ['run-status-check'],
        [
            'class' => 'btn btn-warning',
            'data-confirm' => Yii::t(
                'SociologModule.base',
                'Statusprüfung jetzt ausführen? Dies kann einige Sekunden dauern.'
            ),
            'data-method' => 'post',
        ]
    ) ?>

    <hr class="my-4">

    <h3 class="h6 fw-semibold mb-2">
      <i class="fa-solid fa-file-import me-1"></i>
      <?= Yii::t('SociologModule.base', 'Historische Daten') ?>
    </h3>

    <p class="text-muted mb-3">
      <?= Yii::t(
          'SociologModule.base',
          'Hier können vorbereitete historische Logbuch-Einträge zuerst geprüft und anschließend importiert werden.'
      ) ?>
    </p>

    <?= Html::a(
        '<i class="fa-solid fa-file-import me-1"></i> '
          . Yii::t('SociologModule.base', 'Historische Einträge importieren'),
        ['/sociolog/import/index'],
        ['class' => 'btn btn-outline-secondary']
    ) ?>

  </div>
</div>

<!-- Automatische Status-Updates – Info -->

<div class="alert alert-info mt-4">

  <h2 class="h5 fw-semibold text-primary mb-2">
    <i class="fa-solid fa-rotate me-1"></i>
    <?= Yii::t('SociologModule.base','Automatische Status-Updates') ?>
  </h2>

  <p>
    <?= Yii::t(
        'SociologModule.base',
        'Das Modul prüft täglich alle Einträge automatisch und passt deren Status entsprechend an.'
    ) ?>
  </p>

  <ul>
    <li>
      <?= Yii::t(
          'SociologModule.base',
          'Nach dem Entscheid wechselt der Status von „{status}“ automatisch auf „Gültig“, sobald das Inkrafttretedatum erreicht ist.',
          ['status' => $pendingStatusLabel]
      ) ?>
    </li>

    <li>
      <?= Yii::t(
          'SociologModule.base',
          'Wenn kein Überprüfungsdatum eingetragen ist, wird automatisch zwei Jahre nach dem Inkrafttreten eine Überprüfung gesetzt.'
      ) ?>
    </li>

    <li>
      <?= Yii::t(
          'SociologModule.base',
          'Der Status „Nicht mehr gültig“ wird nicht automatisch gesetzt, sondern manuell nach einer Überprüfung oder einem neuen Entscheid angepasst.'
      ) ?>
    </li>
  </ul>

  <hr>

  <p class="small">
    <?= Yii::t(
        'SociologModule.base',
        'Die tägliche Statusprüfung erfolgt automatisch über den HumHub-Cronjob (yii cron/run). Es ist kein zusätzlicher Cronjob für dieses Modul erforderlich.'
    ) ?>
  </p>

  <p class="text-muted small mb-0">
    <?= Yii::t(
        'SociologModule.base',
        'Voraussetzung ist lediglich, dass die regulären HumHub-Cronjobs aktiv sind.'
    ) ?>
  </p>

</div>

<?php
$settings = Yii::$app->getModule('sociolog')->settings;

$lastRun  = $settings->get('lastStatusRun');
$success  = $settings->get('lastStatusRunSuccess');
$error    = $settings->get('lastStatusRunError');
$duration = $settings->get('lastStatusRunDuration');
$checked  = $settings->get('lastStatusRunChecked');
$updated  = $settings->get('lastStatusRunUpdated');
?>

<div class="alert alert-light border mt-3">

<strong>
<i class="fa-solid fa-clock me-1"></i>
<?= Yii::t('SociologModule.base','Letzter Statuslauf') ?>
</strong>

<br>

<?php if ($lastRun): ?>

<?= Yii::$app->formatter->asDatetime($lastRun) ?>

<?php else: ?>

<span class="text-muted">
<?= Yii::t('SociologModule.base','Noch nie ausgeführt') ?>
</span>

<?php endif; ?>


<br><br>

<strong>
<?= Yii::t('SociologModule.base','Status') ?>:
</strong>

<?php if ($success === null): ?>

<span class="badge bg-secondary">
<?= Yii::t('SociologModule.base','Unbekannt') ?>
</span>

<?php elseif ($success): ?>

<span class="badge bg-success">
<?= Yii::t('SociologModule.base','Erfolgreich') ?>
</span>

<?php else: ?>

<span class="badge bg-danger">
<?= Yii::t('SociologModule.base','Fehler') ?>
</span>

<?php endif; ?>


<?php if ($duration !== null): ?>

<br><br>

<strong>
<?= Yii::t('SociologModule.base','Dauer') ?>:
</strong>

<?= Html::encode($duration) ?> Sekunden

<?php endif; ?>


<?php if ($checked !== null): ?>

<br>

<strong>
<?= Yii::t('SociologModule.base','Einträge geprüft') ?>:
</strong>

<?= Html::encode($checked) ?>

<?php endif; ?>


<?php if ($updated !== null): ?>

<br>

<strong>
<?= Yii::t('SociologModule.base','Einträge geändert') ?>:
</strong>

<?= Html::encode($updated) ?>

<?php endif; ?>


<?php if ($success === false && $error): ?>

<br><br>

<strong>
<?= Yii::t('SociologModule.base','Fehlermeldung') ?>:
</strong>

<div class="text-danger small">

<?= Html::encode($error) ?>

</div>

<?php endif; ?>

</div>
