<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\assets\SociologAsset;
use humhub\modules\sociolog\models\SpaceConfig;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var humhub\modules\sociolog\models\EntrySearch $searchModel */

SociologAsset::register($this);

/** @var \humhub\modules\sociolog\Module $module */
$module = Yii::$app->getModule('sociolog');
$moduleTitle = $module && $module->settings->get('moduleTitle')
    ? $module->settings->get('moduleTitle')
    : 'Logbuch';

/* ============================================================
   🧭 View-Modus: Request → Session → Default
============================================================ */
$requestedView = Yii::$app->request->get('view');
if (in_array($requestedView, ['cards', 'table'], true)) {
    $viewMode = $requestedView;
    Yii::$app->session->set('sociologViewMode', $viewMode);
} else {
    $viewMode = Yii::$app->session->get('sociologViewMode', 'cards');
}

/* ============================================================
   🔍 Suchstatus (für Panel-Startzustand)
============================================================ */
$hasFilter =
    !empty($searchModel->year) ||
    !empty($searchModel->query) ||
    !empty($searchModel->organ) ||
    !empty($searchModel->status) ||
    !empty($searchModel->decision_type_id);

$this->title = $moduleTitle . ' – ' . Yii::t('SociologModule.base', 'Einträge');

$years = ['' => Yii::t('SociologModule.base', 'Alle Jahre')]
    + array_reverse(Entry::getAvailableYears(), true);
?>


<?php

/* ============================================================
   🧭 Organe aus SpaceConfig laden
============================================================ */

$spaceConfigs = SpaceConfig::find()
    ->where(['enabled' => 1])
    ->indexBy('space_id')
    ->all();

$entriesByOrgan = [];

foreach ($dataProvider->models as $entry) {

    $spaceId = $entry->content->container_id ?? null;

    if ($spaceId && isset($spaceConfigs[$spaceId])) {

        $organ = $entry->organName ?: '';

    } else {

        $organ = '';
    }

    $entriesByOrgan[$organ][] = $entry;
}
?>


<!-- ============================================================
     🔹 KOPFBEREICH
============================================================ -->
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2 sociolog-header">

  <div>
    <h1 class="h3 mb-1">
      <i class="fa-solid fa-book me-2 text-primary" aria-hidden="true"></i>
      <?= Html::encode($moduleTitle) ?>
      <span class="text-muted ms-1">/ <?= Yii::t('SociologModule.base', 'Einträge') ?></span>
    </h1>

    <!-- ✅ Status-Legende (einmalig) -->
    <div class="small sociolog-status-legend">
      <strong><?= Yii::t('SociologModule.base', 'Status') ?>:</strong>
      <span class="badge bg-secondary"><?= Yii::t('SociologModule.base', 'Nicht in Kraft') ?></span>
      <span class="badge bg-success"><?= Yii::t('SociologModule.base', 'Gültig') ?></span>
      <span class="badge badge-sociolog-review">
        <?= Yii::t('SociologModule.base', 'Überprüfung fällig') ?>
      </span>
      <span class="badge bg-dark"><?= Yii::t('SociologModule.base', 'Nicht mehr gültig') ?></span>
    </div>
  </div>

  <div class="btn-group">
    <?php if (\humhub\modules\sociolog\models\Entry::canCreateGlobal(Yii::$app->user->identity)): ?>
      <?= Html::a(
        '<i class="fa-solid fa-plus me-1"></i>' . Yii::t('SociologModule.base', 'Neuer Eintrag'),
        ['create', 'view' => $viewMode],
        ['class' => 'btn btn-sm btn-primary']
      ) ?>
    <?php endif; ?>

    <?= Html::a(
      '<i class="fa-solid fa-file-csv me-1"></i>' . Yii::t('SociologModule.base', 'Export CSV'),
      ['export-csv'],
      ['class' => 'btn btn-sm btn-outline-secondary']
    ) ?>

    <?= Html::a(
      '<i class="fa-solid fa-th" aria-hidden="true"></i>',
      ['index', 'view' => 'cards'],
      [
        'class' => 'btn btn-sm ' . ($viewMode === 'cards'
          ? 'btn-primary'
          : 'btn-outline-secondary'),
        'title' => Yii::t('SociologModule.base', 'Kachelansicht'),
        'aria-label' => Yii::t('SociologModule.base', 'Kachelansicht'),
        'aria-current' => $viewMode === 'cards' ? 'page' : null,
      ]
    ) ?>

    <?= Html::a(
      '<i class="fa-solid fa-table" aria-hidden="true"></i>',
      ['index', 'view' => 'table'],
      [
        'class' => 'btn btn-sm ' . ($viewMode === 'table'
          ? 'btn-primary'
          : 'btn-outline-secondary'),
        'title' => Yii::t('SociologModule.base', 'Tabellenansicht'),
        'aria-label' => Yii::t('SociologModule.base', 'Tabellenansicht'),
        'aria-current' => $viewMode === 'table' ? 'page' : null,
      ]
    ) ?>
  </div>

