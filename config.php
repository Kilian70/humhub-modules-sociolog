<?php

use humhub\modules\sociolog\Module;
use humhub\modules\sociolog\Events;
use humhub\widgets\TopMenu;
use humhub\modules\dashboard\widgets\Sidebar;
use humhub\commands\CronController;
use humhub\modules\sociolog\models\Entry;
use yii\db\ActiveRecord;

return [
    'id' => 'sociolog',
    'class' => Module::class,
    'namespace' => 'humhub\modules\sociolog',

    'events' => [
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
