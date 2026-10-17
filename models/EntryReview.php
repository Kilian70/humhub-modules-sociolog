<?php

namespace humhub\modules\sociolog\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;
use Yii;

class EntryReview extends ActiveRecord
{
    public const RESULT_CONFIRMED = 'confirmed';
    public const RESULT_REPEALED = 'repealed';
    public const RESULT_REPLACED = 'replaced';

    public static function tableName()
    {
        return '{{%sociolog_entry_review}}';
    }

    public function rules(): array
    {
        return [
            [['entry_id', 'result', 'justification', 'created_at'], 'required'],
            [['entry_id', 'protocol_id', 'created_at', 'created_by'], 'integer'],
            [['justification'], 'string'],
            [['replacement_url'], 'string', 'max' => 1000],
            [['replacement_url'], 'url', 'validSchemes' => ['http', 'https']],
            [['replacement_url'], 'required', 'when' => fn(self $model): bool => $model->result === self::RESULT_REPLACED],
            [['previous_review_date', 'next_review_date'], 'date', 'format' => 'php:Y-m-d'],
            [['result'], 'in', 'range' => array_keys(self::resultOptions())],
        ];
    }

    public static function resultOptions(): array
    {
        return [
            self::RESULT_CONFIRMED => Yii::t('SociologModule.base', 'Entscheid bleibt bestehen'),
            self::RESULT_REPEALED => Yii::t('SociologModule.base', 'Nicht mehr gültig (ausser Kraft gesetzt)'),
            self::RESULT_REPLACED => Yii::t('SociologModule.base', 'Ersetzt durch einen neuen Beschluss'),
        ];
    }

    public function getSafeReplacementUrl(): ?string
    {
        $url = trim((string)$this->replacement_url);
        if (!in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        return $url;
    }

    public function getEntry()
    {
        return $this->hasOne(Entry::class, ['id' => 'entry_id']);
    }

    public function getProtocol()
    {
        return $this->hasOne(Protocol::class, ['id' => 'protocol_id']);
    }

    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}
