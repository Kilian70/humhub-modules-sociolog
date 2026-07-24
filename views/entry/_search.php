<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use humhub\modules\sociolog\models\Entry;

/* @var $model humhub\modules\sociolog\models\EntrySearch */

// ------------------------------------------------------------
// 🔹 Prüfen, ob Filter aktiv sind (Server-Zustand)
// ------------------------------------------------------------
$hasFilter =
    $model->year !== null && $model->year !== '' ||
    !empty($model->query) ||
    !empty($model->organ) ||
    !empty($model->status) ||
    !empty($model->decision_type_id);

// Aktuellen View-Modus (cards / table) erhalten
$currentView = Yii::$app->request->get('view', 'cards');
?>

<!-- ============================================================
     🔍 Such-Panel
============================================================ -->
<div id="searchPanel"
     class="card shadow-sm border-0 mb-3 <?= $hasFilter ? '' : 'd-none' ?>">

  <div class="card-body pb-2">

    <?php $form = ActiveForm::begin([
        'method'  => 'get',
        'action'  => ['index'],
        'options' => ['class' => 'row g-3 align-items-end'],
    ]); ?>

      <!-- View-Modus beibehalten -->
      <?= Html::hiddenInput('view', $currentView) ?>

      <!-- Jahr -->
      <div class="col-6 col-md-2">
        <?= $form->field($model, 'year')
          ->dropDownList(
              ['' => Yii::t('SociologModule.base', 'Alle Jahre')] + Entry::getAvailableYears(),
              ['class' => 'form-select']
          )
          ->label(Yii::t('SociologModule.base', 'Jahr')) ?>
      </div>

      <!-- Titel / Beschluss -->
      <div class="col-12 col-md-3">
        <?= $form->field($model, 'query')
          ->textInput([
              'placeholder' => Yii::t('SociologModule.base', 'Titel oder Beschluss …'),
              'class'       => 'form-control',
          ])
          ->label(Yii::t('SociologModule.base', 'Titel / Beschluss')) ?>
      </div>

      <!-- Organ -->
      <div class="col-12 col-md-2">
        <?= $form->field($model, 'organ')
          ->dropDownList(
              Entry::getOrganList(),
              [
                  'prompt' => Yii::t('SociologModule.base', 'Organ auswählen …'),
                  'class'  => 'form-select',
              ]
          )
          ->label(Yii::t('SociologModule.base', 'Organ')) ?>
      </div>

      <!-- Art der Entscheidung -->
      <div class="col-12 col-md-2">
        <?= $form->field($model, 'decision_type_id')
          ->dropDownList(
              Entry::getDecisionTypeList(),
              [
                  'prompt' => Yii::t('SociologModule.base', 'Art der Entscheidung …'),
                  'class'  => 'form-select',
              ]
          )
          ->label(Yii::t('SociologModule.base', 'Art der Entscheidung')) ?>
      </div>

      <!-- Status -->
      <div class="col-12 col-md-2">
        <?= $form->field($model, 'status')
          ->dropDownList(
              Entry::getStatusOptions(),
              [
                  'prompt' => Yii::t('SociologModule.base', 'Status auswählen …'),
                  'class'  => 'form-select',
              ]
          )
          ->label(Yii::t('SociologModule.base', 'Status')) ?>
      </div>

      <!-- Buttons -->
      <div class="col-12 col-md-1 text-end">
        <div class="d-flex justify-content-end gap-2">

          <?= Html::submitButton(
              '<i class="fa-solid fa-search"></i>',
              [
                  'class'       => 'btn btn-primary btn-sm',
                  'title'       => Yii::t('SociologModule.base', 'Suchen'),
                  'aria-label'  => Yii::t('SociologModule.base', 'Suchen'),
              ]
          ) ?>

          <?= Html::a(
              '<i class="fa-solid fa-rotate-left"></i>',
              ['index', 'view' => $currentView],
              [
                  'class'      => 'btn btn-outline-secondary btn-sm',
                  'title'      => Yii::t('SociologModule.base', 'Filter zurücksetzen'),
                  'aria-label' => Yii::t('SociologModule.base', 'Filter zurücksetzen'),
              ]
          ) ?>

        </div>
      </div>

    <?php ActiveForm::end(); ?>

  </div>
</div>
