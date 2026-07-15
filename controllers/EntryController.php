<?php


namespace humhub\modules\sociolog\controllers;

use Yii;
use humhub\components\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\EntrySearch;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\sociolog\models\EntryFlow;
use humhub\modules\sociolog\models\Protocol;

/**
 * ============================================================
 * 🔹 EntryController – Sociolog (Logbuch)
 * ------------------------------------------------------------
 * Verantwortlich für:
 * - Navigation & Flow
 * - Berechtigungsprüfungen
 * - Redirects & Flash-Messages
 *
 * Stream- & Kalenderlogik:
 * 👉 ausschliesslich im Model (afterSave / afterDelete)
 * ============================================================
 */
class EntryController extends Controller
{
    // ============================================================
    // 🔐 Zugriff & HTTP-Methoden
    // ============================================================
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'delete'     => ['POST'],
                'forward'    => ['POST'],
                'take-over'  => ['POST'],
                'decide'     => ['POST'],
                'return'     => ['POST'],
                'review'     => ['POST'],
                'export-csv' => ['GET'],
            ],
        ];

        return $behaviors;
    }

    /**
     * HumHub-Zugriffsregel für den globalen Controller.
     */
    public function getAccessRules()
    {
        return [
            ['login'],
        ];
    }

    // ============================================================
    // 📋 INDEX – Übersicht
    // ============================================================
   public function actionIndex()
{
    $module = Yii::$app->getModule('sociolog');
    $moduleTitle = $module && $module->settings->get('moduleTitle')
        ? $module->settings->get('moduleTitle')
        : Yii::t('SociologModule.base', 'Logbuch');

    $this->view->title = $moduleTitle . ' – ' . Yii::t('SociologModule.base', 'Einträge');

    $searchModel = new EntrySearch();

    $configs = SpaceConfig::find()
    ->where(['enabled' => 1])
    ->indexBy('space_id')
    ->all();

    $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

    return $this->render('index', [
        'searchModel'  => $searchModel,
        'dataProvider' => $dataProvider,
        'configs'      => $configs,
    ]);
}

    // ============================================================
    // 👁️ EINZELANSICHT
    // ============================================================
    public function actionView($id)
    {
        $model = Entry::find()
            ->with(['decisionType'])
            ->where(['id' => $id])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException(
                Yii::t('SociologModule.base', 'Der angeforderte Eintrag wurde nicht gefunden.')
            );
        }

        return $this->render('view', [
            'model'    => $model,
            'viewMode' => Yii::$app->request->get('view', 'cards'),
        ]);
    }

// ============================================================
// ➕ ERSTELLEN
// ============================================================
public function actionCreate()
{
    $model = new Entry();

    $user = Yii::$app->user->identity;
    $allowed = Entry::canCreateGlobal($user);

    if (!$allowed) {
        Yii::$app->session->setFlash('warning',
            Yii::t('SociologModule.base', 'Du hast keine Berechtigung, neue Einträge zu erstellen.')
        );
        return $this->redirect(['index']);
    }

    if ($model->load(Yii::$app->request->post())) {

        // Das Dropdown ist nur eine UI-Hilfe. Die Berechtigung muss
        // für das tatsächlich übermittelte Organ erneut geprüft werden.
        if (!Entry::canCreateGlobal($user, (int)$model->organ)) {
            throw new \yii\web\ForbiddenHttpException(
                Yii::t('SociologModule.base', 'Du hast für diesen Space keine Schreibberechtigung.')
            );
        }

        $space = \humhub\modules\space\models\Space::findOne((int)$model->organ);

        if (!$space) {
            Yii::$app->session->setFlash('danger',
                Yii::t('SociologModule.base', 'Kein passender Space gefunden.')
            );
            return $this->redirect(['index']);
        }

        $model->setContentContainer($space);
        $model->current_organ = $space->id;

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!$model->save()) {
                throw new \RuntimeException('Entry validation failed.');
            }

            $this->replaceProtocols($model->id);
            $transaction->commit();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }

            Yii::warning([
                'message' => 'Sociolog-Eintrag konnte nicht erstellt werden.',
                'errors' => $model->getErrors(),
                'userId' => $user->id ?? null,
                'exception' => $e->getMessage(),
            ], 'sociolog.entry');

            if (!$model->hasErrors()) {
                $model->addError(
                    'title',
                    Yii::t('SociologModule.base', 'Die Protokoll-Links konnten nicht gespeichert werden.')
                );
            }

            Yii::$app->session->setFlash(
                'danger',
                Yii::t(
                    'SociologModule.base',
                    'Der Eintrag konnte nicht gespeichert werden. Bitte prüfe die markierten Felder.'
                )
            );

            return $this->render('create', [
                'model' => $model,
            ]);
        }

        Yii::$app->session->setFlash('success',
            Yii::t('SociologModule.base', 'Eintrag wurde erfolgreich erstellt.')
        );

        return $this->redirect(['view', 'id' => $model->id]);
    }

    return $this->render('create', [
        'model' => $model
    ]);
}

