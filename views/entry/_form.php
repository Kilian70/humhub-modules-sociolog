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
$workflowEnabled = Yii::$app->getModule('sociolog')
    ->settings
    ->get('decisionWorkflowEnabled', true);

?>

<div class="card shadow-sm">
<div class="card-body">

<!-- ========================================================
     🔧 DEBUG (nur für Admins sichtbar)
======================================================== -->
<?php if (Yii::$app->user->isAdmin()): ?>
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


<!-- ======================================================
     🧩 LINKE SPALTE
====================================================== -->

<div class="col-12 col-md-6">

<?= $form->field($model,'title')
->textInput(['maxlength'=>true])
->label(Yii::t('SociologModule.base','Titel')) ?>

<?= $form->field($model,'decision_type_id')
->dropDownList(
Entry::getDecisionTypeList(),
['prompt'=>Yii::t('SociologModule.base','Bitte wählen …')]
)
->label(Yii::t('SociologModule.base','Art der Entscheidung')) ?>

<?= $form->field($model,'organ')
->dropDownList(
$allowedOrgans,
['prompt'=>Yii::t('SociologModule.base','Bitte wählen …')]
)
->label(Yii::t('SociologModule.base','Organ')) ?>

<?= $form->field($model,'topic_owner')
->textInput(['maxlength'=>true])
->label(Yii::t('SociologModule.base','Themenhüter:in')) ?>

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

<?= $form->field($model,'decision_date')
->input('date')
->label(Yii::t('SociologModule.base','Beschlussdatum')) ?>

</div>


<div class="col-12 col-md-4">

<?= $form->field($model,'effective_date')
->input('date')
->label(Yii::t('SociologModule.base','Inkrafttreten')) ?>

</div>


<div class="col-12 col-md-4">

<?= $form->field($model,'review_date')
    ->input('date')
    ->label(Yii::t('SociologModule.base','Überprüfung ab')) ?>

</div>


<!-- ======================================================
     🔗 PROTOKOLLE
====================================================== -->

<div class="col-12">

<label class="form-label">
<?= Yii::t('SociologModule.base','Protokolle') ?>
</label>

<div id="protocol-list">

<?php if (!$model->isNewRecord && $model->protocols): ?>

<?php foreach ($model->protocols as $protocol): ?>

<div class="protocol-row mb-2 d-flex gap-2 align-items-start">

<input type="hidden"
name="protocol_id[]"
value="<?= $protocol->id ?>">

<input type="text"
name="protocol_title[]"
class="form-control"
value="<?= Html::encode($protocol->title) ?>"
placeholder="<?= Yii::t('SociologModule.base','Titel') ?>">

<input type="url"
name="protocol_url[]"
class="form-control"
value="<?= Html::encode($protocol->url) ?>"
placeholder="<?= Yii::t('SociologModule.base','Link') ?>">

<button type="button"
class="btn btn-outline-danger btn-sm remove-protocol">

<i class="fa-solid fa-trash"></i>

</button>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="protocol-row mb-2">

<input type="text"
name="protocol_title[]"
class="form-control mb-1"
placeholder="<?= Yii::t('SociologModule.base','Titel (z.B. BK Protokoll)') ?>">

<input type="url"
name="protocol_url[]"
class="form-control"
placeholder="<?= Yii::t('SociologModule.base','Link zum Protokoll') ?>">

</div>

<?php endif; ?>

</div>


<button type="button"
class="btn btn-sm btn-outline-secondary mt-2"
id="add-protocol">

+ <?= Yii::t('SociologModule.base','Protokoll hinzufügen') ?>

</button>

</div>


<!-- ======================================================
     🏷️ STATUS
====================================================== -->

<?php if (!$model->isNewRecord): ?>

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
'<i class="fa-solid fa-floppy-disk me-1"></i> '
. Yii::t('SociologModule.base','Speichern'),
['class'=>'btn btn-primary me-2']
) ?>

</div>


<?php ActiveForm::end(); ?>

</div>
</div>



<?php

$script = <<<JS

$(document).on('click','#add-protocol',function(){

const row = `
<div class="protocol-row mb-2 d-flex gap-2 align-items-start">

<input type="hidden" name="protocol_id[]" value="">

<input type="text"
name="protocol_title[]"
class="form-control"
placeholder="Titel (z.B. Leitungskreis)">

<input type="url"
name="protocol_url[]"
class="form-control"
placeholder="Link zum Protokoll">

<button type="button"
class="btn btn-outline-danger btn-sm remove-protocol">

<i class="fa-solid fa-trash"></i>

</button>

</div>
`;

$('#protocol-list').append(row);

});


$(document).on('click','.remove-protocol',function(){
$(this).closest('.protocol-row').remove();
});

JS;

$this->registerJs($script);

?>
