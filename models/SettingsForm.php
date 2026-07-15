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

			[['moduleTitle'], 'trim'],
			[['moduleTitle'], 'required'],
			[['moduleTitle'], 'string', 'max' => 100],

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
					'decisionWorkflowEnabled'
				],
				'boolean'
			],
	
		];
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
