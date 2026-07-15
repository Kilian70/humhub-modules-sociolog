<?php

namespace humhub\modules\sociolog\models;

use Yii;
use humhub\components\ActiveRecord;

class EntryFlow extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%sociolog_entry_flow}}';
    }

    public function rules()
    {
        return [
            [['entry_id','action'], 'required'],
            [['entry_id','from_organ_id','to_organ_id','created_by'], 'integer'],
            [['created_at'], 'safe'],
            [['action'], 'string', 'max' => 20],
            [['action'], 'in', 'range' => ['forward', 'takeover', 'decision', 'return', 'review']],
        ];
    }

    public static function log($entryId, $fromOrgan, $toOrgan, $action): bool
    {
        $flow = new self();

        $flow->entry_id = $entryId;
        $flow->from_organ_id = $fromOrgan;
        $flow->to_organ_id = $toOrgan;
        $flow->action = $action;
        $flow->created_at = date('Y-m-d H:i:s');
        $flow->created_by = Yii::$app->user->id;

        return $flow->save();
    }
    
    public function getLabel(): string
{
    $from = \humhub\modules\space\models\Space::findOne($this->from_organ_id);
    $to   = \humhub\modules\space\models\Space::findOne($this->to_organ_id);

    $fromName = $from ? $from->name : '-';
    $toName   = $to ? $to->name : '-';

    switch ($this->action) {

        case 'forward':
            return Yii::t('SociologModule.base', 'Weitergeleitet von') . ' '
                . $fromName . ' '
                . Yii::t('SociologModule.base', 'an') . ' '
                . $toName;

        case 'takeover':
            return Yii::t('SociologModule.base', 'Übernommen von') . ' '
                . $toName;

        case 'decision':
            return Yii::t('SociologModule.base', 'Beschluss gefasst');

        case 'return':
            return Yii::t('SociologModule.base', 'Entscheid zurückgegeben von') . ' '
                . $fromName . ' '
                . Yii::t('SociologModule.base', 'an') . ' '
                . $toName;

        case 'review':
            return Yii::t('SociologModule.base', 'Überprüfung durchgeführt durch') . ' '
                . $fromName;
    }

    return '';
}
}
