<?php

use yii\helpers\Html;

$this->title = Yii::t('SociologModule.base', 'Organe');

?>

<div class="panel panel-default">

    <div class="panel-heading d-flex justify-content-between align-items-center">

        <h1 class="h5 mb-0"><?= Html::encode($this->title) ?></h1>

        <div>

            <?= Html::a(
                '<i class="fa fa-arrow-left me-1" aria-hidden="true"></i> ' . Yii::t('SociologModule.base', 'Zurück'),
                ['/sociolog/admin/index'],
                ['class' => 'btn btn-sm btn-outline-secondary']
            ) ?>

            <?= Html::a(
                '<i class="fa fa-plus me-1" aria-hidden="true"></i> ' . Yii::t('SociologModule.base', 'Neues Organ'),
                ['create-organ'],
                ['class' => 'btn btn-sm btn-success']
            ) ?>

        </div>

    </div>

    <div class="panel-body">

        <div class="table-responsive sociolog-admin-table-scroll"
             role="region"
             aria-label="<?= Yii::t('SociologModule.base', 'Konfigurierte Logbuch-Organe') ?>"
             tabindex="0">

        <table class="table table-striped">

            <caption class="visually-hidden">
                <?= Yii::t('SociologModule.base', 'Konfigurierte Logbuch-Organe') ?>
            </caption>

            <thead>
            <tr>
                <th scope="col"><?= Yii::t('SociologModule.base', 'ID') ?></th>
                <th scope="col"><?= Yii::t('SociologModule.base', 'Name') ?></th>
                <th scope="col"><?= Yii::t('SociologModule.base', 'Übergeordnetes Organ') ?></th>
                <th scope="col"><?= Yii::t('SociologModule.base', 'Sortierung') ?></th>
                <th scope="col"><?= Yii::t('SociologModule.base', 'Aktionen') ?></th>
            </tr>
            </thead>

            <tbody>

            <?php foreach ($organs as $organ): ?>

            <tr>

                <td><?= $organ->id ?></td>

                <th scope="row">
                <?php
                $prefix = $organ->parent_id ? '— ' : '';
                echo $prefix . Html::encode($organ->name);
                ?>
                </th>

                <td>
                <?= $organ->parent ? Html::encode($organ->parent->name) : '-' ?>
                </td>

                <td><?= (int)$organ->sort_order ?></td>

                <td>

                <?= Html::a(
                    Yii::t('SociologModule.base', 'Bearbeiten'),
                    ['update-organ', 'id' => $organ->id],
                    [
                        'class' => 'btn btn-primary btn-xs',
                        'aria-label' => Yii::t('SociologModule.base', '{organ} bearbeiten', ['organ' => $organ->name]),
                    ]
                ) ?>

                <?= Html::a(
                    Yii::t('SociologModule.base', 'Löschen'),
                    ['delete-organ', 'id' => $organ->id],
                    [
                        'class' => 'btn btn-danger btn-xs',
                        'aria-label' => Yii::t('SociologModule.base', '{organ} löschen', ['organ' => $organ->name]),
                        'data-confirm' => Yii::t('SociologModule.base', 'Organ wirklich löschen?'),
                        'data-method' => 'post'
                    ]
                ) ?>

                </td>

            </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

        </div>

    </div>

</div>

<?php
$this->registerCss(<<<CSS
.sociolog-admin-table-scroll:focus-visible {
    outline: 3px solid var(--bs-primary, #4b8f29);
    outline-offset: 3px;
}

.sociolog-admin-table-scroll:focus:not(:focus-visible) {
    outline: none;
}
CSS);
?>
