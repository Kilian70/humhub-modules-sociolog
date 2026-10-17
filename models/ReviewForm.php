<?php

namespace humhub\modules\sociolog\models;

use Yii;
use yii\base\Model;

/**
 * Eingeschränkte Eingabe für eine dokumentierte Überprüfung.
 */
class ReviewForm extends Model
{
    public const SCENARIO_LIMITED = 'limited';

    public $result;
    public $justification;
    public $reviewDate;
    public $protocolTitle;
    public $protocolUrl;

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        // Das Ergebnis bleibt serverseitig auf „bestehen bleibt“ festgelegt.
        $scenarios[self::SCENARIO_LIMITED] = ['justification', 'reviewDate', 'protocolTitle', 'protocolUrl'];
        return $scenarios;
    }

    public function rules(): array
    {
        return [
            [['result', 'justification'], 'required'],
            [['result'], 'in', 'range' => [EntryReview::RESULT_CONFIRMED, EntryReview::RESULT_REPEALED]],
            [['justification'], 'string', 'max' => 5000],
            [['reviewDate'], 'required', 'when' => fn(self $model): bool => $model->result === EntryReview::RESULT_CONFIRMED],
            [['reviewDate'], 'date', 'format' => 'php:Y-m-d'],
            [['reviewDate'], 'validateNextReviewDate'],
            [['protocolTitle', 'protocolUrl'], 'trim'],
            [['protocolTitle'], 'string', 'max' => 255],
            [['protocolUrl'], 'string', 'max' => 1000],
            [['protocolUrl'], 'url', 'validSchemes' => ['http', 'https']],
            [['protocolTitle'], 'validateProtocolPair'],
            [['protocolUrl'], 'validateProtocolPair'],
        ];
    }

    public function validateNextReviewDate(string $attribute): void
    {
        if ($this->result === EntryReview::RESULT_CONFIRMED
            && $this->$attribute
            && (string)$this->$attribute <= date('Y-m-d')) {
            $this->addError(
                $attribute,
                Yii::t('SociologModule.base', 'Das nächste Überprüfungsdatum muss in der Zukunft liegen.')
            );
        }
    }

    public function validateProtocolPair(string $attribute): void
    {
        $hasTitle = trim((string)$this->protocolTitle) !== '';
        $hasUrl = trim((string)$this->protocolUrl) !== '';

        if ($hasTitle !== $hasUrl) {
            $this->addError(
                $attribute,
                Yii::t('SociologModule.base', 'Für ein neues Protokoll werden Titel und Link benötigt.')
            );
        }
    }

    public function attributeLabels(): array
    {
        return [
            'result' => Yii::t('SociologModule.base', 'Ergebnis der Überprüfung'),
            'justification' => Yii::t('SociologModule.base', 'Neue Begründung der Überprüfung'),
            'reviewDate' => Yii::t('SociologModule.base', 'Nächste Überprüfung ab'),
            'protocolTitle' => Yii::t('SociologModule.base', 'Titel des neuen Protokolls'),
            'protocolUrl' => Yii::t('SociologModule.base', 'Link zum neuen Protokoll'),
        ];
    }
}
