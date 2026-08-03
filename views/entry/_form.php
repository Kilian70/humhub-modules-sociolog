<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use humhub\modules\sociolog\models\Entry;

/* @var yii\web\View $this */
/* @var humhub\modules\sociolog\models\Entry $model */

// ============================================================
// 🔹 Schreibrechte / Organe
// ============================================================
$user = Yii::$app->user->identity;
$allowedOrgans = Entry::getWritableOrgansForUser($user);

// ============================================================
// 🔹 Workflow-Modus laden
// ============================================================
$module = Yii::$app->getModule('sociolog');
$settings = $module->settings;
$workflowEnabled = $settings->get('decisionWorkflowEnabled', true);
$autoPublicationDate = (bool)$settings->get('autoPublicationDate', false);
$reviewDateRequired = $model->isNewRecord
    && (bool)$settings->get('reviewDateRequiredForNewEntries', false);
$fixedDecisionTypeId = (int)$settings->get('fixedDecisionTypeId', 0);
$decisionTypes = Entry::getDecisionTypeList(
    $model->isNewRecord ? null : (int)$model->decision_type_id
);
$allDecisionTypes = Entry::getDecisionTypeList(null, true);
$fixedDecisionTypeName = $allDecisionTypes[$fixedDecisionTypeId] ?? null;
$decisionDateLabel = $module->getCustomLabel(
    'decisionDateLabel',
    Yii::t('SociologModule.base', 'Beschlussdatum')
);
$topicOwnerLabel = $module->getCustomLabel(
    'topicOwnerLabel',
    Yii::t('SociologModule.base', 'Themenhüter:in')
);
$topicOwnerPlaceholder = trim((string)$settings->get('topicOwnerPlaceholder', ''));
$protocolsLabel = $module->getCustomLabel(
    'protocolsLabel',
    Yii::t('SociologModule.base', 'Protokolle')
);
$statusManagersOnly = (bool)$settings->get('statusManagersOnly', false);
$canSelectStatus = !$statusManagersOnly || Entry::isLogbookManager($user);

?>

<div class="card shadow-sm">
<div class="card-body">

<!-- ========================================================
     🔧 DEBUG (nur für Admins sichtbar)
======================================================== -->
<?php if (defined('YII_DEBUG') && YII_DEBUG && Yii::$app->user->isAdmin()): ?>
<div class="alert alert-info py-2 mb-4 small">

<a class="text-decoration-none"
   data-bs-toggle="collapse"
   href="#sociolog-debug">

<strong>Debug (Sociolog)</strong> anzeigen

</a>

<div id="sociolog-debug" class="collapse mt-2">

<ul class="mb-0 ps-3">

<li><strong>User:</strong>
<?= Html::encode($user->username ?? '–') ?>
</li>

<li><strong>Admin:</strong> ja</li>

<li><strong>Schreibbare Organe:</strong>
<?= count($allowedOrgans) ?>
</li>

<li><strong>Organe:</strong>

<?= empty($allowedOrgans)
    ? '–'
    : Html::encode(implode(', ', array_keys($allowedOrgans))) ?>

</li>

</ul>

</div>

</div>
<?php endif; ?>


<!-- ========================================================
     📝 FORMULAR
======================================================== -->

<?php $form = ActiveForm::begin([
'options' => [
'class' => 'row g-4',
'data-ui-widget' => 'form',
'data-ui-init'   => 'modal.form',
],
]); ?>

<?= Html::errorSummary($model, [
    'id' => 'sociolog-error-summary',
    'class' => 'alert alert-danger sociolog-form-errors',
    'role' => 'alert',
    'tabindex' => '-1',
    'header' => '<strong>' . Yii::t('SociologModule.base', 'Bitte korrigiere die folgenden Fehler:') . '</strong>',
]) ?>


<!-- ======================================================
     🧩 LINKE SPALTE
====================================================== -->

<div class="col-12 col-md-6">

<?= $form->field($model,'title')
->textInput(['maxlength'=>true])
->label(Yii::t('SociologModule.base','Titel')) ?>

<?php if ($model->isNewRecord && $fixedDecisionTypeId > 0 && $fixedDecisionTypeName !== null): ?>
  <?= Html::activeHiddenInput($model, 'decision_type_id') ?>
  <div class="form-group">
    <label class="control-label" for="sociolog-fixed-decision-type">
      <?= Yii::t('SociologModule.base', 'Art der Entscheidung') ?>
    </label>
    <input id="sociolog-fixed-decision-type"
           class="form-control"
           value="<?= Html::encode($fixedDecisionTypeName) ?>"
           disabled
           aria-describedby="sociolog-fixed-decision-type-hint">
    <div id="sociolog-fixed-decision-type-hint" class="form-text">
      <?= Yii::t('SociologModule.base', 'Diese Entscheidungsart ist in den Moduleinstellungen vorgegeben.') ?>
    </div>
  </div>
