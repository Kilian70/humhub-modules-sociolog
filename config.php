<?php

use humhub\modules\sociolog\Module;
use humhub\modules\sociolog\Events;
use humhub\widgets\TopMenu;
use humhub\modules\dashboard\widgets\Sidebar;
use humhub\commands\CronController;

return [
    'id' => 'sociolog',
    'class' => Module::class,
    'namespace' => 'humhub\modules\sociolog',

    'events' => [

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
    ],
];
