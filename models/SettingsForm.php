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
    public $effectiveDateAddExtraDay;
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
    public $infoGuidelineText;
    public $infoExamplesText;

    /* ============================================================
     * Optionale Formularvorgaben
     * ============================================================ */

    public $autoPublicationDate;
    public $fixedDecisionTypeId;
    public $hiddenDecisionTypeIds = [];
    public $showDecisionTypeHeader;
    public $reviewDateRequiredForNewEntries;
    public $limitedReviewMaintenanceEnabled;
    public $decisionDateLabel;
    public $topicOwnerLabel;
    public $topicOwnerPlaceholder;
    public $protocolsLabel;
    public $pendingStatusLabel;


    /* ============================================================
     * Rechte
     * ============================================================ */

    public $writerUsers = [];
    public $deleterUsers = [];

    public $writerGroups = [];
    public $deleterGroups = [];
    public $managerUsers = [];
    public $managerGroups = [];

    public $notifyGroups = [];
    public $lockPublishedEntries;
    public $statusManagersOnly;
    public $extendedStatusesEnabled;


    /* ============================================================
     * Validation
     * ============================================================ */

	public function rules()
	{
		return [

			[[
				'moduleTitle',
				'infoPageTitle',
				'infoDocumentUrl',
				'decisionDateLabel',
				'topicOwnerLabel',
				'topicOwnerPlaceholder',
				'protocolsLabel',
				'pendingStatusLabel'
			], 'trim'],
			[['moduleTitle'], 'required'],
			[['moduleTitle'], 'string', 'max' => 100],
			[['infoPageTitle'], 'string', 'max' => 150],
			[['infoDocumentUrl'], 'string', 'max' => 1000],
			[['infoDocumentUrl'], 'validateInfoDocumentUrl'],
			[[
				'decisionDateLabel',
				'topicOwnerLabel',
				'protocolsLabel',
				'pendingStatusLabel'
			], 'required'],
			[[
				'decisionDateLabel',
				'topicOwnerLabel',
				'topicOwnerPlaceholder',
				'protocolsLabel',
				'pendingStatusLabel'
			], 'string', 'max' => 100],
			[[
				'infoIntroText',
				'infoProcessText',
				'infoPermissionsText',
				'infoStatusText',
				'infoObjectionText',
				'infoReviewText',
				'infoDocumentsText',
				'infoGuidelineText',
				'infoExamplesText'
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
					'defaultEffectiveDays',
					'fixedDecisionTypeId'
				],
				'integer'
			],

			[['latestEntriesLimit'], 'integer', 'min' => 1, 'max' => 50],
			[['widgetSortOrder'], 'integer', 'min' => 0],
			[['defaultEffectiveDays'], 'integer', 'min' => 0],
			[['fixedDecisionTypeId'], 'integer', 'min' => 0],
			[['fixedDecisionTypeId'], 'validateFixedDecisionType'],
	
			[
				[
					'writerUsers',
					'deleterUsers',
					'writerGroups',
					'deleterGroups',
					'managerUsers',
					'managerGroups',
					'notifyGroups',
					'hiddenDecisionTypeIds'
				],
				'safe'
			],
	
			[
				[
					'showReviewInCalendar',
					'decisionWorkflowEnabled',
					'infoPageEnabled',
					'autoPublicationDate',
					'showDecisionTypeHeader',
					'reviewDateRequiredForNewEntries',
					'limitedReviewMaintenanceEnabled',
					'lockPublishedEntries',
					'statusManagersOnly',
					'extendedStatusesEnabled',
					'effectiveDateAddExtraDay'
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

    public function validateFixedDecisionType(string $attribute): void
    {
        $id = (int)$this->$attribute;

        if ($id > 0 && !DecisionType::find()->where(['id' => $id])->exists()) {
            $this->addError(
                $attribute,
                Yii::t('SociologModule.base', 'Der ausgewählte Entscheid-Typ existiert nicht mehr.')
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

            'effectiveDateAddExtraDay' =>
                Yii::t('SociologModule.base', 'Inkrafttreten erst am Folgetag der vollständigen Frist'),

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

            'managerUsers' =>
                Yii::t('SociologModule.base', 'Logbuch-Verantwortliche'),

            'managerGroups' =>
                Yii::t('SociologModule.base', 'Verantwortliche Gruppen'),

            'lockPublishedEntries' =>
                Yii::t('SociologModule.base', 'Veröffentlichte Einträge für Erfasser:innen sperren'),

            'statusManagersOnly' =>
                Yii::t('SociologModule.base', 'Manuelle Statusänderung nur für Logbuch-Verantwortliche'),

            'extendedStatusesEnabled' =>
                Yii::t('SociologModule.base', 'Zusätzliche Status „Schwerwiegender Einwand“ und „Ersetzt“ aktivieren'),

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

            'infoGuidelineText' =>
                Yii::t('SociologModule.base', 'Was ist ein Grundsatzentscheid?'),

            'infoExamplesText' =>
                Yii::t('SociologModule.base', 'Beispiele'),

            'autoPublicationDate' =>
                Yii::t('SociologModule.base', 'Veröffentlichungsdatum bei neuen Einträgen automatisch setzen'),

            'fixedDecisionTypeId' =>
                Yii::t('SociologModule.base', 'Feste Entscheidungsart für neue Einträge'),

            'hiddenDecisionTypeIds' =>
                Yii::t('SociologModule.base', 'Ausgeblendete Entscheidungstypen'),

            'showDecisionTypeHeader' =>
                Yii::t('SociologModule.base', 'Entscheidungstyp in Karten, Dashboard und Detailansicht anzeigen'),

            'reviewDateRequiredForNewEntries' =>
                Yii::t('SociologModule.base', 'Überprüfungsdatum bei neuen Einträgen verlangen'),

            'limitedReviewMaintenanceEnabled' =>
                Yii::t('SociologModule.base', 'Eingeschränkte Pflege nach einer Überprüfung erlauben'),

            'decisionDateLabel' =>
                Yii::t('SociologModule.base', 'Bezeichnung des Entscheidungsdatums'),

            'topicOwnerLabel' =>
                Yii::t('SociologModule.base', 'Bezeichnung der Ausführungsverantwortung'),

            'topicOwnerPlaceholder' =>
                Yii::t('SociologModule.base', 'Platzhalter der Ausführungsverantwortung'),

            'protocolsLabel' =>
                Yii::t('SociologModule.base', 'Bezeichnung für Protokolle und Dokumente'),

            'pendingStatusLabel' =>
                Yii::t('SociologModule.base', 'Bezeichnung des ersten Status'),

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

        $this->effectiveDateAddExtraDay =
            (bool)$settings->get('effectiveDateAddExtraDay', true);
	
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

        $this->infoGuidelineText =
            $settings->get('infoGuidelineText', '');

        $this->infoExamplesText =
            $settings->get('infoExamplesText', '');

        $this->autoPublicationDate =
            (bool)$settings->get('autoPublicationDate', false);

        $this->fixedDecisionTypeId =
            (int)$settings->get('fixedDecisionTypeId', 0);

        $this->hiddenDecisionTypeIds =
            $settings->getSerialized('hiddenDecisionTypeIds') ?? [];

        $this->showDecisionTypeHeader =
            (bool)$settings->get('showDecisionTypeHeader', true);

        $this->reviewDateRequiredForNewEntries =
            (bool)$settings->get('reviewDateRequiredForNewEntries', false);

        $this->limitedReviewMaintenanceEnabled =
            (bool)$settings->get('limitedReviewMaintenanceEnabled', false);

        $this->decisionDateLabel =
            $settings->get('decisionDateLabel', Yii::t('SociologModule.base', 'Beschlussdatum'));

        $this->topicOwnerLabel =
            $settings->get('topicOwnerLabel', Yii::t('SociologModule.base', 'Themenhüter:in'));

        $this->topicOwnerPlaceholder =
            $settings->get('topicOwnerPlaceholder', '');

        $this->protocolsLabel =
            $settings->get('protocolsLabel', Yii::t('SociologModule.base', 'Protokolle'));

        $this->pendingStatusLabel =
            $settings->get('pendingStatusLabel', Yii::t('SociologModule.base', 'Nicht in Kraft'));
	
	
	
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

        $this->managerUsers =
            $settings->getSerialized('managerUsers') ?? [];

        $this->managerGroups =
            $settings->getSerialized('managerGroups') ?? [];

        $this->lockPublishedEntries =
            (bool)$settings->get('lockPublishedEntries', false);

        $this->statusManagersOnly =
            (bool)$settings->get('statusManagersOnly', false);

        $this->extendedStatusesEnabled =
            (bool)$settings->get('extendedStatusesEnabled', false);
	
	
	
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
        $settings->set('effectiveDateAddExtraDay', (bool)$this->effectiveDateAddExtraDay);
	
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
        $settings->set('infoGuidelineText', trim((string)$this->infoGuidelineText));
        $settings->set('infoExamplesText', trim((string)$this->infoExamplesText));
        $settings->set('autoPublicationDate', (bool)$this->autoPublicationDate);
        $hiddenDecisionTypeIds = array_values(array_unique(array_filter(array_map(
            'intval',
            (array)$this->hiddenDecisionTypeIds
        ))));
        $fixedDecisionTypeId = (int)$this->fixedDecisionTypeId;
        if (in_array($fixedDecisionTypeId, $hiddenDecisionTypeIds, true)) {
            $fixedDecisionTypeId = 0;
        }
        $settings->set('fixedDecisionTypeId', $fixedDecisionTypeId);
        $settings->setSerialized(
            'hiddenDecisionTypeIds',
            $hiddenDecisionTypeIds
        );
        $settings->set('showDecisionTypeHeader', (bool)$this->showDecisionTypeHeader);
        $settings->set(
            'reviewDateRequiredForNewEntries',
            (bool)$this->reviewDateRequiredForNewEntries
        );
        $settings->set(
            'limitedReviewMaintenanceEnabled',
            (bool)$this->limitedReviewMaintenanceEnabled
        );
        $settings->set('decisionDateLabel', trim((string)$this->decisionDateLabel));
        $settings->set('topicOwnerLabel', trim((string)$this->topicOwnerLabel));
        $settings->set('topicOwnerPlaceholder', trim((string)$this->topicOwnerPlaceholder));
        $settings->set('protocolsLabel', trim((string)$this->protocolsLabel));
        $settings->set('pendingStatusLabel', trim((string)$this->pendingStatusLabel));
	
	
	
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

        $settings->setSerialized(
            'managerUsers',
            array_values(array_filter((array)$this->managerUsers))
        );

        $settings->setSerialized(
            'managerGroups',
            array_values(array_filter((array)$this->managerGroups))
        );

        $settings->set('lockPublishedEntries', (bool)$this->lockPublishedEntries);
        $settings->set('statusManagersOnly', (bool)$this->statusManagersOnly);
        $settings->set('extendedStatusesEnabled', (bool)$this->extendedStatusesEnabled);
	
	
	
		/* Notify Groups */
	
		$settings->setSerialized(
			'notifyGroups',
			array_values(array_filter((array)$this->notifyGroups))
		);
	
	
		return true;
	
	}

}