// ============================================================
// ✏️ BEARBEITEN
// ============================================================
public function actionUpdate($id)
{
    $model = $this->findModel($id);
    $user = Yii::$app->user->identity;
    $originalOrgan = (int)$model->organ;
    $originView = Yii::$app->request->get('view', 'cards');

    $this->view->params['backUrl'] = Yii::$app->request->referrer;

    if (!$model->canWrite($user)) {
        Yii::$app->session->setFlash('warning',
            Yii::t('SociologModule.base', 'Du hast keine Berechtigung, diesen Eintrag zu bearbeiten.')
        );
        return $this->redirect(['index', 'view' => $originView]);
    }

    if ($model->load(Yii::$app->request->post())) {
        $targetOrgan = (int)$model->organ;

        // Für einen Organwechsel muss auch im neuen Ziel-Space ein
        // Erstellrecht bestehen. Ein manipuliertes POST-Feld darf keinen
        // Eintrag in einen fremden Space verschieben.
        if ($targetOrgan !== $originalOrgan && !Entry::canCreateGlobal($user, $targetOrgan)) {
            throw new \yii\web\ForbiddenHttpException(
                Yii::t('SociologModule.base', 'Du hast für diesen Space keine Schreibberechtigung.')
            );
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!$model->save()) {
                throw new \RuntimeException('Entry validation failed.');
            }

            $this->replaceProtocols($model->id);
            $transaction->commit();

            Yii::$app->session->setFlash('success',
                Yii::t('SociologModule.base', 'Änderungen wurden gespeichert.')
            );

            return $this->redirect(['view', 'id' => $model->id, 'view' => $originView]);
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }

            Yii::warning([
                'message' => 'Sociolog-Eintrag konnte nicht aktualisiert werden.',
                'entryId' => $model->id,
                'errors' => $model->getErrors(),
                'exception' => $e->getMessage(),
            ], 'sociolog.entry');

            if (!$model->hasErrors()) {
                $model->addError(
                    'title',
                    Yii::t('SociologModule.base', 'Die Protokoll-Links konnten nicht gespeichert werden.')
                );
            }

            Yii::$app->session->setFlash(
                'danger',
                Yii::t(
                    'SociologModule.base',
                    'Der Eintrag konnte nicht gespeichert werden. Bitte prüfe die markierten Felder.'
                )
            );
        }
    }

    return $this->render('update', ['model' => $model]);
}

// ============================================================
// 🗑️ LÖSCHEN
// ============================================================
public function actionDelete($id)
{
    $model = $this->findModel($id);
    $originView = Yii::$app->request->get('view', 'cards');

    if (!$model->canDelete(Yii::$app->user->identity)) {
        Yii::$app->session->setFlash('warning',
            Yii::t('SociologModule.base', 'Du hast keine Berechtigung, diesen Eintrag zu löschen.')
        );
        return $this->redirect(['index', 'view' => $originView]);
    }

    try {
        // Explizite Bereinigung ist für bestehende Sociolog-Installationen
        // erforderlich, deren Content-Datensätze historisch erzeugt wurden.
        \humhub\modules\sociolog\services\SociologStreamService::onAfterDelete($model);
        \humhub\modules\sociolog\services\SociologCalendarService::deleteByEntryId($model->id);
        Entry::deleteAll(['id' => $model->id]);

        if (Entry::findOne($model->id) !== null) {
            throw new \RuntimeException('Entry could not be deleted.');
        }

        Yii::$app->session->setFlash('success',
            Yii::t('SociologModule.base', 'Eintrag wurde erfolgreich gelöscht.')
        );

    } catch (\Throwable $e) {

        Yii::error("Sociolog: Fehler beim Löschen von Entry #{$id}: {$e->getMessage()}", 'sociolog');

        Yii::$app->session->setFlash('danger',
            Yii::t('SociologModule.base', 'Fehler beim Löschen des Eintrags.')
        );
    }

    return $this->redirect(['index', 'view' => $originView]);
}   

