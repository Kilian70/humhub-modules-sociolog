<?php

namespace humhub\modules\sociolog\models;

use Yii;
use yii\base\Model;
use humhub\modules\user\models\Group;

class SettingsForm extends Model
{

    /* ============================================================
     * Allgemeine Einstellungen
     * ============================================================ */

    public $moduleTitle;
    public $latestEntriesLimit;
    public $widgetSortOrder;

    public $organs;
    public $organLinks;
    public $organColors;
    public $globalOrgans;

    public $defaultEffectiveDays;
	public $showReviewInCalendar;
	
	public $decisionWorkflowEnabled;

    /* ============================================================
     * Optionale Informationsseite
     * ============================================================ */

    public $infoPageEnabled;
    public $infoPageTitle;
    public $infoIntroText;
    public $infoDocumentUrl;
    public $infoProcessText;
    public $infoPermissionsText;
    public $infoStatusText;
    public $infoObjectionText;
    public $infoReviewText;
    public $infoDocumentsText;


    /* ============================================================
     * Rechte
     * ============================================================ */

    public $writerUsers = [];
    public $deleterUsers = [];

    public $writerGroups = [];
    public $deleterGroups = [];

    public $notifyGroups = [];


    /* ============================================================
     * Validation
     * ============================================================ */

	public function rules()
	{
		return [

			[['moduleTitle', 'infoPageTitle', 'infoDocumentUrl'], 'trim'],
			[['moduleTitle'], 'required'],
			[['moduleTitle'], 'string', 'max' => 100],
			[['infoPageTitle'], 'string', 'max' => 150],
			[['infoDocumentUrl'], 'string', 'max' => 1000],
			[['infoDocumentUrl'], 'validateInfoDocumentUrl'],
			[[
				'infoIntroText',
				'infoProcessText',
				'infoPermissionsText',
				'infoStatusText',
				'infoObjectionText',
				'infoReviewText',
				'infoDocumentsText'
			], 'string', 'max' => 5000],

			[
				[
					'organs',
					'organLinks',
					'organColors',
					'globalOrgans'
				],
				'string'
			],
	
			[
				[
					'latestEntriesLimit',
					'widgetSortOrder',
					'defaultEffectiveDays'
				],
				'integer'
			],

			[['latestEntriesLimit'], 'integer', 'min' => 1, 'max' => 50],
			[['widgetSortOrder'], 'integer', 'min' => 0],
			[['defaultEffectiveDays'], 'integer', 'min' => 0],
	
			[
				[
					'writerUsers',
					'deleterUsers',
					'writerGroups',
					'deleterGroups',
					'notifyGroups'
				],
				'safe'
			],
	
			[
				[
					'showReviewInCalendar',
					'decisionWorkflowEnabled',
					'infoPageEnabled'
				],
				'boolean'
			],
	
		];
	}

