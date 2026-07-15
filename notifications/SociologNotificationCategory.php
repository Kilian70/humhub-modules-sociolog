<?php

namespace humhub\modules\sociolog\notifications;

use humhub\modules\notification\components\NotificationCategory;
use Yii;

class SociologNotificationCategory extends NotificationCategory
{
    public $id = 'sociolog';
    public $moduleId = 'sociolog';

    /**
     * Standard-Kanäle für Sociolog-Benachrichtigungen
     * -> Nur Glocke (Web) und Mail
     */
    public function getDefaultChannels()
    {
        return [
            self::CHANNEL_WEB,   // 🔔 Glocke im HumHub-Interface
            self::CHANNEL_MAIL,  // ✉️ Standard-Mail mit deinem Template
        ];
    }

    public function getTitle()
    {
        $moduleName = Yii::$app->getModule('sociolog')->getName();
        return Yii::t('SociologModule.base', $moduleName);
    }

    public function getDescription()
    {
        $moduleName = Yii::$app->getModule('sociolog')->getName();
        return Yii::t('SociologModule.base', 'Benachrichtigungen über neue oder geänderte Einträge im {module}-Modul.', [
            'module' => $moduleName,
        ]);
    }
}