// ============================================================
// 🔁 WEITERLEITEN (BG → BK → LK)
// ============================================================
public function actionForward($id)
{
    $model = $this->findModel($id);

    if (!$model->canWrite(Yii::$app->user->identity)) {
        throw new \yii\web\ForbiddenHttpException(
            Yii::t('SociologModule.base', 'Du hast keine Berechtigung.')
        );
    }

    $currentOrgan = (int)$model->current_organ;

    if (!$currentOrgan) {
        Yii::$app->session->setFlash(
            'warning',
            Yii::t('SociologModule.base', 'Kein aktuelles Organ vorhanden.')
        );

        return $this->redirect(['view', 'id' => $model->id]);
    }

    $nextSpaceId = $model->getNextOrgan();

    if (!$nextSpaceId) {
        Yii::$app->session->setFlash(
            'warning',
            Yii::t('SociologModule.base', 'Kein weiteres Organ vorhanden.')
        );

        return $this->redirect(['view', 'id' => $model->id]);
    }

    $nextSpace = \humhub\modules\space\models\Space::findOne($nextSpaceId);

    if (!$nextSpace) {
        Yii::$app->session->setFlash(
            'warning',
            Yii::t('SociologModule.base', 'Zielorgan konnte nicht gefunden werden.')
        );

        return $this->redirect(['view', 'id' => $model->id]);
    }

    // --------------------------------------------------------
    // Entscheid wieder öffnen (Logik im Model)
    // --------------------------------------------------------
    $model->reopenDecision();

    // --------------------------------------------------------
    // Weiterleitung setzen
    // --------------------------------------------------------
    $model->current_organ = $nextSpace->id;
    $model->forwarded_to  = $nextSpace->name;
    $model->forwarded_at  = date('Y-m-d');

    $this->saveWorkflowStep(
        $model,
        $model->id,
        $currentOrgan,
        $nextSpace->id,
        'forward'
    );

    Yii::$app->session->setFlash(
        'success',
        Yii::t('SociologModule.base', 'Eintrag wurde weitergeleitet an {organ}.', [
            'organ' => $nextSpace->name
        ])
    );

    return $this->redirect(['view', 'id' => $model->id]);
}

// ============================================================
// ✅ ÜBERNEHMEN
// ============================================================
public function actionTakeOver($id)
{
    $model = $this->findModel($id);

    if (!$model->canWrite(Yii::$app->user->identity)) {
        throw new \yii\web\ForbiddenHttpException(
            Yii::t('SociologModule.base', 'Du hast keine Berechtigung.')
        );
    }

    if (!$model->forwarded_to) {
        throw new \yii\web\ForbiddenHttpException();
    }

    $space = \humhub\modules\space\models\Space::find()
        ->where(['name' => $model->forwarded_to])
        ->one();

    if (!$space) {
        Yii::$app->session->setFlash(
            'warning',
            Yii::t('SociologModule.base', 'Zielorgan konnte nicht gefunden werden.')
        );

        return $this->redirect(['view', 'id' => $model->id]);
    }

    $fromOrgan = $model->current_organ;

    // --------------------------------------------------------
    // ➡️ Organ übernehmen
    // --------------------------------------------------------
    $model->current_organ = $space->id;
    $model->forwarded_to = null;
    $model->forwarded_at = null;

    $this->saveWorkflowStep(
        $model,
        $model->id,
        $fromOrgan,
        $space->id,
        'takeover'
    );

    Yii::$app->session->setFlash(
        'success',
        Yii::t('SociologModule.base', 'Der Entscheid wurde übernommen.')
    );

    return $this->redirect(['view', 'id' => $model->id]);
}

