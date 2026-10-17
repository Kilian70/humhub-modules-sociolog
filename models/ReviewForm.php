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
    public $replacementUrl;

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_LIMITED] = ['result', 'justification', 'reviewDate', 'protocolTitle', 'protocolUrl', 'replacementUrl'];
        return $scenarios;
    }

    public function rules(): array
    {
        return [
            [['justification'], 'trim'],
            [['result', 'justification'], 'required'],
            [['result'], 'in', 'range' => array_keys(EntryReview::resultOptions())],
            [['justification'], 'string', 'max' => 5000],
            [['reviewDate'], 'filter', 'filter' => fn($value) => $this->result === EntryReview::RESULT_CONFIRMED ? $value : null, 'skipOnEmpty' => false],
            [['reviewDate'], 'required', 'when' => fn(self $model): bool => $model->result === EntryReview::RESULT_CONFIRMED, 'enableClientValidation' => false],
            [['replacementUrl'], 'trim'],
            [['replacementUrl'], 'filter', 'filter' => fn($value) => $this->result === EntryReview::RESULT_REPLACED ? $value : null, 'skipOnEmpty' => false],
            [['replacementUrl'], 'required', 'when' => fn(self $model): bool => $model->result === EntryReview::RESULT_REPLACED, 'enableClientValidation' => false],
            [['replacementUrl'], 'string', 'max' => 1000],
            [['replacementUrl'], 'url', 'validSchemes' => ['http', 'https']],
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
            'replacementUrl' => Yii::t('SociologModule.base', 'Link zum neuen Beschluss'),
            'result' => Yii::t('SociologModule.base', 'Ergebnis der Überprüfung'),
            'justification' => Yii::t('SociologModule.base', 'Neue Begründung der Überprüfung'),
            'reviewDate' => Yii::t('SociologModule.base', 'Nächste Überprüfung ab'),
            'protocolTitle' => Yii::t('SociologModule.base', 'Titel des neuen Protokolls'),
            'protocolUrl' => Yii::t('SociologModule.base', 'Link zum neuen Protokoll'),
        ];
    }
}
