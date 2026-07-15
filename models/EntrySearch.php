<?php

namespace humhub\modules\sociolog\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ============================================================
 * 🔹 EntrySearch – Suchmodell für Sociolog-Einträge
 * ------------------------------------------------------------
 * - Unterstützt Filter: Jahr, Organ, Entscheidungstyp, Status, Freitext
 * - Automatische Statuslogik (pending, valid, review, expired)
 * ============================================================
 */
class EntrySearch extends Model
{
    public $year;
    public $organ;
    public $status;
    public $query;
    public $decision_type_id;

    // ============================================================
    // 🧩 Validierungsregeln
    // ============================================================
    public function rules()
    {
        return [
            [['organ', 'status', 'query'], 'safe'],
            [['decision_type_id', 'year'], 'integer'],
        ];
    }

    // ============================================================
    // 🧩 Szenarien
    // ============================================================
    public function scenarios()
    {
        return Model::scenarios();
    }

    // ============================================================
    // 🏷️ Attribut-Bezeichnungen (für Labels in Formularen & Tabellen)
    // ============================================================
    public function attributeLabels()
    {
        return [
            'year' => Yii::t('SociologModule.base', 'Jahr'),
            'organ' => Yii::t('SociologModule.base', 'Organ'),
            'query' => Yii::t('SociologModule.base', 'Titel / Beschluss'),
            'decision_type_id' => Yii::t('SociologModule.base', 'Art der Entscheidung'),
            'status' => Yii::t('SociologModule.base', 'Status'),
        ];
    }

    // ============================================================
    // 🔍 Haupt-Suchfunktion
    // ============================================================
		public function search($params)
		{
			$query = Entry::find()
				->orderBy(['decision_date' => SORT_DESC]);
		
			$dataProvider = new ActiveDataProvider([
				'query' => $query,
				'pagination' => ['pageSize' => 50],
			]);
		
			$this->load($params);
		
			if (!$this->validate()) {
				return $dataProvider;
			}
		
			// 🔹 Organ
			if (!empty($this->organ)) {
				$query->andFilterWhere([
					'current_organ' => (int)$this->organ
				]);
			}

        // 🔹 Entscheidungstyp (Art des Entscheids)
        if (!empty($this->decision_type_id)) {
            $query->andFilterWhere(['decision_type_id' => $this->decision_type_id]);
        }

        // 🔹 Jahr (aus decision_date)
        if (!empty($this->year)) {
            $query->andFilterWhere([
                'between',
                'decision_date',
                "{$this->year}-01-01",
                "{$this->year}-12-31",
            ]);
        }

        // 🔹 Freitextsuche (Titel + Beschluss)
		$queryText = trim($this->query ?? '');
		
		if ($queryText !== '') {
			$query->andFilterWhere(['or',
				['like', 'title', $queryText],
				['like', 'decision', $queryText],
			]);
		}

        // 🔹 Statusfilter (automatisch + manuell)
        if (!empty($this->status)) {
            $today  = date('Y-m-d');
            $manual = ['status' => $this->status];
            $auto   = null;

            switch ($this->status) {
                case 'pending': // Nicht in Kraft
                    $auto = [
                        'and',
                        ['not', ['effective_date' => null]],
                        ['>', 'effective_date', $today],
                    ];
                    break;

                case 'valid': // Gültig
                    $auto = [
                        'and',
                        [
                            'or',
                            ['<=', 'effective_date', $today],
                            ['effective_date' => null],
                        ],
                        [
                            'or',
                            ['review_date' => null],
                            ['>', 'review_date', $today],
                        ],
                    ];
                    break;

                case 'review': // In Überprüfung
                    $auto = [
                        'and',
                        ['not', ['review_date' => null]],
                        ['<=', 'review_date', $today],
                    ];
                    break;

                case 'expired': // Nicht mehr gültig
                    $auto = [
                        'and',
                        ['not', ['review_date' => null]],
                        ['<', 'review_date', $today],
                    ];
                    break;
            }

            if ($auto) {
                // Manuell ODER automatisch
                $query->andWhere(['or', $manual, $auto]);
            } else {
                // Nur manuell gesetzter Status
                $query->andWhere($manual);
            }
        }

        return $dataProvider;
    }
}
