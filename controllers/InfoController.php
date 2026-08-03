<?php

namespace humhub\modules\sociolog\controllers;

use Yii;
use humhub\components\Controller;
use yii\web\NotFoundHttpException;

/**
 * Optionale, vom Logbuch fachlich getrennte Informationsseite.
 */
class InfoController extends Controller
{
    public function getAccessRules(): array
    {
        return [
            ['login'],
        ];
    }

    public function actionIndex()
    {
        $module = Yii::$app->getModule('sociolog');

        if (!$module || !(bool)$module->settings->get('infoPageEnabled', false)) {
            throw new NotFoundHttpException(
                Yii::t('SociologModule.base', 'Die Informationsseite ist nicht aktiviert.')
            );
        }

        $title = trim((string)$module->settings->get(
            'infoPageTitle',
            Yii::t('SociologModule.base', 'So funktioniert das Logbuch')
        ));

        if ($title === '') {
            $title = Yii::t('SociologModule.base', 'So funktioniert das Logbuch');
        }

        $this->view->title = $title;

        return $this->render('index', [
            'title' => $title,
            'introText' => (string)$module->settings->get('infoIntroText', ''),
            'documentUrl' => trim((string)$module->settings->get('infoDocumentUrl', '')),
            'sections' => [
                [
                    'icon' => 'fa-random',
                    'class' => 'sociolog-info-card--process',
                    'title' => Yii::t('SociologModule.base', 'So entsteht ein Eintrag'),
                    'text' => (string)$module->settings->get('infoProcessText', ''),
                ],
                [
                    'icon' => 'fa-user-secret',
                    'class' => 'sociolog-info-card--permissions',
                    'title' => Yii::t('SociologModule.base', 'Wer darf was?'),
                    'text' => (string)$module->settings->get('infoPermissionsText', ''),
                ],
                [
                    'icon' => 'fa-clock',
                    'class' => 'sociolog-info-card--status',
                    'title' => Yii::t('SociologModule.base', 'Status und Fristen'),
                    'text' => (string)$module->settings->get('infoStatusText', ''),
                ],
                [
                    'icon' => 'fa-exclamation-circle',
                    'class' => 'sociolog-info-card--objection',
                    'title' => Yii::t('SociologModule.base', 'Einsprache und Einwand'),
                    'text' => (string)$module->settings->get('infoObjectionText', ''),
                ],
                [
                    'icon' => 'fa-calendar-check-o',
                    'class' => 'sociolog-info-card--review',
                    'title' => Yii::t('SociologModule.base', 'Überprüfung'),
                    'text' => (string)$module->settings->get('infoReviewText', ''),
                ],
                [
                    'icon' => 'fa-file-text-o',
                    'class' => 'sociolog-info-card--documents',
                    'title' => Yii::t('SociologModule.base', 'Protokolle und Dokumente'),
                    'text' => (string)$module->settings->get('infoDocumentsText', ''),
                ],
                [
                    'icon' => 'fa-balance-scale',
                    'class' => 'sociolog-info-card--guideline',
                    'title' => Yii::t('SociologModule.base', 'Was ist ein Grundsatzentscheid?'),
                    'text' => (string)$module->settings->get('infoGuidelineText', ''),
                ],
                [
                    'icon' => 'fa-list-alt',
                    'class' => 'sociolog-info-card--examples',
                    'title' => Yii::t('SociologModule.base', 'Beispiele'),
                    'text' => (string)$module->settings->get('infoExamplesText', ''),
                ],
            ],
        ]);
    }
}
