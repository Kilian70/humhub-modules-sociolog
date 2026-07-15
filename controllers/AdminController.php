<?php

namespace humhub\modules\sociolog\controllers;

use Yii;
use humhub\modules\admin\components\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\helpers\Html;
use yii\filters\VerbFilter;

use humhub\modules\sociolog\models\SettingsForm;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\sociolog\models\Organ;
use humhub\modules\space\models\Space;
use humhub\modules\sociolog\services\SociologStatusService;

use humhub\modules\user\models\User;

/**
 * ============================================================
 * 🔹 AdminController – Verwaltung des Sociolog-Moduls
 * ------------------------------------------------------------
 * Verantwortlich für:
 * - Modul-Einstellungen
 * - Benutzer:innen-Rechteprüfung (AJAX)
 * - Manuelles Auslösen der automatischen Statusprüfung
 * ============================================================
 */
class AdminController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'run-status-check' => ['POST'],
                'check-user' => ['POST'],
                'delete-organ' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    /**
     * ============================================================
     * ⚙️ Modul-Einstellungen
     * ------------------------------------------------------------
     * - Anzeige und Speicherung der Modul-Konfiguration
     * - Nur für Administrator:innen
     * ============================================================
     */
public function actionIndex()
{
    if (!Yii::$app->user->isAdmin()) {
        throw new ForbiddenHttpException(
            'Nur Administrator:innen dürfen diese Einstellungen anpassen.'
        );
    }

    $this->view->title = 'Sociolog – Einstellungen';

    $model = new SettingsForm();

    // bestehende Werte laden
    $model->loadSettings();

    // POST speichern
    if ($model->load(Yii::$app->request->post())) {
        if ($model->save()) {
            Yii::$app->session->setFlash(
                'success',
                '<i class="fa-solid fa-circle-check me-1"></i> Einstellungen wurden gespeichert.'
            );

            return $this->redirect(['index']);
        }

        Yii::$app->session->setFlash(
            'danger',
            Yii::t('SociologModule.base', 'Die Einstellungen konnten nicht gespeichert werden. Bitte prüfe die markierten Felder.')
        );
    }

    // 🔹 Globale Spaces laden
    $globaleSpaces = \humhub\modules\space\models\Space::find()
        ->alias('s')
        ->innerJoin(SpaceConfig::tableName() . ' cfg', 'cfg.space_id = s.id')
        ->where(['cfg.global_write' => 1])
        ->orderBy(['s.name' => SORT_ASC])
        ->all();

    return $this->render('index', [
        'model' => $model,
        'globaleSpaces' => $globaleSpaces,
    ]);
}

    /**
     * ============================================================
     * 🔄 Manuelle Statusprüfung (Admin-Button)
     * ------------------------------------------------------------
     * - Löst dieselbe Logik aus wie der Cron (run.php)
     * - Enthält KEINE eigene Fachlogik
     * - Dient ausschliesslich zu Debug- und Kontrollzwecken
     * ============================================================
     */
    public function actionRunStatusCheck()
    {
        // 🔒 Zugriff nur für Administrator:innen
        if (!Yii::$app->user->isAdmin()) {
            throw new ForbiddenHttpException(
                'Nur Administrator:innen dürfen diese Aktion ausführen.'
            );
        }

        // 👉 EINZIGER Fachaufruf
        // Gleiche Logik wie im Cron (kein Duplikat!)
        SociologStatusService::run();

        Yii::$app->session->setFlash(
            'success',
            '<i class="fa-solid fa-circle-check me-1"></i> Statusprüfung wurde erfolgreich ausgeführt.'
        );

        // Zurück zur Admin-Seite
        return $this->redirect(['index']);
    }

    /**
     * ============================================================
     * 🔍 AJAX-Aktion: Rechteprüfung für ausgewählte Benutzer:innen
     * ------------------------------------------------------------
     * - Wird im Adminbereich per AJAX aufgerufen
     * - Zeigt Schreib- und Löschrechte an
     * ============================================================
     */
    public function actionCheckUser()
    {
        Yii::$app->response->format = Response::FORMAT_HTML;

        // 🔒 Zugriff nur für Administrator:innen
        if (!Yii::$app->user->isAdmin()) {
            return '<div class="text-danger">Keine Berechtigung.</div>';
        }

        $userId = Yii::$app->request->post('user_id');
        $user   = User::findOne($userId);

        if (!$user) {
            return '<div class="text-danger">Benutzer:in nicht gefunden.</div>';
        }

        $writable  = Entry::getWritableOrgansForUser($user);
        $canDelete = (new Entry())->canDelete($user);
        $groups    = array_map(fn($g) => $g->name, $user->groups ?? []);

        ob_start();
        ?>
        <div>
            <strong><?= Html::encode($user->displayName) ?></strong><br>
            <small class="text-muted">
                Gruppen:
                <?= $groups
                    ? Html::encode(implode(', ', $groups))
                    : 'Keine Gruppen'
                ?>
            </small>
        </div>

        <div class="mt-2">
            <i class="fa-solid fa-pen-to-square text-primary me-1"></i>
            Schreibrechte:
            <?php if (!empty($writable)): ?>
                <span class="badge bg-success">
                    <?= implode(
                        '</span> <span class="badge bg-success">',
                        array_map('htmlspecialchars', $writable)
                    ) ?>
                </span>
            <?php else: ?>
                <span class="text-muted">Keine Schreibrechte erkannt</span>
            <?php endif; ?>
        </div>

        <div class="mt-1">
            <i class="fa-solid fa-trash-can text-danger me-1"></i>
            Löschrecht:
            <?= $canDelete
                ? '<span class="badge bg-danger">Ja</span>'
                : '<span class="badge bg-secondary">Nein</span>'
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
/**
 * ============================================================
 * 🔹 Spaces & Bereiche verwalten
 * ------------------------------------------------------------
 * Funktionen:
 * - ordnet Spaces einem Logbuch-Bereich zu
 * - definiert globale Schreibrechte
 * - definiert Löschenhrechte
 *
 * Tabelle:
 * sociolog_space_config
 * ============================================================
 */
public function actionSpaces()
{
    /* --------------------------------------------------------
     * Zugriff nur für Administrator:innen
     * -------------------------------------------------------- */

    if (!Yii::$app->user->isAdmin()) {
        throw new ForbiddenHttpException(
            'Nur Administrator:innen dürfen diese Einstellungen anpassen.'
        );
    }


    /* --------------------------------------------------------
     * Seitentitel
     * -------------------------------------------------------- */

    $this->view->title = 'Sociolog – Spaces & Bereiche';


/* --------------------------------------------------------
 * POST: Einstellungen speichern
 * -------------------------------------------------------- */

if (Yii::$app->request->isPost) {

		$organs      = Yii::$app->request->post('organ_id', []);
		$globalWrite = Yii::$app->request->post('global_write', []);
		$canDelete   = Yii::$app->request->post('can_delete', []);
		$enabled     = Yii::$app->request->post('enabled', []);
		$linkMode    = Yii::$app->request->post('link_mode', []);
		$link        = Yii::$app->request->post('link', []);
		$isOrganSpace = Yii::$app->request->post('is_organ_space', []);

    $spaces = Space::find()
        ->orderBy(['name' => SORT_ASC])
        ->all();

    $transaction = Yii::$app->db->beginTransaction();

    try {
    foreach ($spaces as $space) {

        $spaceId = (int)$space->id;
        $organId = $organs[$spaceId] ?? null;

        $config = SpaceConfig::findOne([
            'space_id' => $spaceId
        ]);

        $global = isset($globalWrite[$spaceId]);
        $delete = isset($canDelete[$spaceId]);
        $show   = isset($enabled[$spaceId]);
        $mode   = $linkMode[$spaceId] ?? 'about';
        $url    = trim($link[$spaceId] ?? '');

        if (empty($organId) && !$global && !$delete && !$show && $mode === 'about' && $url === '') {

            if ($config) {
                if ($config->delete() === false) {
                    throw new \RuntimeException('Space configuration could not be deleted.');
                }
            }

            continue;
        }

        if (!$config) {
            $config = new SpaceConfig();
            $config->space_id = $spaceId;
        }

        $config->organ_id     = $organId ?: null;
		$config->global_write = $global;
		$config->can_delete   = $delete;
		$config->enabled      = $show;
		$config->link_mode    = $mode;
		$config->link         = ($mode === 'custom') ? $url : null;
		
		$config->is_organ_space = isset($isOrganSpace[$spaceId]) ? 1 : 0;

        if (!$config->save()) {
            throw new \RuntimeException(json_encode($config->getErrors()));
        }
    }

    $transaction->commit();
    } catch (\Throwable $e) {
        if ($transaction->isActive) {
            $transaction->rollBack();
        }

        Yii::error([
            'message' => 'Sociolog Space-Konfiguration konnte nicht gespeichert werden.',
            'exception' => $e->getMessage(),
        ], 'sociolog.settings');

        Yii::$app->session->setFlash(
            'danger',
            Yii::t('SociologModule.base', 'Die Space-Konfiguration konnte nicht gespeichert werden. Bitte prüfe die Eingaben.')
        );

        return $this->redirect(['spaces']);
    }

    Yii::$app->session->setFlash(
        'success',
        '<i class="fa-solid fa-circle-check me-1"></i> Einstellungen wurden gespeichert.'
    );

    return $this->redirect(['spaces']);
}
/* --------------------------------------------------------
 * Alle Spaces laden
 * -------------------------------------------------------- */

$spaces = Space::find()
    ->orderBy(['name' => SORT_ASC])
    ->all();


/* --------------------------------------------------------
 * gespeicherte Konfiguration laden
 * -------------------------------------------------------- */

$configs = SpaceConfig::find()
    ->indexBy('space_id')
    ->all();


/* --------------------------------------------------------
 * Organe laden
 * -------------------------------------------------------- */

$organe = Organ::find()
    ->orderBy(['sort_order' => SORT_ASC])
    ->all();


/* --------------------------------------------------------
 * View anzeigen
 * -------------------------------------------------------- */

return $this->render('spaces', [
    'spaces'  => $spaces,
    'configs' => $configs,
    'organe'  => $organe,
]);

}

/* --------------------------------------------------------
 * Organe
 * -------------------------------------------------------- */

public function actionOrgans()
{
    $organs = Organ::find()
        ->orderBy([
            'sort_order' => SORT_ASC,
            'name' => SORT_ASC
        ])
        ->all();

    return $this->render('organs', [
        'organs' => $organs
    ]);
}


/* --------------------------------------------------------
 * Organ erstellen
 * -------------------------------------------------------- */

public function actionCreateOrgan()
{
    $model = new Organ();

    if ($model->load(Yii::$app->request->post()) && $model->save()) {
        return $this->redirect(['organs']);
    }

    return $this->render('organ_form', [
        'model' => $model
    ]);
}


/* --------------------------------------------------------
 * Organ bearbeiten
 * -------------------------------------------------------- */

public function actionUpdateOrgan($id)
{
    $model = Organ::findOne($id);

    if (!$model) {
        throw new \yii\web\NotFoundHttpException('Organ nicht gefunden.');
    }

    if ($model->load(Yii::$app->request->post()) && $model->save()) {
        return $this->redirect(['organs']);
    }

    return $this->render('organ_form', [
        'model' => $model
    ]);
}


/* --------------------------------------------------------
 * Organ löschen
 * -------------------------------------------------------- */

public function actionDeleteOrgan($id)
{
    $model = Organ::findOne($id);

    if ($model) {
        $model->delete();
    }

    return $this->redirect(['organs']);
}
}
