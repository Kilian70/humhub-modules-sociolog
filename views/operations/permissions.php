<?php

use humhub\modules\sociolog\models\SpaceConfig;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var SpaceConfig[] $configs */

$this->title = Yii::t('SociologModule.base', 'Zuständige Personen der Kreise');
?>

<div class="panel panel-default">
  <div class="panel-heading d-flex justify-content-between align-items-center">
    <h1 class="h5 mb-0"><?= Html::encode($this->title) ?></h1>
    <?= Html::a(
      '<i class="fa fa-arrow-left me-1" aria-hidden="true"></i>' . Yii::t('SociologModule.base', 'Zurück zum Logbuch'),
      ['/sociolog/entry/index'],
      ['class' => 'btn btn-sm btn-outline-secondary']
    ) ?>
  </div>
  <div class="panel-body">
    <p class="text-muted">
      <?= Yii::t('SociologModule.base', 'Der Betrieb des Logbuches legt hier je Space fest, ob alle Space-Administrator:innen oder nur ausgewählte zuständige Personen Einträge erfassen dürfen.') ?>
    </p>

    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th><?= Yii::t('SociologModule.base', 'Bereich / Space') ?></th>
            <th><?= Yii::t('SociologModule.base', 'Schreibberechtigung') ?></th>
            <th class="text-end"><?= Yii::t('SociologModule.base', 'Aktion') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($configs as $config): ?>
            <tr>
              <td>
                <strong><?= Html::encode($config->space->name) ?></strong>
                <?php if ($config->organ): ?>
                  <div class="small text-muted"><?= Html::encode($config->organ->name) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($config->writer_mode === SpaceConfig::WRITER_MODE_SELECTED): ?>
                  <?= Yii::t('SociologModule.base', 'Ausgewählte zuständige Personen') ?>
                  <span class="badge bg-secondary"><?= count($config->getWriterUserGuids()) ?></span>
                <?php else: ?>
                  <?= Yii::t('SociologModule.base', 'Alle Space-Administrator:innen') ?>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?= Html::a(
                  '<i class="fa fa-pencil me-1" aria-hidden="true"></i>' . Yii::t('SociologModule.base', 'Bearbeiten'),
                  ['space-permission', 'id' => $config->space_id],
                  ['class' => 'btn btn-sm btn-primary']
                ) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
