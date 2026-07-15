<?php

use yii\helpers\Html;

$this->title = 'Organe';

?>

<div class="panel panel-default">

    <div class="panel-heading d-flex justify-content-between align-items-center">

        <strong><?= Html::encode($this->title) ?></strong>

        <div>

            <?= Html::a(
                '<i class="fa-solid fa-arrow-left me-1"></i> Zurück',
                ['/sociolog/admin/index'],
                ['class' => 'btn btn-sm btn-outline-secondary']
            ) ?>

            <?= Html::a(
                '<i class="fa-solid fa-plus me-1"></i> Neues Organ',
                ['create-organ'],
                ['class' => 'btn btn-sm btn-success']
            ) ?>

        </div>

    </div>

    <div class="panel-body">

        <table class="table table-striped">

            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Parent</th>
                <th>Sort</th>
                <th>Aktionen</th>
            </tr>

            <?php foreach ($organs as $organ): ?>

            <tr>

                <td><?= $organ->id ?></td>

                <td>
                <?php
                $prefix = $organ->parent_id ? '— ' : '';
                echo $prefix . Html::encode($organ->name);
                ?>
                </td>

                <td>
                <?= $organ->parent ? Html::encode($organ->parent->name) : '-' ?>
                </td>

                <td><?= (int)$organ->sort_order ?></td>

                <td>

                <?= Html::a(
                    'Bearbeiten',
                    ['update-organ', 'id' => $organ->id],
                    ['class' => 'btn btn-primary btn-xs']
                ) ?>

                <?= Html::a(
                    'Löschen',
                    ['delete-organ', 'id' => $organ->id],
                    [
                        'class' => 'btn btn-danger btn-xs',
                        'data-confirm' => 'Organ wirklich löschen?',
                        'data-method' => 'post'
                    ]
                ) ?>

                </td>

            </tr>

            <?php endforeach; ?>

        </table>

    </div>

</div>