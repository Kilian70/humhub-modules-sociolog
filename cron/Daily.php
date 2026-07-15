<?php

namespace humhub\modules\sociolog\cron;

use humhub\modules\sociolog\services\SociologStatusService;

class Daily
{
    public static function run()
    {
        SociologStatusService::run();
    }
}