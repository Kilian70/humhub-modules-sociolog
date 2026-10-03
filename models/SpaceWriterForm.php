<?php

namespace humhub\modules\sociolog\models;

use humhub\modules\space\models\Membership;
use humhub\modules\user\models\User;
use Yii;
use yii\base\Model;

class SpaceWriterForm extends Model
{
    public $writerMode = SpaceConfig::WRITER_MODE_SPACE_ADMINS;
    public $writerUsers = [];

    private SpaceConfig $config;

    public function __construct(SpaceConfig $config, $configData = [])
    {
        $this->config = $config;
        parent::__construct($configData);
    }

    public function rules(): array
    {
        return [
            [['writerMode'], 'required'],
            [['writerMode'], 'in', 'range' => [
                SpaceConfig::WRITER_MODE_SPACE_ADMINS,
                SpaceConfig::WRITER_MODE_SELECTED,
            ]],
            [['writerUsers'], 'safe'],
            [['writerUsers'], 'validateWriterUsers'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'writerMode' => Yii::t('SociologModule.base', 'Wer darf Einträge erfassen?'),
            'writerUsers' => Yii::t('SociologModule.base', 'Zuständige Personen'),
        ];
    }

    public function loadConfig(): void
    {
        $this->writerMode = $this->config->writer_mode ?: SpaceConfig::WRITER_MODE_SPACE_ADMINS;
        $this->writerUsers = $this->config->getWriterUserGuids();
    }

    public function validateWriterUsers(string $attribute): void
    {
        $guids = array_values(array_unique(array_filter((array)$this->$attribute)));
        if ($this->writerMode !== SpaceConfig::WRITER_MODE_SELECTED) {
            return;
        }

        if ($guids === []) {
            $this->addError(
                $attribute,
                Yii::t('SociologModule.base', 'Bitte mindestens eine zuständige Person auswählen.')
            );
            return;
        }

        foreach ($guids as $guid) {
            $user = User::findOne(['guid' => $guid]);
            if (!$user || !Membership::find()->where([
                'space_id' => (int)$this->config->space_id,
                'user_id' => (int)$user->id,
            ])->exists()) {
                $this->addError(
                    $attribute,
                    Yii::t('SociologModule.base', 'Zuständige Personen müssen Mitglied dieses Spaces sein.')
                );
                return;
            }
        }
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->config->writer_mode = $this->writerMode;
        $this->config->setWriterUserGuids(
            $this->writerMode === SpaceConfig::WRITER_MODE_SELECTED ? (array)$this->writerUsers : []
        );

        $saved = $this->config->save();
        if ($saved) {
            Entry::resetRuntimeCaches();
        }

        return $saved;
    }
}
