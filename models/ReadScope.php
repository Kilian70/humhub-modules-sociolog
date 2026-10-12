<?php

namespace humhub\modules\sociolog\models;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerSettingsManager;
use humhub\modules\sociolog\services\ReadAccess;
use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Url;

/** Persistent private container: HumHub's permalink/comment/file checks use this ACL. */
class ReadScope extends ContentContainerActiveRecord
{
    public static function tableName()
    {
        return '{{%sociolog_read_scope}}';
    }

    public function getDisplayName(): string
    {
        return Yii::t('SociologModule.base', 'Logbuch');
    }

    public function getDisplayNameSub(): string
    {
        return '';
    }

    public function createUrl($route = null, $params = [], $scheme = false)
    {
        return Url::to(['/sociolog/entry/view', 'id' => (int)$this->entry_id], $scheme);
    }

    public function getSettings(): ContentContainerSettingsManager
    {
        return new ContentContainerSettingsManager(['contentContainer' => $this]);
    }

    public function canAccessPrivateContent(?User $user = null)
    {
        $entry = Entry::findOne((int)$this->entry_id);
        return $entry !== null && ReadAccess::canRead($entry, $user);
    }
}