<?php else: ?>
  <?= $form->field($model,'decision_type_id')
  ->dropDownList(
      $decisionTypes,
      ['prompt'=>Yii::t('SociologModule.base','Bitte wählen …')]
  )
  ->label(Yii::t('SociologModule.base','Art der Entscheidung')) ?>
<?php endif; ?>

<?= $form->field($model,'organ')
->dropDownList(
$allowedOrgans,
['prompt'=>Yii::t('SociologModule.base','Bitte wählen …')]
)
->label(Yii::t('SociologModule.base','Organ')) ?>

<?= $form->field($model,'topic_owner')
->textInput(array_filter([
    'maxlength' => true,
    'placeholder' => $topicOwnerPlaceholder !== '' ? $topicOwnerPlaceholder : null,
]))
->label($topicOwnerLabel) ?>

</div>


<!-- ======================================================
     🧩 RECHTE SPALTE
====================================================== -->

<div class="col-12 col-md-6">

<?= $form->field($model,'decision')
->textarea(['rows'=>4])
->label(Yii::t('SociologModule.base','Beschluss')) ?>

<?= $form->field($model,'description')
->textarea(['rows'=>4])
->label(Yii::t('SociologModule.base','Begründung')) ?>

</div>


<!-- ======================================================
     📅 DATEN
====================================================== -->

<div class="col-12 col-md-4">

<?php if ($model->isNewRecord && $autoPublicationDate): ?>
  <?= Html::activeHiddenInput($model, 'decision_date') ?>
  <div class="form-group">
    <label class="control-label" for="sociolog-publication-date">
      <?= Html::encode($decisionDateLabel) ?>
    </label>
    <input id="sociolog-publication-date"
           class="form-control"
           type="date"
           value="<?= Html::encode((string)$model->decision_date) ?>"
           disabled
           aria-describedby="sociolog-publication-date-hint">
    <div id="sociolog-publication-date-hint" class="form-text">
      <?= Yii::t('SociologModule.base', 'Das heutige Datum wird beim Speichern automatisch gesetzt.') ?>
    </div>
  </div>
<?php else: ?>
  <?= $form->field($model,'decision_date')
  ->input('date')
  ->label($decisionDateLabel) ?>
<?php endif; ?>

</div>


<div class="col-12 col-md-4">

<?= $form->field($model,'effective_date')
->input('date')
->label(Yii::t('SociologModule.base','Inkrafttreten')) ?>

</div>


<div class="col-12 col-md-4">

<?= $form->field($model,'review_date')
    ->input('date', [
        'required' => $reviewDateRequired,
        'aria-required' => $reviewDateRequired ? 'true' : 'false',
    ])
    ->label(
        Yii::t('SociologModule.base','Überprüfung ab')
        . ($reviewDateRequired ? ' <span class="required">*</span>' : ''),
        ['encode' => false]
    ) ?>

</div>


<!-- ======================================================
     🔗 PROTOKOLLE
====================================================== -->

<div class="col-12">

<label class="form-label" id="protocols-label">
<?= Html::encode($protocolsLabel) ?>
</label>

<?php if ($model->hasErrors('protocol_error')): ?>
<div id="protocol-error" class="invalid-feedback d-block sociolog-protocol-error" role="alert">
<?= Html::encode(implode(' ', $model->getErrors('protocol_error'))) ?>
</div>
<?php endif; ?>

<div id="protocol-list"
     role="group"
     aria-labelledby="protocols-label"
     <?= $model->hasErrors('protocol_error') ? 'aria-describedby="protocol-error"' : '' ?>>

<?php if (!$model->isNewRecord && $model->protocols): ?>

<?php foreach ($model->protocols as $protocol): ?>

<div class="protocol-row mb-2 d-flex gap-2 align-items-start">

<input type="hidden"
name="protocol_id[]"
value="<?= $protocol->id ?>">

<input type="text"
name="protocol_title[]"
class="form-control"
aria-label="<?= Yii::t('SociologModule.base','Protokolltitel') ?>"
value="<?= Html::encode($protocol->title) ?>"
placeholder="<?= Yii::t('SociologModule.base','Titel') ?>">

<input type="url"
name="protocol_url[]"
class="form-control"
aria-label="<?= Yii::t('SociologModule.base','Link zum Protokoll') ?>"
value="<?= Html::encode($protocol->url) ?>"
placeholder="<?= Yii::t('SociologModule.base','Link') ?>">

<button type="button"
class="btn btn-outline-danger btn-sm remove-protocol"
aria-label="<?= Yii::t('SociologModule.base','Protokoll entfernen') ?>">

