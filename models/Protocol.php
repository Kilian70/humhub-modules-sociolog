<?php

namespace humhub\modules\sociolog\models;

use Yii;
use humhub\components\ActiveRecord;

/**
 * Protocol – Protokolle zu Sociolog-Entscheidungen
 */
class Protocol extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%sociolog_protocol}}';
    }

    public function rules()
    {
        return [
            [['entry_id', 'title', 'url'], 'required'],
            [['entry_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['url'], 'string', 'max' => 1000],
            [['url'], 'url', 'validSchemes' => ['http', 'https']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'title' => Yii::t('SociologModule.base', 'Protokoll'),
            'url' => Yii::t('SociologModule.base', 'Link'),
        ];
    }

    /**
     * Beziehung zum Beschluss
     */
    public function getEntry()
    {
        return $this->hasOne(Entry::class, ['id' => 'entry_id']);
    }

    /**
     * Liefert nur sichere, vollständige HTTP-/HTTPS-Links aus.
     */
    public function getSafeUrl(): ?string
    {
        $url = trim((string)$this->url);
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));

        if (!in_array($scheme, ['http', 'https'], true)
            || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $url;
    }
}
