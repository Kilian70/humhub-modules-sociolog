<?php

namespace humhub\modules\sociolog\models;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class ImportUploadForm extends Model
{
    /** @var UploadedFile|null */
    public $file;

    public function rules(): array
    {
        return [
            [['file'], 'required'],
            [['file'], 'file',
                'extensions' => ['csv'],
                'checkExtensionByMimeType' => false,
                'maxSize' => 10 * 1024 * 1024,
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'file' => Yii::t('SociologModule.base', 'Bereinigte CSV-Importdatei'),
        ];
    }
}
