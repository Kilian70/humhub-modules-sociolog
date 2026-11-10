<?php

namespace humhub\modules\sociolog\models;

use humhub\components\ActiveRecord;
use humhub\interfaces\ViewableInterface;
use humhub\models\Setting;
use humhub\modules\user\helpers\UserHelper;
use humhub\modules\user\models\User;
use Yii;

/**
 * The board's existing module-setting row also owns its native rich-text uploads.
 * No second copy of the text and no additional database table are needed.
 */
class InfoBoard extends ActiveRecord implements ViewableInterface
{
    public static function tableName()
    {
        return Setting::tableName();
    }

    public static function current(): ?self
    {
        return static::findOne(['module_id' => 'sociolog', 'name' => 'infoBoardText']);
    }

    private function isBoardSetting(): bool
    {
        return !$this->isNewRecord && $this->module_id === 'sociolog' && $this->name === 'infoBoardText';
    }

    public function canView($user = null): bool
    {
        $user = UserHelper::getUserByParam($user);
        if (!$this->isBoardSetting() || !$user || (int)$user->status !== User::STATUS_ENABLED) {
            return false;
        }
        $module = Yii::$app->getModule('sociolog');
        return $module !== null && ($user->isSystemAdmin(false) || (bool)$module->settings->get('infoPageEnabled', false));
    }

    public function canEdit($user = null): bool
    {
        $user = UserHelper::getUserByParam($user);
        return $this->isBoardSetting() && $user !== null
            && (int)$user->status === User::STATUS_ENABLED && $user->isSystemAdmin(false);
    }
}
