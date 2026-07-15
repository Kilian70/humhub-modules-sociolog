<?php

/**
 * ============================================================
 * 🔹 Admin-View: Spaces & Bereiche
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

$this->title = Yii::t('SociologModule.base', 'Spaces & Bereiche');


/* ------------------------------------------------------------
 * Bereiche aus Modul-Einstellungen laden
 *
 * SettingsForm speichert diese im Modul unter:
 *
 * sociolog.settings → organs
 *
 * Beispiel:
 *
 * Hausverein
 * Leitungskreis
 * Gemeinschaftsräume
 * ------------------------------------------------------------ */

$organs = Yii::$app->getModule('sociolog')->settings->get('organs', '');

$bereicheRaw = array_filter(
    preg_split('/\r\n|\r|\n/', trim($organs))
);

$bereiche = [];

foreach ($bereicheRaw as $line) {

    if (preg_match('/^(\d+)\s+(.*)$/', trim($line), $m)) {

        $bereiche[$m[2]] = (int)$m[1];

    } else {

        $bereiche[$line] = 9999;

    }

}


/* ------------------------------------------------------------
 * Spaces nach Bereichen gruppieren
 *
 * Dadurch wird die Tabelle übersichtlicher:
 *
 * Hausverein
 *   BG Vereinsverwaltung
 *
 * Gemeinschaftsräume
 *   BG Musikraum
 *
 * – ohne Bereich –
 *   BG ICT
 *
 * ------------------------------------------------------------ */



$groupedSpaces = [];

foreach ($spaces as $space) {

    $config = $configs[$space->id] ?? null;

    $organId = $config->organ_id ?? 0;

    $groupedSpaces[$organId][] = $space;
}


/* ------------------------------------------------------------
 * Organe sortieren
 * ------------------------------------------------------------ */

$organSort = [];

foreach ($organe as $organ) {
    $organSort[$organ->id] = $organ->sort_order;
}

$organSort[0] = -1; // ohne Organ immer zuletzt


uksort($groupedSpaces, function ($a, $b) use ($organSort) {

    $orderA = $organSort[$a] ?? 9999;
    $orderB = $organSort[$b] ?? 9999;

    return $orderA <=> $orderB;

});


/* ------------------------------------------------------------
 * Organ Namen Map (ID → Name)
 * ------------------------------------------------------------ */

$organNames = [];

foreach ($organe as $organ) {
    $organNames[$organ->id] = $organ->name;
}

$organNames[0] = Yii::t('SociologModule.base', '– kein Organ –');

?>

<?php
/* ------------------------------------------------------------
 * Formular starten
 *
 * Alle Werte der Tabelle werden über POST
 * an AdminController::actionSpaces() gesendet.
 * ------------------------------------------------------------ */
$form = ActiveForm::begin();
?>


<div class="panel panel-default">

    <!-- ======================================================
         Panel Header
         ====================================================== -->

 <div class="panel-heading d-flex justify-content-between align-items-center">

    <strong>
        <?= Yii::t('SociologModule.base', 'Spaces und Logbuch-Bereiche') ?>
    </strong>

    <?= Html::a(
        '<i class="fa-solid fa-arrow-left me-1"></i> ' .
        Yii::t('SociologModule.base', 'Zurück zu Einstellungen'),
        ['/sociolog/admin/index'],
        ['class' => 'btn btn-sm btn-outline-secondary']
    ) ?>

</div>


    <!-- ======================================================
         Panel Body
         ====================================================== -->

    <div class="panel-body">


<!-- ==================================================
     Erklärung für Administrator:innen
     ================================================== -->

<p class="text-muted">

<?= Yii::t(
    'SociologModule.base',
    'Hier kannst du festlegen, welcher {space} zu welchem {bereich} gehört.',
    [
        'space' => '<strong>' . Yii::t('SociologModule.base', 'Space') . '</strong>',
        'bereich' => '<strong>' . Yii::t('SociologModule.base', 'Bereich im Logbuch') . '</strong>',
    ]
) ?>

<br><br>

