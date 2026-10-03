<?php

namespace humhub\modules\sociolog\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;
use Yii;

class EntryReview extends ActiveRecord
{
    public const RESULT_CONFIRMED = 'confirmed';
    public const RESULT_REPEALED = 'repealed';

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
            [['previous_review_date', 'next_review_date'], 'date', 'format' => 'php:Y-m-d'],
            [['result'], 'in', 'range' => [self::RESULT_CONFIRMED, self::RESULT_REPEALED]],
        ];
    }

    public static function resultOptions(): array
    {
        return [
            self::RESULT_CONFIRMED => Yii::t('SociologModule.base', 'Entscheid bleibt bestehen'),
            self::RESULT_REPEALED => Yii::t('SociologModule.base', 'Entscheid wird ausser Kraft gesetzt'),
        ];
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