// ============================================================
// ✔ ENTSCHEID FASSEN
// ============================================================
public function actionDecide($id)
{
    $model = $this->findModel($id);

    if (!$model->canWrite(Yii::$app->user->identity)) {
        throw new \yii\web\ForbiddenHttpException(
            Yii::t('SociologModule.base', 'Du hast keine Berechtigung.')
        );
    }

    // --------------------------------------------------------
    // Entscheid im Model setzen
    // --------------------------------------------------------
    $model->makeDecision();

    $this->saveWorkflowStep(
        $model,
        $model->id,
        $model->current_organ,
        $model->current_organ,
        'decision'
    );

    Yii::$app->session->setFlash(
        'success',
        Yii::t('SociologModule.base', 'Der Beschluss wurde gefasst.')
    );

    return $this->redirect(['view', 'id' => $model->id]);
}

// ============================================================
// ↩ ENTSCHEID ZURÜCKGEBEN
// ============================================================
public function actionReturn($id)
{
    $model = $this->findModel($id);

    if (!$model->canWrite(Yii::$app->user->identity)) {
        throw new \yii\web\ForbiddenHttpException(
            Yii::t('SociologModule.base', 'Du hast keine Berechtigung.')
        );
    }

    if (!$model->current_organ) {
        Yii::$app->session->setFlash(
            'warning',
            Yii::t('SociologModule.base', 'Kein aktuelles Organ vorhanden.')
        );

        return $this->redirect(['view', 'id' => $model->id]);
    }

    // --------------------------------------------------------
    // letzten Forward-Schritt zum aktuellen Organ finden
    // --------------------------------------------------------
    $lastForward = EntryFlow::find()
        ->where([
            'entry_id' => $model->id,
            'action' => 'forward',
            'to_organ_id' => $model->current_organ
        ])
        ->orderBy(['id' => SORT_DESC])
        ->one();

    if (!$lastForward) {
        Yii::$app->session->setFlash(
            'warning',
            Yii::t('SociologModule.base', 'Kein vorheriges Organ gefunden.')
        );

        return $this->redirect(['view', 'id' => $model->id]);
    }

    // Schutz gegen Endlosschleifen
    if ($lastForward->from_organ_id == $model->current_organ) {
        return $this->redirect(['view', 'id' => $model->id]);
    }

    $fromOrgan = (int)$model->current_organ;
    $toOrgan   = (int)$lastForward->from_organ_id;

    // --------------------------------------------------------
    // Entscheid wieder öffnen (Logik im Model)
    // --------------------------------------------------------
    $model->reopenDecision();

    // --------------------------------------------------------
    // Organ zurücksetzen
    // --------------------------------------------------------
    $model->current_organ = $toOrgan;
    $model->forwarded_to  = null;
    $model->forwarded_at  = null;

    $this->saveWorkflowStep(
        $model,
        $model->id,
        $fromOrgan,
        $toOrgan,
        'return'
    );

    Yii::$app->session->setFlash(
        'success',
        Yii::t('SociologModule.base', 'Der Entscheid wurde zurückgegeben.')
    );

    return $this->redirect(['view', 'id' => $model->id]);
}

