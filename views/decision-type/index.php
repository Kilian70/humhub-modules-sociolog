<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $types humhub\modules\sociolog\models\DecisionType[] */

$this->title = Yii::t('SociologModule.base', 'Entscheid-Typen');
$this->params['breadcrumbs'][] = $this->title;

$sortUrl = Url::to(['sort']);
$csrf = Yii::$app->request->csrfToken;
?>

<!-- ============================================================
     🔹 Zurück-Link zu den Moduleinstellungen
============================================================ -->
<div class="mb-3">
  <?= Html::a(
      '<i class="fa-solid fa-arrow-left me-1"></i> ' . Yii::t('SociologModule.base', 'Zurück zu den Einstellungen'),
      ['/sociolog/admin/index'],
      ['class' => 'btn btn-outline-secondary btn-sm']
  ) ?>
</div>

<!-- ============================================================
     🔹 Kopfbereich
============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h1 class="h4 mb-0 fw-semibold">
    <i class="fa-solid fa-list me-2 text-primary" aria-hidden="true"></i>
    <?= Html::encode($this->title) ?>
  </h1>

  <?= Html::a(
    '<i class="fa-solid fa-plus me-1"></i> ' . Yii::t('SociologModule.base', 'Neuer Entscheid-Typ'),
    ['create'],
    ['class' => 'btn btn-sm btn-primary']
  ) ?>
</div>

<!-- ============================================================
     🔹 Kartenübersicht (sortable)
============================================================ -->
<div id="sortableGrid" class="type-grid">
  <?php foreach ($types as $type): ?>
    <?php
      $color = Html::encode($type->color ?: '#777');
      $textColor = \humhub\modules\sociolog\models\DecisionType::getAccessibleTextColor($color);
    ?>
    <div class="type-card" data-id="<?= $type->id ?>" style="--type-color: <?= $color ?>;">
      <div class="type-header" style="background: <?= $color ?>;"></div>

      <div class="type-body">
        <div class="badge text-uppercase mb-2"
             style="background-color: <?= $color ?>; color: <?= $textColor ?>;">
          <?= Html::encode($type->name) ?>
        </div>
        <p class="text-muted small mb-2">
          <?= Html::encode($type->description ?: Yii::t('SociologModule.base', 'Keine Beschreibung')) ?>
        </p>
        <small class="text-secondary">
          <?= Yii::t('SociologModule.base', 'Sortierung: {n}', ['n' => Html::encode($type->sort_order)]) ?>
        </small>
      </div>

      <div class="type-actions">
        <?= Html::a('<i class="fa-solid fa-pen" aria-hidden="true"></i>', ['update', 'id' => $type->id], [
          'class' => 'btn btn-sm btn-outline-secondary rounded-circle',
          'title' => Yii::t('SociologModule.base', 'Bearbeiten'),
          'aria-label' => Yii::t('SociologModule.base', '{type} bearbeiten', ['type' => $type->name]),
        ]) ?>
        <?= Html::a('<i class="fa-solid fa-trash" aria-hidden="true"></i>', ['delete', 'id' => $type->id], [
          'class' => 'btn btn-sm btn-outline-danger rounded-circle',
          'title' => Yii::t('SociologModule.base', 'Löschen'),
          'aria-label' => Yii::t('SociologModule.base', '{type} löschen', ['type' => $type->name]),
          'data-confirm' => Yii::t('SociologModule.base', 'Diesen Entscheid-Typ wirklich löschen?'),
          'data-method' => 'post',
        ]) ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- ============================================================
     🧩 Drag & Drop Sortierung (SortableJS)
============================================================ -->
<?php
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js', [
    'depends' => [\yii\web\JqueryAsset::class],
]);
$this->registerJs(<<<JS
const grid = document.getElementById('sortableGrid');
if (grid) {
  Sortable.create(grid, {
    animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 150,
    ghostClass: 'sortable-placeholder',
    onEnd: function() {
      const ids = Array.from(grid.querySelectorAll('.type-card')).map(el => el.dataset.id);
      $.post('$sortUrl', {ids: ids, _csrf: '$csrf'});
    }
  });
}
JS);
?>

<!-- ============================================================
     🎨 Styles
============================================================ -->
<style>
.type-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 20px;
}

.type-card {
  position: relative;
  border-radius: 16px;
  background: #fff;
  padding: 18px 18px 56px 18px;
  border: 1px solid #e9ecef;
  box-shadow: 0 3px 8px rgba(0,0,0,0.06);
  transition: all 0.18s ease;
}

.type-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}

.type-card:has(:focus-visible) {
  outline: 3px solid var(--type-color, var(--bs-primary, #0d6efd));
  outline-offset: 3px;
}

.type-header {
  height: 6px;
  border-radius: 6px;
  margin-bottom: 10px;
}

.type-body {
  position: relative;
  z-index: 2;
}

.type-actions {
  position: absolute;
  bottom: 10px;
  right: 10px;
  display: flex;
  gap: 6px;
  z-index: 3;
}

.sortable-placeholder {
  background: #f8f9fa;
  border: 2px dashed #adb5bd;
  border-radius: 12px;
  height: 150px;
}

@media (max-width: 576px) {
  .type-grid { grid-template-columns: 1fr; }
}

@media (prefers-reduced-motion: reduce) {
  .type-card {
    transition: none;
  }

  .type-card:hover {
    transform: none;
  }
}
</style>