<i class="fa fa-trash"></i>

</button>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="protocol-row mb-2">

<input type="text"
name="protocol_title[]"
class="form-control mb-1"
aria-label="<?= Yii::t('SociologModule.base','Protokolltitel') ?>"
placeholder="<?= Yii::t('SociologModule.base','Titel (z.B. BK Protokoll)') ?>">

<input type="url"
name="protocol_url[]"
class="form-control"
aria-label="<?= Yii::t('SociologModule.base','Link zum Protokoll') ?>"
placeholder="<?= Yii::t('SociologModule.base','Link zum Protokoll') ?>">

</div>

<?php endif; ?>

</div>

<div id="protocol-status" class="visually-hidden" aria-live="polite"></div>


<button type="button"
class="btn btn-sm btn-outline-secondary mt-2"
id="add-protocol">

+ <?= Yii::t('SociologModule.base','Protokoll hinzufügen') ?>

</button>

</div>


<!-- ======================================================
     🏷️ STATUS
====================================================== -->

<?php if (!$model->isNewRecord && $canSelectStatus): ?>

<div class="col-12">

<?= $form->field($model,'status')
    ->dropDownList(
        Entry::getStatusOptions(),
        ['prompt'=>Yii::t('SociologModule.base','Automatisch')]
    )
    ->label(Yii::t('SociologModule.base','Status'))
    ->hint(Yii::t(
        'SociologModule.base',
        'Standardmässig wird der Status automatisch anhand der Daten gesetzt. '
        .'„Nicht mehr gültig“ wird immer manuell gesetzt.'
    )) ?>

</div>

<?php endif; ?>



<!-- ======================================================
     🧭 AKTIONEN
====================================================== -->

<div class="col-12 d-flex justify-content-end mt-3">

<?= Html::submitButton(
'<i class="fa fa-save me-1"></i> '
. Yii::t('SociologModule.base','Speichern'),
['class'=>'btn btn-primary me-2']
) ?>

</div>


<?php ActiveForm::end(); ?>

</div>
</div>



<?php

$protocolTitleLabel = Html::encode(Yii::t('SociologModule.base', 'Protokolltitel'));
$protocolUrlLabel = Html::encode(Yii::t('SociologModule.base', 'Link zum Protokoll'));
$protocolRemoveLabel = Html::encode(Yii::t('SociologModule.base', 'Protokoll entfernen'));
$protocolAddedMessage = json_encode(Yii::t('SociologModule.base', 'Protokoll hinzugefügt'));
$protocolRemovedMessage = json_encode(Yii::t('SociologModule.base', 'Protokoll entfernt'));

$script = <<<JS

const errorSummary = document.getElementById('sociolog-error-summary');
if (errorSummary && errorSummary.querySelector('li')) {
  errorSummary.focus();
}

$(document).on('click','#add-protocol',function(){

const row = `
<div class="protocol-row mb-2 d-flex gap-2 align-items-start">

<input type="hidden" name="protocol_id[]" value="">

<input type="text"
name="protocol_title[]"
class="form-control"
aria-label="{$protocolTitleLabel}"
placeholder="Titel (z.B. Leitungskreis)">

<input type="url"
name="protocol_url[]"
class="form-control"
aria-label="{$protocolUrlLabel}"
placeholder="Link zum Protokoll">

<button type="button"
class="btn btn-outline-danger btn-sm remove-protocol"
aria-label="{$protocolRemoveLabel}">

<i class="fa fa-trash"></i>

</button>

</div>
`;

const \$row = $(row).appendTo('#protocol-list');
\$row.find('input[name="protocol_title[]"]').trigger('focus');
$('#protocol-status').text({$protocolAddedMessage});

});


$(document).on('click','.remove-protocol',function(){
$(this).closest('.protocol-row').remove();
$('#protocol-status').text({$protocolRemovedMessage});
$('#add-protocol').trigger('focus');
});

JS;

$this->registerJs($script);

$this->registerCss(<<<CSS
.sociolog-form-errors {
  color: #842029;
  background-color: #f8d7da;
  border-color: #842029;
}

.sociolog-form-errors:focus {
  outline: 3px solid #842029;
  outline-offset: 3px;
}

.sociolog-protocol-error {
  color: #842029 !important;
  font-weight: 600;
}

html[data-bs-theme="dark"] .sociolog-form-errors {
  color: #ffd7da;
  background-color: #4a171c;
  border-color: #ffb3b8;
}

html[data-bs-theme="dark"] .sociolog-form-errors:focus {
  outline-color: #ffb3b8;
}

html[data-bs-theme="dark"] .sociolog-protocol-error {
  color: #ffb3b8 !important;
}
CSS);

?>
