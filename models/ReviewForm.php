<?php

namespace humhub\modules\sociolog\models;

use Yii;
use yii\base\Model;

/**
 * Eingeschränkte Eingabe für eine dokumentierte Überprüfung.
 */
class ReviewForm extends Model
{
    public $reviewDate;
    public $protocolTitle;
    public $protocolUrl;

    public function rules(): array
    {
        return [
            [['reviewDate'], 'required'],
            [['reviewDate'], 'date', 'format' => 'php:Y-m-d'],
            [['protocolTitle', 'protocolUrl'], 'trim'],
            [['protocolTitle'], 'string', 'max' => 255],
            [['protocolUrl'], 'string', 'max' => 1000],
            [['protocolUrl'], 'url', 'validSchemes' => ['http', 'https']],
            [['protocolTitle'], 'validateProtocolPair'],
            [['protocolUrl'], 'validateProtocolPair'],
        ];
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
            'reviewDate' => Yii::t('SociologModule.base', 'Überprüfung ab'),
            'protocolTitle' => Yii::t('SociologModule.base', 'Titel des neuen Protokolls'),
            'protocolUrl' => Yii::t('SociologModule.base', 'Link zum neuen Protokoll'),
        ];
    }
}