<?= Yii::t(
    'SociologModule.base',
    'Administrator:innen eines Spaces dürfen automatisch im Logbuch ihres Bereichs schreiben.'
) ?>

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

        <table class="table table-hover table-sm">

            <thead>

			<tr>
			
			<th style="width:22%">
			<?= Yii::t('SociologModule.base', 'Space') ?>
			</th>
			
			<th style="width:22%">
			<?= Yii::t('SociologModule.base', 'Bereich') ?>
			</th>
			
			<th style="width:8%" class="text-center">
			<?= Yii::t('SociologModule.base', 'Organ-Space') ?>
			</th>
			
			<th style="width:8%" class="text-center">
			<?= Yii::t('SociologModule.base', 'Global') ?>
			</th>
			
			<th style="width:8%" class="text-center">
			<?= Yii::t('SociologModule.base', 'Löschen') ?>
			</th>
			
			<th style="width:8%" class="text-center">
			<?= Yii::t('SociologModule.base', 'Sichtbar') ?>
			</th>
			
			<th style="width:12%">
			<?= Yii::t('SociologModule.base', 'Link') ?>
			</th>
			
			<th style="width:40%">
			<?= Yii::t('SociologModule.base', 'URL') ?>
			</th>
			
			</tr>

            </thead>


            <tbody>


            <?php foreach ($groupedSpaces as $bereich => $spaceList): ?>


                <!-- ==========================================
                     Bereichsüberschrift
                     ========================================== -->

                <tr class="table-secondary">

                    <td colspan="7">

                        <strong>
                            <?= Html::encode($organNames[$bereich] ?? $bereich) ?>
                        </strong>

                    </td>

                </tr>


                <?php foreach ($spaceList as $space): ?>


                    <?php
                    /* gespeicherte Konfiguration laden */
                    $config = $configs[$space->id] ?? null;
                    ?>


                    <tr>


                        <!-- ==================================
                             Space Name + Öffnen Icon
                             ================================== -->

                        <td>

                            <strong>
                                <?= Html::encode($space->name) ?>
                            </strong>

                            <?= Html::a(
                                '<i class="fa-solid fa-arrow-up-right-from-square"></i>',
                                $space->getUrl(),
                                [
                                    'class' => 'text-muted ms-2',
                                    'title' => Yii::t('SociologModule.base', 'Space öffnen'),
                                    'target' => '_blank'
                                ]
                            ) ?>

                        </td>


                        <!-- ==================================
                             Organ auswählen
                             ================================== -->

                        <td>

						<select
							name="organ_id[<?= $space->id ?>]"
							class="form-control"
						>
						
						<option value="">
						<?= Yii::t('SociologModule.base', '– kein Organ –') ?>
						</option>
						
						<?php foreach ($organe as $organ): ?>
						
						<option
							value="<?= $organ->id ?>"
							<?= ($config && $config->organ_id == $organ->id) ? 'selected' : '' ?>
						>
						
						<?= Html::encode($organ->name) ?>
						
						</option>
						
						<?php endforeach; ?>
						
						</select>

						</td>
						
						<td class="text-center">
						
						<input
						type="checkbox"
						name="is_organ_space[<?= $space->id ?>]"
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
<?= ($config && $config->can_delete) ? 'checked' : '' ?>
>
						
						</td>
                        
 <td class="text-center">

<input
type="checkbox"
name="enabled[<?= $space->id ?>]"
<?= (!$config || (isset($config->enabled) && $config->enabled)) ? 'checked' : '' ?>
>

</td>

<td>

<select name="link_mode[<?= $space->id ?>]" class="form-control form-control-sm">

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
style="width:100%"
placeholder="<?= Yii::t('SociologModule.base','https://... (optional)') ?>"
>

</td>


                    </tr>


                <?php endforeach; ?>


            <?php endforeach; ?>


            </tbody>

        </table>


        <!-- ==================================================
             Speichern Button
             ================================================== -->

        <div class="form-group mt-3">

            <?= Html::submitButton(
                '<i class="fa-solid fa-floppy-disk me-1"></i> ' .
                Yii::t('SociologModule.base', 'Speichern'),
                ['class' => 'btn btn-primary']
            ) ?>

        </div>


    </div>

</div>


<?php
/* ------------------------------------------------------------
 * Formular beenden
 * ------------------------------------------------------------ */
ActiveForm::end();
?>
