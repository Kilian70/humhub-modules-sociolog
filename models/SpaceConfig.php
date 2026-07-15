<?php

namespace humhub\modules\sociolog\models;

use Yii;
use humhub\components\ActiveRecord;
use humhub\modules\space\models\Space;

class SpaceConfig extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%sociolog_space_config}}';
    }

    public function rules()
    {
        return [

            [['space_id'], 'required'],

            [['space_id', 'organ_id'], 'integer'],

            [['space_id'], 'unique'],

            [['space_id'], 'exist', 'targetClass' => Space::class, 'targetAttribute' => ['space_id' => 'id']],

            [['organ_id'], 'exist', 'targetClass' => Organ::class, 'targetAttribute' => ['organ_id' => 'id'], 'skipOnEmpty' => true],

            [['link'], 'string', 'max' => 255],

            [['link_mode'], 'string', 'max' => 50],

            [['link_mode'], 'in', 'range' => ['about', 'space', 'custom', 'none']],

            [
                ['link'],
                'required',
                'when' => static fn(self $model): bool => $model->link_mode === 'custom',
            ],

            [
                ['link'],
                'url',
                'validSchemes' => ['http', 'https'],
                'when' => static fn(self $model): bool => $model->link_mode === 'custom',
            ],

            [['global_write', 'can_delete', 'enabled', 'is_organ_space'], 'boolean'],

        ];
    }

    public function attributeLabels()
    {
        return [

            'space_id' => Yii::t('SociologModule.base', 'Space'),

            'organ_id' => Yii::t('SociologModule.base', 'Organ'),

            'global_write' => Yii::t('SociologModule.base', 'Global schreiben'),

            'can_delete' => Yii::t('SociologModule.base', 'Löschen'),

            'link' => Yii::t('SociologModule.base', 'Externer Link'),

            'link_mode' => Yii::t('SociologModule.base', 'Linktyp'),

            'enabled' => Yii::t('SociologModule.base', 'Im Logbuch anzeigen'),

            'is_organ_space' => Yii::t('SociologModule.base', 'Organ-Space'),

        ];
    }

    /**
     * Relation zum Space
     */
    public function getSpace()
    {
        return $this->hasOne(
            Space::class,
            ['id' => 'space_id']
        );
    }

    /**
     * Relation zum Organ
     */
    public function getOrgan()
    {
        return $this->hasOne(
            Organ::class,
            ['id' => 'organ_id']
        );
    }

    /**
     * Gibt den effektiven Link für das Organ zurück
     */
    public function getOrganLink()
    {

        if ($this->link_mode === 'none') {
            return null;
        }

        if ($this->link_mode === 'custom' && !empty($this->link)) {
            return $this->link;
        }

        if ($this->space) {

            if ($this->link_mode === 'space') {
                return $this->space->getUrl();
            }

            if ($this->link_mode === 'about') {
                return $this->space->getUrl() . '/about';
            }

        }

        return null;
    }

}
