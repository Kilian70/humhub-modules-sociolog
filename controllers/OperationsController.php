<?php

namespace humhub\modules\sociolog\controllers;

use humhub\components\Controller;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\sociolog\models\SpaceWriterForm;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class OperationsController extends Controller
{
    public function actionPermissions()
    {
        $this->requireLogbookOperations();

        $configs = SpaceConfig::find()
            ->with(['space', 'organ'])
            ->where(['enabled' => 1])
            ->orderBy(['organ_id' => SORT_ASC, 'space_id' => SORT_ASC])
            ->all();

        return $this->render('permissions', ['configs' => $configs]);
    }

    public function actionSpacePermission(int $id)
    {
        $this->requireLogbookOperations();

        $config = SpaceConfig::find()->with('space')->where(['space_id' => $id, 'enabled' => 1])->one();
        if (!$config || !$config->space) {
            throw new NotFoundHttpException(Yii::t('SociologModule.base', 'Der Space wurde nicht gefunden.'));
        }

        $model = new SpaceWriterForm($config);
        $model->loadConfig();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash(
                'success',
                Yii::t('SociologModule.base', 'Die Schreibberechtigungen wurden gespeichert.')
            );
            return $this->redirect(['permissions']);
        }

        return $this->render('space_permission', [
            'model' => $model,
            'space' => $config->space,
        ]);
    }

    private function requireLogbookOperations(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user || (!Yii::$app->user->isAdmin() && !Entry::isLogbookManager($user))) {
            throw new ForbiddenHttpException(Yii::t('SociologModule.base', 'Nur der Betrieb des Logbuches darf diese Berechtigungen verwalten.'));
        }
    }
}