</div>

<!-- ============================================================
     🔍 SUCHE
============================================================ -->


<div id="searchPanel" class="card shadow-sm border-0 mb-3 sociolog-filter">
  <div class="card-body pb-2">

    <div class="sociolog-filter-title">
      <i class="fa-solid fa-filter me-1"></i>
      <?= Yii::t('SociologModule.base', 'Filter') ?>
    </div>

    <?php $form = ActiveForm::begin(['method' => 'get']); ?>
    <?= Html::hiddenInput('view', $viewMode) ?>

    <div class="row g-3 align-items-end">

      <div class="col-md-2">
        <?= $form->field($searchModel, 'year')
          ->dropDownList($years, [
            'prompt' => Yii::t('SociologModule.base', 'Jahr auswählen...')
          ]) ?>
      </div>

      <div class="col-md-3">
        <?= $form->field($searchModel, 'organ')
          ->dropDownList(
            Entry::getOrganList(),
            ['prompt' => Yii::t('SociologModule.base', 'Organ auswählen...')]
          ) ?>
      </div>

      <div class="col-md-3">
        <?= $form->field($searchModel, 'query')
          ->textInput([
            'placeholder' => Yii::t('SociologModule.base', 'Titel oder Beschluss ...')
          ]) ?>
      </div>

      <div class="col-md-2">
        <?= $form->field($searchModel, 'decision_type_id')
          ->dropDownList(
            Entry::getDecisionTypeList(),
            ['prompt' => Yii::t('SociologModule.base', 'Art der Entscheidung ...')]
          ) ?>
      </div>

      <div class="col-md-2">
        <?= $form->field($searchModel, 'status')
          ->dropDownList([
            'pending' => Yii::t('SociologModule.base', 'Nicht in Kraft'),
            'valid'   => Yii::t('SociologModule.base', 'Gültig'),
            'review'  => Yii::t('SociologModule.base', 'In Überprüfung'),
            'expired' => Yii::t('SociologModule.base', 'Nicht mehr gültig'),
          ], [
            'prompt' => Yii::t('SociologModule.base', 'Status auswählen...')
          ]) ?>
      </div>

    </div>

    <div class="text-end mt-3">
      <?= Html::submitButton(
        '🔍 ' . Yii::t('SociologModule.base', 'Suchen'),
        ['class' => 'btn btn-primary btn-sm']
      ) ?>
      <?= Html::a(
        '✖️ ' . Yii::t('SociologModule.base', 'Zurücksetzen'),
        ['index', 'view' => $viewMode],
        ['class' => 'btn btn-outline-secondary btn-sm']
      ) ?>
    </div>

    <?php ActiveForm::end(); ?>
  </div>
</div>

<!-- ============================================================
     🧱 KACHELANSICHT
============================================================ -->
<?php if ($viewMode === 'cards'): ?>

<div class="container-fluid px-0">

<?php foreach ($entriesByOrgan as $organ => $entries): ?>

    <div class="mb-4">

        <h5 class="text-primary mb-3">
            <?= Html::encode($organ) ?>
        </h5>

        <div class="sociolog-grid">

            <?php foreach ($entries as $model): ?>
                <?= $this->render('_card', [
                    'model' => $model,
                    'spaceConfigs' => $spaceConfigs ?? []
                ]) ?>
            <?php endforeach; ?>

        </div>

    </div>

<?php endforeach; ?>

</div>

<?php endif; ?>

<!-- ============================================================
     📋 TABELLENANSICHT
============================================================ -->
<?php if ($viewMode === 'table'): ?>
  <?= $this->render('_table', ['dataProvider' => $dataProvider]) ?>
<?php endif; ?>

<?php if (empty($dataProvider->models)): ?>
  <div class="alert alert-info mt-3">
    <?= Yii::t('SociologModule.base', 'Es sind noch keine Einträge vorhanden.') ?>
  </div>
<?php endif; ?>
