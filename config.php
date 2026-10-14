<?php

use humhub\modules\sociolog\Module;
use humhub\modules\sociolog\Events;
use humhub\widgets\TopMenu;
use humhub\modules\dashboard\widgets\Sidebar;
use humhub\commands\CronController;
use humhub\modules\sociolog\models\Entry;
use yii\db\ActiveRecord;
use humhub\modules\user\models\User;

return [
    'id' => 'sociolog',
    'class' => Module::class,
    'namespace' => 'humhub\modules\sociolog',
    'consoleControllerMap' => [
        'sociolog' => \humhub\modules\sociolog\commands\MaintenanceController::class,
    ],

    'events' => [
        [\yii\base\Application::class, \yii\base\Application::EVENT_BEFORE_REQUEST, [Events::class, 'onDigestBootstrap']],
        [\yii\queue\Queue::class, \yii\queue\Queue::EVENT_BEFORE_EXEC, [Events::class, 'onDigestBootstrap']],
        [\humhub\modules\admin\models\forms\UserDeleteForm::class,
            \yii\base\Model::EVENT_AFTER_VALIDATE, [Events::class, 'onUserDeletionFormValidated']],
        [\humhub\modules\user\models\forms\AccountDelete::class,
            \yii\base\Model::EVENT_AFTER_VALIDATE, [Events::class, 'onUserDeletionFormValidated']],
        [User::class, ActiveRecord::EVENT_BEFORE_UPDATE, [Events::class, 'onUserBeforeUpdate']],
        [\yii\queue\Queue::class, \yii\queue\Queue::EVENT_BEFORE_PUSH, [Events::class, 'onUserDeletionJobPush']],
        [\yii\queue\Queue::class, \yii\queue\Queue::EVENT_BEFORE_EXEC, [Events::class, 'onUserDeletionJobExecute']],
        ['class' => \humhub\modules\content\models\Content::class,
            'event' => ActiveRecord::EVENT_BEFORE_INSERT, 'callback' => [Events::class, 'onContentBeforeSave']],
        ['class' => \humhub\modules\content\models\Content::class,
            'event' => ActiveRecord::EVENT_BEFORE_UPDATE, 'callback' => [Events::class, 'onContentBeforeSave']],
        ['class' => \humhub\modules\stream\models\StreamQuery::class,
            'event' => \humhub\modules\stream\models\StreamQuery::EVENT_AFTER_FILTER,
            'callback' => [Events::class, 'onStreamAfterFilter']],


        // 🔹 TopMenu
        [
            'class' => TopMenu::class,
            'event' => TopMenu::EVENT_INIT,
            'callback' => [Events::class, 'onTopMenuInit'],
        ],

        // 🔹 Dashboard Sidebar Widget
        [
            'class' => Sidebar::class,
            'event' => Sidebar::EVENT_INIT,
            'callback' => [Events::class, 'onDashboardSidebarInit'],
        ],

        // 🔹 Täglicher HumHub-Cron
        [
			'class' => CronController::class,
			'event' => CronController::EVENT_ON_DAILY_RUN,
			'callback' => [Events::class, 'onCronRun'],
		],

        // 🔹 Benachrichtigungen bei Logbuch-Einträgen
        [
            'class' => Entry::class,
            'event' => ActiveRecord::EVENT_AFTER_INSERT,
            'callback' => [Events::class, 'onAfterSave'],
        ],
        [
            'class' => Entry::class,
            'event' => ActiveRecord::EVENT_AFTER_UPDATE,
            'callback' => [Events::class, 'onAfterSave'],
        ],
    ],
];
