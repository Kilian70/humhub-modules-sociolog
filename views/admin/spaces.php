<?php

/**
 * ============================================================
 * 🔹 Gemeinsame Verwaltung von Organen, Bereichen und Spaces
 * ------------------------------------------------------------
 * Diese Seite erlaubt Administrator:innen:
 *
 * - Spaces einem Logbuch-Bereich zuzuordnen
 * - globale Schreibrechte zu vergeben
 * - Löschrechte zu vergeben
 *
 * Grundlage:
 * Tabelle: sociolog_space_config
 *
 * Controller liefert:
 *
 * $spaces
 *   → alle Spaces im System
 *
 * $configs
 *   → gespeicherte Konfiguration pro Space
 *
 * ============================================================
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;


/* ------------------------------------------------------------
 * Seitentitel (übersetzbar)
 * ------------------------------------------------------------ */

$this->title = Yii::t('SociologModule.base', 'Organe, Bereiche und Spaces verwalten');


/* ------------------------------------------------------------
 * Spaces nach Organen gruppieren und Organe hierarchisch ordnen.
 * Auch Organe ohne zugeordneten Space bleiben sichtbar.
 * ------------------------------------------------------------ */

$groupedSpaces = [];

foreach ($spaces as $space) {

    $config = $configs[$space->id] ?? null;

    $organId = $config->organ_id ?? 0;

    $groupedSpaces[$organId][] = $space;
}


$organsByParent = [];
foreach ($organe as $organ) {
    $organsByParent[(int)($organ->parent_id ?: 0)][] = $organ;
}

$orderedOrgans = [];
$visitedOrgans = [];
$walkOrgans = static function (int $parentId, int $level = 0) use (&$walkOrgans, &$organsByParent, &$orderedOrgans, &$visitedOrgans): void {
    foreach ($organsByParent[$parentId] ?? [] as $organ) {
        if (isset($visitedOrgans[$organ->id])) {
            continue;
        }

        $visitedOrgans[$organ->id] = true;
        $orderedOrgans[] = ['model' => $organ, 'level' => $level];
        $walkOrgans((int)$organ->id, $level + 1);
    }
};
$walkOrgans(0);

// Verwaiste Altdaten nicht aus der Verwaltung verschwinden lassen.
foreach ($organe as $organ) {
    if (!isset($visitedOrgans[$organ->id])) {
        $orderedOrgans[] = ['model' => $organ, 'level' => 0];
    }
}

$organGroups = [];
foreach ($orderedOrgans as $item) {
    $organ = $item['model'];
    $organGroups[] = [
        'id' => (int)$organ->id,
        'model' => $organ,
        'level' => (int)$item['level'],
        'spaces' => $groupedSpaces[$organ->id] ?? [],
    ];
}

if (!empty($groupedSpaces[0])) {
    $organGroups[] = [
        'id' => 0,
        'model' => null,
        'level' => 0,
        'spaces' => $groupedSpaces[0],
    ];
}

?>

<?php
/* ------------------------------------------------------------
 * Formular starten
 *
 * Alle Werte der Tabelle werden über POST
 * an AdminController::actionSpaces() gesendet.
 * ------------------------------------------------------------ */
$form = ActiveForm::begin([
    'id' => 'sociolog-space-config-form',
]);
?>


