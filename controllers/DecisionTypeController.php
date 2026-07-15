<?php

namespace humhub\modules\sociolog\controllers;

use Yii;
use humhub\components\Controller;
use humhub\modules\sociolog\models\DecisionType;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;

/**
 * ============================================================
 * 🔹 Controller: DecisionType
 * ------------------------------------------------------------
 * Verwaltung der Entscheid-Typen (z. B. Grundsatzentscheid, Richtlinie …)
 * - Verwaltung ausschliesslich durch Systemadministrator:innen
 * - Unterstützt Drag&Drop-Sortierung (AJAX)
 * ============================================================
 */
class DecisionTypeController extends Controller
{
    /**
     * 🔧 Verhalten (Methodenbeschränkung)
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['POST'],
                'sort'   => ['POST'],
            ],
        ];

        return $behaviors;
    }

    /**
     * Entscheid-Typen sind nur für angemeldete Benutzer erreichbar.
     */
    public function getAccessRules()
    {
        return [
            ['login'],
        ];
    }

    // ============================================================
    // 📋 INDEX – Übersicht aller Entscheid-Typen
    // ============================================================
    public function actionIndex()
    {
        $this->ensureAdmin();

        $types = DecisionType::find()
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'types' => $types,
        ]);
    }

    // ============================================================
    // ➕ CREATE – Neue Entscheidungsform
    // ============================================================
    public function actionCreate()
    {
        $this->ensureAdmin();

        $model = new DecisionType();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success',
                Yii::t('SociologModule.base', 'Der Entscheid-Typ wurde erfolgreich erstellt.')
            );
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    // ============================================================
    // ✏️ UPDATE – Bestehende Entscheidungsform bearbeiten
    // ============================================================
    public function actionUpdate($id)
    {
        $this->ensureAdmin();

        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success',
                Yii::t('SociologModule.base', 'Änderungen wurden gespeichert.')
            );
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    // ============================================================
    // 🗑️ DELETE – Entscheidungsform löschen
    // ============================================================
    public function actionDelete($id)
    {
        $this->ensureAdmin();

        $model = $this->findModel($id);
        $name = $model->name;
        $model->delete();

        Yii::$app->session->setFlash('info',
            Yii::t('SociologModule.base', 'Der Entscheid-Typ "{name}" wurde gelöscht.', ['name' => $name])
        );

        return $this->redirect(['index']);
    }

    // ============================================================
    // 🔀 SORT – Drag&Drop-Reihenfolge speichern (AJAX)
    // ============================================================
    public function actionSort()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->ensureAdmin();

        $ids = Yii::$app->request->post('ids', []);
        if (!is_array($ids)) {
            return ['success' => false, 'message' => Yii::t('SociologModule.base', 'Ungültige Daten.')];
        }

        foreach ($ids as $index => $id) {
            if (($model = DecisionType::findOne($id)) !== null) {
                $model->sort_order = $index + 1;
                $model->save(false, ['sort_order']);
            }
        }

        return ['success' => true];
    }

    /**
     * Entscheid-Typen sind globale Moduleinstellungen.
     */
    private function ensureAdmin(): void
    {
        if (!Yii::$app->user->isAdmin()) {
            throw new ForbiddenHttpException(
                Yii::t('SociologModule.base', 'Nur Administrator:innen dürfen Entscheid-Typen verwalten.')
            );
        }
    }

    // ============================================================
    // 🔎 Hilfsfunktion – Modell finden oder 404
    // ============================================================
    protected function findModel($id)
    {
        if (($model = DecisionType::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('SociologModule.base',
            'Der Entscheid-Typ wurde nicht gefunden.'));
    }
}