    public function validateInfoDocumentUrl(string $attribute): void
    {
        $value = trim((string)$this->$attribute);

        if ($value === '') {
            return;
        }

        $isInternal = str_starts_with($value, '/') && !str_starts_with($value, '//');
        $scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME));
        $isExternal = in_array($scheme, ['http', 'https'], true)
            && filter_var($value, FILTER_VALIDATE_URL) !== false;

        if (!$isInternal && !$isExternal) {
            $this->addError(
                $attribute,
                Yii::t(
                    'SociologModule.base',
                    'Bitte eine interne URL oder eine vollständige HTTP-/HTTPS-Adresse eingeben.'
                )
            );
        }
    }


    /* ============================================================
     * Labels
     * ============================================================ */

    public function attributeLabels()
    {
        return [

            'moduleTitle' =>
                Yii::t('SociologModule.base', 'Modulname'),

            'latestEntriesLimit' =>
                Yii::t('SociologModule.base', 'Einträge im Dashboard'),

            'widgetSortOrder' =>
                Yii::t('SociologModule.base', 'Widget-Position'),

            'organs' =>
                Yii::t('SociologModule.base', 'Organe'),

            'organLinks' =>
                Yii::t('SociologModule.base', 'Organ-Links'),

            'organColors' =>
                Yii::t('SociologModule.base', 'Organfarben'),

            'globalOrgans' =>
                Yii::t('SociologModule.base', 'Globale Organe'),

            'defaultEffectiveDays' =>
                Yii::t('SociologModule.base', 'Inkrafttreten nach (Tagen)'),

            'showReviewInCalendar' =>
                Yii::t('SociologModule.base', 'Überprüfungsdaten im Kalender anzeigen'),

            'writerUsers' =>
                Yii::t('SociologModule.base', 'Benutzer mit Schreibrecht'),

            'deleterUsers' =>
                Yii::t('SociologModule.base', 'Benutzer mit Löschrecht'),

            'writerGroups' =>
                Yii::t('SociologModule.base', 'Gruppen mit Schreibrecht'),

            'deleterGroups' =>
                Yii::t('SociologModule.base', 'Gruppen mit Löschrecht'),

            'notifyGroups' =>
                Yii::t('SociologModule.base', 'Benachrichtigungsgruppen'),

            'infoPageEnabled' =>
                Yii::t('SociologModule.base', 'Informationsseite aktivieren'),

            'infoPageTitle' =>
                Yii::t('SociologModule.base', 'Titel der Informationsseite'),

            'infoIntroText' =>
                Yii::t('SociologModule.base', 'Einleitung'),

            'infoDocumentUrl' =>
                Yii::t('SociologModule.base', 'Link zum Einleitungsdokument'),

            'infoProcessText' =>
                Yii::t('SociologModule.base', 'So entsteht ein Eintrag'),

            'infoPermissionsText' =>
                Yii::t('SociologModule.base', 'Berechtigungen'),

            'infoStatusText' =>
                Yii::t('SociologModule.base', 'Status und Fristen'),

            'infoObjectionText' =>
                Yii::t('SociologModule.base', 'Einsprache und Einwand'),

            'infoReviewText' =>
                Yii::t('SociologModule.base', 'Überprüfung'),

            'infoDocumentsText' =>
                Yii::t('SociologModule.base', 'Protokolle und Dokumente'),

        ];
    }


    /* ============================================================
     * Load Settings
     * FINAL — GroupPicker benötigt IDs, nicht Namen
     * ============================================================ */

		public function loadSettings()
	{
	
		$settings = Yii::$app->getModule('sociolog')->settings;
	
	
		/* normale Werte */
	
		$this->moduleTitle =
			$settings->get('moduleTitle', 'Logbuch');
	
		$this->latestEntriesLimit =
			$settings->get('latestEntriesLimit', 5);
	
		$this->widgetSortOrder =
			$settings->get('widgetSortOrder', 100);
	
		$this->organs =
			$settings->get('organs', '');
	
		$this->organLinks =
			$settings->get('organLinks', '');
	
		$this->organColors =
			$settings->get('organColors', '');
	
		$this->globalOrgans =
			$settings->get('globalOrgans', '');
	
		$this->defaultEffectiveDays =
			$settings->get('defaultEffectiveDays', 10);
	
		$this->showReviewInCalendar =
			$settings->get('showReviewInCalendar', false);
	
		$this->decisionWorkflowEnabled =
			$settings->get('decisionWorkflowEnabled', true);

        $this->infoPageEnabled =
            (bool)$settings->get('infoPageEnabled', false);

        $this->infoPageTitle =
            $settings->get('infoPageTitle', Yii::t('SociologModule.base', 'So funktioniert das Logbuch'));

        $this->infoIntroText =
            $settings->get('infoIntroText', '');

        $this->infoDocumentUrl =
            $settings->get('infoDocumentUrl', '');

        $this->infoProcessText =
            $settings->get('infoProcessText', '');

        $this->infoPermissionsText =
            $settings->get('infoPermissionsText', '');

        $this->infoStatusText =
            $settings->get('infoStatusText', '');

        $this->infoObjectionText =
            $settings->get('infoObjectionText', '');

        $this->infoReviewText =
            $settings->get('infoReviewText', '');

        $this->infoDocumentsText =
            $settings->get('infoDocumentsText', '');
	
	
	
		/* Benutzer */
	
		$this->writerUsers =
			$settings->getSerialized('writerUsers') ?? [];
	
		$this->deleterUsers =
			$settings->getSerialized('deleterUsers') ?? [];
	
	
	
		/* Gruppen — DIREKT ALS IDs LADEN (FINAL FIX) */
	
		$this->writerGroups =
			$settings->getSerialized('writerGroups') ?? [];
	
		$this->deleterGroups =
			$settings->getSerialized('deleterGroups') ?? [];
	
	
	
		/* Notify Groups */
	
		$this->notifyGroups =
			$settings->getSerialized('notifyGroups') ?? [];
	
	}


    /* ============================================================
     * Save Settings
     * FINAL — GroupPicker liefert IDs direkt
     * ============================================================ */

	public function save()
	{
		if (!$this->validate()) {
			return false;
		}

		$settings = Yii::$app->getModule('sociolog')->settings;
	
	
	
		/* normale Werte */
	
		$settings->set('moduleTitle', $this->moduleTitle);
	
		$settings->set('latestEntriesLimit', $this->latestEntriesLimit);
	
		$settings->set('widgetSortOrder', $this->widgetSortOrder);
	
		$settings->set('organs', $this->organs);
	
		$settings->set('organLinks', $this->organLinks);
	
		$settings->set('organColors', $this->organColors);
	
		$settings->set('globalOrgans', $this->globalOrgans);
	
		$settings->set('defaultEffectiveDays', $this->defaultEffectiveDays);
	
		$settings->set('showReviewInCalendar', $this->showReviewInCalendar);
	
		$settings->set('decisionWorkflowEnabled', $this->decisionWorkflowEnabled);

        $settings->set('infoPageEnabled', (bool)$this->infoPageEnabled);
        $settings->set('infoPageTitle', trim((string)$this->infoPageTitle));
        $settings->set('infoIntroText', trim((string)$this->infoIntroText));
        $settings->set('infoDocumentUrl', trim((string)$this->infoDocumentUrl));
        $settings->set('infoProcessText', trim((string)$this->infoProcessText));
        $settings->set('infoPermissionsText', trim((string)$this->infoPermissionsText));
        $settings->set('infoStatusText', trim((string)$this->infoStatusText));
        $settings->set('infoObjectionText', trim((string)$this->infoObjectionText));
        $settings->set('infoReviewText', trim((string)$this->infoReviewText));
        $settings->set('infoDocumentsText', trim((string)$this->infoDocumentsText));
	
	
	
		/* Benutzer */
	
		$settings->setSerialized(
			'writerUsers',
			array_values(array_filter((array)$this->writerUsers))
		);
	
		$settings->setSerialized(
			'deleterUsers',
			array_values(array_filter((array)$this->deleterUsers))
		);
	
	
	
		/* Gruppen — DIREKT IDs SPEICHERN (FINAL FIX) */
	
		$settings->setSerialized(
			'writerGroups',
			array_values(array_filter((array)$this->writerGroups))
		);
	
		$settings->setSerialized(
			'deleterGroups',
			array_values(array_filter((array)$this->deleterGroups))
		);
	
	
	
		/* Notify Groups */
	
		$settings->setSerialized(
			'notifyGroups',
			array_values(array_filter((array)$this->notifyGroups))
		);
	
	
		return true;
	
	}

}