<div class="panel panel-default">

    <!-- ======================================================
         Panel Header
         ====================================================== -->

 <div class="panel-heading d-flex justify-content-between align-items-center">

    <h1 class="h5 mb-0"><?= Html::encode($this->title) ?></h1>

    <div>
        <?= Html::a(
            '<i class="fa fa-users me-1" aria-hidden="true"></i> ' . Yii::t('SociologModule.base', 'Zuständige Personen'),
            ['/sociolog/operations/permissions'],
            ['class' => 'btn btn-sm btn-info']
        ) ?>
        <?= Html::a(
            '<i class="fa fa-arrow-left me-1"></i> ' . Yii::t('SociologModule.base', 'Zurück zu Einstellungen'),
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


    <!-- ======================================================
         Panel Body
         ====================================================== -->

    <div class="panel-body">


<!-- ==================================================
     Erklärung für Administrator:innen
     ================================================== -->

<p class="text-muted">

<?= Yii::t('SociologModule.base', 'Die Organisationsstruktur und alle zugehörigen Spaces werden hier gemeinsam dargestellt. Organe bilden die Überschriften; die eingerückten Spaces gehören zum jeweiligen Organ.') ?>

<br><br>

<?= Yii::t('SociologModule.base', 'Die Schreibberechtigung wird je Space auf alle Space-Administrator:innen oder auf ausgewählte zuständige Personen festgelegt.') ?>

<br>

<?= Yii::t(
    'SociologModule.base',
    'Spaces mit {global} dürfen in allen Bereichen schreiben.',
    [
        'global' => '<strong>' . Yii::t('SociologModule.base', 'globalem Schreibrecht') . '</strong>',
    ]
) ?>

</p>


        <!-- ==================================================
             Tabelle
             ================================================== -->

        <div class="table-responsive sociolog-admin-table-scroll"
             role="region"
             aria-label="<?= Yii::t('SociologModule.base', 'Konfiguration der Spaces und Bereiche') ?>"
             tabindex="0">

        <table class="table table-hover table-sm">

            <caption class="visually-hidden">
                <?= Yii::t('SociologModule.base', 'Konfiguration der Spaces und Bereiche') ?>
            </caption>

            <thead>

			<tr>
			
				<th scope="col" style="width:22%">
			<?= Yii::t('SociologModule.base', 'Organ / Space') ?>
			</th>
			
				<th scope="col" style="width:22%">
			<?= Yii::t('SociologModule.base', 'Zugeordnet zu') ?>
			</th>
			
				<th scope="col" style="width:8%" class="text-center">
			<?= Yii::t('SociologModule.base', 'Organ-Space') ?>
			</th>
			
				<th scope="col" style="width:8%" class="text-center">
			<?= Yii::t('SociologModule.base', 'Global') ?>
			</th>
			
				<th scope="col" style="width:8%" class="text-center">
			<?= Yii::t('SociologModule.base', 'Löschen') ?>
			</th>
			
				<th scope="col" style="width:8%" class="text-center">
			<?= Yii::t('SociologModule.base', 'Sichtbar') ?>
			</th>
			
				<th scope="col" style="width:12%">
			<?= Yii::t('SociologModule.base', 'Link') ?>
			</th>
			
				<th scope="col" style="width:40%">
			<?= Yii::t('SociologModule.base', 'URL') ?>
			</th>
			
			</tr>

            </thead>


            <tbody>


            <?php foreach ($organGroups as $group): ?>


                <!-- ==========================================
                     Bereichsüberschrift
                     ========================================== -->

                <?php $organ = $group['model']; ?>
                <tr class="table-secondary sociolog-organ-row">

                    <th scope="rowgroup" colspan="8">

                        <div class="d-flex justify-content-between align-items-center" style="padding-left:<?= (int)$group['level'] * 18 ?>px">
                            <span>
                                <i class="fa <?= $organ ? 'fa-sitemap' : 'fa-inbox' ?> me-1" aria-hidden="true"></i>
                                <strong><?= Html::encode($organ ? $organ->name : Yii::t('SociologModule.base', 'Noch nicht zugeordnet')) ?></strong>
                                <?php if ($organ && $organ->parent): ?>
                                    <small class="text-muted ms-2">
                                        <?= Yii::t('SociologModule.base', 'unter {parent}', ['parent' => Html::encode($organ->parent->name)]) ?>
                                    </small>
                                <?php endif; ?>
                            </span>

                            <?php if ($organ): ?>
                                <span class="sociolog-organ-actions">
                                    <?= Html::a(
                                        '<i class="fa fa-pencil" aria-hidden="true"></i> ' . Yii::t('SociologModule.base', 'Bearbeiten'),
                                        ['update-organ', 'id' => $organ->id],
                                        [
                                            'class' => 'btn btn-primary btn-xs',
                                            'aria-label' => Yii::t('SociologModule.base', '{organ} bearbeiten', ['organ' => $organ->name]),
                                        ]
                                    ) ?>
                                    <?= Html::a(
                                        '<i class="fa fa-trash" aria-hidden="true"></i> ' . Yii::t('SociologModule.base', 'Löschen'),
                                        ['delete-organ', 'id' => $organ->id],
                                        [
                                            'class' => 'btn btn-danger btn-xs',
                                            'aria-label' => Yii::t('SociologModule.base', '{organ} löschen', ['organ' => $organ->name]),
                                            'data-confirm' => Yii::t('SociologModule.base', 'Organ wirklich löschen?'),
                                            'data-method' => 'post',
                                        ]
                                    ) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                    </th>

                </tr>


                <?php foreach ($group['spaces'] as $space): ?>


                    <?php
                    /* gespeicherte Konfiguration laden */
                    $config = $configs[$space->id] ?? null;
                    ?>


                    <tr data-space-config-row="<?= (int)$space->id ?>">


                        <!-- ==================================
                             Space Name + Öffnen Icon
                             ================================== -->

                        <td>

                            <?= Html::hiddenInput('space_rows[' . (int)$space->id . ']', '1') ?>

                            <span class="sociolog-space-indent" style="padding-left:<?= ((int)$group['level'] + 1) * 18 ?>px">
                            <i class="fa fa-users text-muted me-1" aria-hidden="true"></i>
                            <strong>
                                <?= Html::encode($space->name) ?>
                            </strong>

                            <?= Html::a(
                                '<i class="fa fa-external-link"></i>',
                                $space->getUrl(),
                                [
                                    'class' => 'text-muted ms-2',
                                    'title' => Yii::t('SociologModule.base', 'Space öffnen'),
                                    'aria-label' => Yii::t('SociologModule.base', 'Space öffnen') . ': ' . $space->name . ' – ' . Yii::t('SociologModule.base', 'öffnet in neuem Fenster'),
                                    'target' => '_blank',
                                    'rel' => 'noopener noreferrer',
                                ]
                            ) ?>
                            </span>

                        </td>


                        <!-- ==================================
                             Organ auswählen
                             ================================== -->

                        <td>

						<select
								name="organ_id[<?= $space->id ?>]"
								class="form-control"
								aria-label="<?= Html::encode(Yii::t('SociologModule.base', 'Organ für {space}', ['space' => $space->name])) ?>"
						>
						
						<option value="">
						<?= Yii::t('SociologModule.base', '– kein Organ –') ?>
						</option>
						
						<?php foreach ($orderedOrgans as $organItem): ?>
						<?php $optionOrgan = $organItem['model']; ?>
						
						<option
							value="<?= $optionOrgan->id ?>"
							<?= ($config && $config->organ_id == $optionOrgan->id) ? 'selected' : '' ?>
						>
						
						<?= Html::encode(str_repeat('— ', (int)$organItem['level']) . $optionOrgan->name) ?>
						
						</option>
						
						<?php endforeach; ?>
						
						</select>

						</td>
						
						<td class="text-center">
						
						<input
						type="checkbox"
							name="is_organ_space[<?= $space->id ?>]"
							aria-label="<?= Html::encode(Yii::t('SociologModule.base', 'Organ-Space: {space}', ['space' => $space->name])) ?>"
						<?= ($config && $config->is_organ_space) ? 'checked' : '' ?>
						>
						
						</td>


                        <!-- ==================================
                             Globales Schreibrecht
                             ================================== -->

                        <td class="text-center">

						<input
						type="checkbox"
							name="global_write[<?= $space->id ?>]"
							aria-label="<?= Html::encode(Yii::t('SociologModule.base', 'Globales Schreibrecht für {space}', ['space' => $space->name])) ?>"
						<?= ($config && $config->global_write)
						? 'checked'
						: '' ?>
						>
						
						</td>


                        <!-- ==================================
                             Löschrecht
                             ================================== -->

                       <td class="text-center">

						<input
type="checkbox"
	name="can_delete[<?= $space->id ?>]"
	aria-label="<?= Html::encode(Yii::t('SociologModule.base', 'Löschrecht für {space}', ['space' => $space->name])) ?>"
<?= ($config && $config->can_delete) ? 'checked' : '' ?>
>
						
						</td>
                        
 <td class="text-center">

<input
type="checkbox"
	name="enabled[<?= $space->id ?>]"
	aria-label="<?= Html::encode(Yii::t('SociologModule.base', 'Sichtbarkeit für {space}', ['space' => $space->name])) ?>"
<?= (!$config || (isset($config->enabled) && $config->enabled)) ? 'checked' : '' ?>
>

</td>

<td>

<select name="link_mode[<?= $space->id ?>]"
        class="form-control form-control-sm"
        aria-label="<?= Html::encode(Yii::t('SociologModule.base', 'Linkart für {space}', ['space' => $space->name])) ?>">

<option value="about"
<?= (!$config || $config->link_mode === 'about') ? 'selected' : '' ?>>
<?= Yii::t('SociologModule.base', 'Space-About-Seite') ?>
</option>

<option value="space"
<?= ($config && $config->link_mode === 'space') ? 'selected' : '' ?>>
<?= Yii::t('SociologModule.base', 'Space-Startseite') ?>
</option>

<option value="custom"
<?= ($config && $config->link_mode === 'custom') ? 'selected' : '' ?>>
<?= Yii::t('SociologModule.base', 'Externer Link') ?>
</option>

<option value="none"
<?= ($config && $config->link_mode === 'none') ? 'selected' : '' ?>>
<?= Yii::t('SociologModule.base', 'Kein Link') ?>
</option>

</select>

</td>

<td>

<input
type="text"
name="link[<?= $space->id ?>]"
value="<?= $config->link ?? '' ?>"
	class="form-control form-control-sm"
	aria-label="<?= Html::encode(Yii::t('SociologModule.base', 'URL für {space}', ['space' => $space->name])) ?>"
style="width:100%"
placeholder="<?= Yii::t('SociologModule.base','https://... (optional)') ?>"
>

<?= Html::hiddenInput('space_rows_complete[' . (int)$space->id . ']', '1') ?>

</td>


                    </tr>


                <?php endforeach; ?>


            <?php endforeach; ?>


            </tbody>

        </table>

        </div>


        <!-- ==================================================
             Speichern Button
             ================================================== -->

        <div class="form-group mt-3">

            <?= Html::submitButton(
                '<i class="fa fa-save me-1"></i> ' .
                Yii::t('SociologModule.base', 'Speichern'),
                [
                    'class' => 'btn btn-primary',
                    'id' => 'sociolog-space-config-save',
                    'disabled' => true,
                ]
            ) ?>

        </div>


    </div>

</div>


<?php
/* ------------------------------------------------------------
 * Formular beenden
 * ------------------------------------------------------------ */
ActiveForm::end();

$this->registerJs(<<<'JS'
(function () {
    const form = document.getElementById('sociolog-space-config-form');
    const saveButton = document.getElementById('sociolog-space-config-save');

    if (!form || !saveButton) {
        return;
    }

    const rows = Array.from(form.querySelectorAll('[data-space-config-row]'));

    rows.forEach(function (row) {
        row.querySelectorAll('input, select, textarea').forEach(function (control) {
            if (control.type === 'hidden') {
                return;
            }

            control.addEventListener('change', function () {
                row.dataset.changed = '1';
                saveButton.disabled = false;
            });
            control.addEventListener('input', function () {
                row.dataset.changed = '1';
                saveButton.disabled = false;
            });
        });
    });

    form.addEventListener('submit', function () {
        rows.forEach(function (row) {
            if (row.dataset.changed === '1') {
                return;
            }

            row.querySelectorAll('input, select, textarea').forEach(function (control) {
                control.disabled = true;
            });
        });
    });
})();
JS);
?>

<?php
$this->registerCss(<<<CSS
.sociolog-admin-table-scroll:focus-visible {
    outline: 3px solid var(--bs-primary, #4b8f29);
    outline-offset: 3px;
}

.sociolog-admin-table-scroll:focus:not(:focus-visible) {
    outline: none;
}

.sociolog-organ-row > th {
    border-left: 4px solid var(--bs-info, #17a2b8);
}

.sociolog-organ-actions {
    white-space: nowrap;
}

.sociolog-space-indent {
    display: inline-block;
}
CSS);
?>
