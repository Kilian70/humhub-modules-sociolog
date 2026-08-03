<?php

namespace humhub\modules\sociolog\controllers;

use Yii;
use humhub\modules\admin\components\Controller;
use humhub\modules\sociolog\models\ImportUploadForm;
use humhub\modules\sociolog\services\SociologImportService;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class ImportController extends Controller
{
    private const PREVIEW_MAX_AGE = 86400;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'run' => ['POST'],
            ],
        ];
        return $behaviors;
    }

    public function actionIndex()
    {
        $this->requireAdmin();
        $this->cleanupExpiredPreviews();

        $model = new ImportUploadForm();
        $preview = null;
        $token = null;

        if ($model->load(Yii::$app->request->post())) {
            $model->file = UploadedFile::getInstance($model, 'file');

            if ($model->validate()) {
                try {
                    $preview = SociologImportService::preview($model->file->tempName);
                    $this->removeCurrentUserPreview();
                    $token = bin2hex(random_bytes(24));
                    $path = $this->getPreviewPath($token);
                    $directory = dirname($path);

                    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                        throw new \RuntimeException('Import preview directory could not be created.');
                    }

                    if (file_put_contents(
                        $path,
                        json_encode($preview, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        LOCK_EX
                    ) === false) {
                        throw new \RuntimeException('Import preview could not be stored.');
                    }
                    chmod($path, 0600);
                    Yii::$app->session->set('sociologImportToken', $token);
                } catch (\Throwable $e) {
                    Yii::error($e, 'sociolog.import');
                    $model->addError(
                        'file',
                        Yii::t('SociologModule.base', 'Die Importdatei konnte nicht geprüft werden.')
                    );
                }
            }
        }

        return $this->render('index', [
            'model' => $model,
            'preview' => $preview,
            'token' => $token,
        ]);
    }

    public function actionTemplate()
    {
        $this->requireAdmin();

        $rows = [
            [
                'source_sheet',
                'source_row',
                'target_organ',
                'decision_type',
                'title',
                'decision',
                'decision_date',
                'review_date',
            ],
            [
                'Altes Logbuch',
                '1',
                'Hausverein',
                'Grundsatzentscheid',
                'Beispielentscheid',
                'Hier steht der vollständige Wortlaut des Entscheids.',
                '2024-01-31',
                '2026-01-31',
            ],
        ];

        $handle = fopen('php://temp', 'w+b');
        if ($handle === false) {
            throw new \RuntimeException('CSV template could not be created.');
        }

        try {
            // UTF-8 BOM improves compatibility with spreadsheet applications.
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($handle, $row, ',', '"', '\\');
            }
            rewind($handle);
            $content = stream_get_contents($handle);
        } finally {
            fclose($handle);
        }

        return Yii::$app->response->sendContentAsFile(
            (string)$content,
            'sociolog-import-vorlage.csv',
            ['mimeType' => 'text/csv']
        );
    }

    public function actionRun()
    {
        $this->requireAdmin();
        $this->cleanupExpiredPreviews();

        $token = (string)Yii::$app->request->post('token');
        $sessionToken = (string)Yii::$app->session->get('sociologImportToken', '');

        if ($token === '' || !hash_equals($sessionToken, $token)) {
            throw new ForbiddenHttpException(
                Yii::t('SociologModule.base', 'Die Importbestätigung ist ungültig oder abgelaufen.')
            );
        }

        $path = $this->getPreviewPath($token);
        if (!is_file($path)) {
            throw new NotFoundHttpException(
                Yii::t('SociologModule.base', 'Die Importvorschau wurde nicht gefunden.')
            );
        }

        try {
            $preview = json_decode(
                (string)file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            $result = SociologImportService::import($preview);
            @unlink($path);
            Yii::$app->session->remove('sociologImportToken');

            Yii::$app->session->setFlash(
                'success',
                Yii::t(
                    'SociologModule.base',
                    '{count} historische Einträge wurden importiert. {duplicates} Duplikate wurden übersprungen.',
                    [
                        'count' => $result['imported'],
                        'duplicates' => $result['duplicates'],
                    ]
                )
            );

            return $this->redirect(['/sociolog/entry/index']);
        } catch (\Throwable $e) {
            Yii::error($e, 'sociolog.import');
            Yii::$app->session->setFlash(
                'danger',
                Yii::t('SociologModule.base', 'Der Import wurde vollständig abgebrochen. Es wurden keine unvollständigen Daten übernommen.')
            );
            return $this->redirect(['index']);
        }
    }

    private function requireAdmin(): void
    {
        if (!Yii::$app->user->isAdmin()) {
            throw new ForbiddenHttpException(
                Yii::t('SociologModule.base', 'Nur Administrator:innen dürfen historische Einträge importieren.')
            );
        }
    }

    private function getPreviewPath(string $token): string
    {
        $userId = (int)Yii::$app->user->id;
        return Yii::getAlias('@runtime/sociolog-import/')
            . $userId . '-' . $token . '.json';
    }

    /**
     * Entfernt die vorherige Vorschau dieses Administrators, sobald eine neue
     * erstellt wird. Andere aktive Sitzungen bleiben davon unberuehrt.
     */
    private function removeCurrentUserPreview(): void
    {
        $oldToken = (string)Yii::$app->session->get('sociologImportToken', '');
        if ($oldToken === '' || !preg_match('/^[a-f0-9]{48}$/', $oldToken)) {
            return;
        }

        $oldPath = $this->getPreviewPath($oldToken);
        if (is_file($oldPath) && !is_link($oldPath)) {
            @unlink($oldPath);
        }

        Yii::$app->session->remove('sociologImportToken');
    }

    /**
     * Importvorschauen enthalten die zu importierenden Beschlusstexte. Nicht
     * bestaetigte Dateien werden deshalb nach 24 Stunden automatisch entfernt.
     */
    private function cleanupExpiredPreviews(): void
    {
        $directory = Yii::getAlias('@runtime/sociolog-import');
        if (!is_dir($directory)) {
            return;
        }

        $expiry = time() - self::PREVIEW_MAX_AGE;
        foreach (glob($directory . '/*.json') ?: [] as $path) {
            if (is_link($path) || !is_file($path)) {
                continue;
            }

            $modifiedAt = filemtime($path);
            if ($modifiedAt !== false && $modifiedAt < $expiry) {
                @unlink($path);
            }
        }
    }
}