// ============================================================
// 🔍 ÜBERPRÜFUNG
// ============================================================
public function actionReview($id)
{
    $model = $this->findModel($id);

    if (!$model->canWrite(Yii::$app->user->identity)) {
        throw new \yii\web\ForbiddenHttpException(
            Yii::t('SociologModule.base', 'Du hast keine Berechtigung.')
        );
    }

    if (!EntryFlow::log(
        $model->id,
        $model->current_organ,
        $model->current_organ,
        'review'
    )) {
        throw new \yii\web\ServerErrorHttpException(
            Yii::t('SociologModule.base', 'Der Verlaufsschritt konnte nicht gespeichert werden.')
        );
    }

    Yii::$app->session->setFlash(
        'success',
        Yii::t('SociologModule.base', 'Überprüfung wurde dokumentiert.')
    );

    return $this->redirect(['view', 'id' => $model->id]);
}

    // ============================================================
    // 🔍 HILFSMETHODE
    // ============================================================
    protected function findModel($id): Entry
    {
        $model = Entry::findOne(['id' => $id]);

        if (!$model) {
            throw new NotFoundHttpException(
                Yii::t('SociologModule.base', 'Der angeforderte Eintrag wurde nicht gefunden.')
            );
        }

        return $model;
    }

    /**
     * Ersetzt alle Protokoll-Links eines Eintrags innerhalb der aktiven Transaktion.
     *
     * @throws \RuntimeException wenn ein Link nicht gespeichert werden kann
     */
    private function replaceProtocols(int $entryId): void
    {
        Protocol::deleteAll(['entry_id' => $entryId]);

        $titles = Yii::$app->request->post('protocol_title', []);
        $urls = Yii::$app->request->post('protocol_url', []);

        foreach ($titles as $index => $rawTitle) {
            $title = trim((string)$rawTitle);
            $url = trim((string)($urls[$index] ?? ''));

            if ($title === '' && $url === '') {
                continue;
            }

            $protocol = new Protocol([
                'entry_id' => $entryId,
                'title' => $title,
                'url' => $url,
            ]);

            if (!$protocol->save()) {
                throw new \RuntimeException(json_encode($protocol->getErrors()));
            }
        }
    }

    /**
     * Speichert Zustandsänderung und Verlauf atomar.
     */
    private function saveWorkflowStep(
        Entry $model,
        int $entryId,
        ?int $fromOrgan,
        ?int $toOrgan,
        string $action
    ): void {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Die Workflow-Methoden setzen ausschliesslich interne Felder.
            // Ein offener/weitergeleiteter Entscheid hat bewusst noch kein
            // decision_date und kann daher nicht die Formularregeln erfüllen.
            if (!$model->save(false)) {
                throw new \RuntimeException('Workflow state could not be saved.');
            }

            if (!EntryFlow::log($entryId, $fromOrgan, $toOrgan, $action)) {
                throw new \RuntimeException('Workflow log could not be saved.');
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }

            Yii::error([
                'message' => 'Sociolog-Workflow konnte nicht gespeichert werden.',
                'entryId' => $entryId,
                'action' => $action,
                'exception' => $e->getMessage(),
            ], 'sociolog.workflow');

            throw new \yii\web\ServerErrorHttpException(
                Yii::t('SociologModule.base', 'Der Workflow konnte nicht gespeichert werden.')
            );
        }
    }

    /**
     * Verhindert, dass Tabellenprogramme CSV-Inhalte als Formeln ausführen.
     */
    private static function csvCell($value): string
    {
        $value = (string)($value ?? '');

        if (preg_match('/^[=+\-@\t\r]/u', $value)) {
            return "'" . $value;
        }

        return $value;
    }

    // ============================================================
    // 📤 CSV-EXPORT (NULL-safe)
    // ============================================================
    public function actionExportCsv()
    {
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_RAW;

        $filename = 'logbuch_' . date('Y-m-d_H-i-s') . '.csv';

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF");

        fputcsv($fh, [
            'Titel',
            'Entscheid-Typ',
            'Organ',
            'Themenhüter:in',
            'Beschluss',
            'Begründung',
            'Beschlussdatum',
            'Inkrafttreten',
            'Überprüfung ab',
            'Status',
            'Protokoll-Links',
            'Erstellt von',
            'Erstellt am',
            'Bearbeitet von',
            'Bearbeitet am',
        ], ';');

        $query = Entry::find()
            ->with(['decisionType', 'protocols', 'creator', 'editor'])
            ->orderBy(['decision_date' => SORT_DESC]);

        foreach ($query->each(100) as $entry) {
            $protocolLinks = [];
            foreach ($entry->protocols as $protocol) {
                if ($protocol->safeUrl !== null) {
                    $protocolLinks[] = trim((string)$protocol->title) . ': ' . $protocol->safeUrl;
                }
            }

            $row = [
                $entry->title ?: '',
                $entry->decisionType->name ?? '',
                $entry->organName ?? '',
                $entry->topic_owner ?? '',
                strip_tags($entry->decision ?? ''),
                strip_tags($entry->description ?? ''),
                $entry->decision_date ?? '',
                $entry->effective_date ?? '',
                $entry->review_date ?? '',
                strip_tags($entry->getStatusBadge()),
                implode("\n", $protocolLinks),
                $entry->creator->displayName ?? '',
                $entry->created_at ? date('d.m.Y H:i', $entry->created_at) : '',
                $entry->editor->displayName ?? '',
                $entry->updated_at ? date('d.m.Y H:i', $entry->updated_at) : '',
            ];

            fputcsv($fh, array_map([self::class, 'csvCell'], $row), ';');
        }

        rewind($fh);
        $response->content = stream_get_contents($fh);
        fclose($fh);

        return $response;
        
    }
}
