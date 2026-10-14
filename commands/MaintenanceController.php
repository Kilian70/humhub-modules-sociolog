<?php

namespace humhub\modules\sociolog\commands;

use humhub\modules\sociolog\services\ReadScopeCleanupService;
use humhub\modules\sociolog\services\SociologStatusService;
use yii\console\Controller;
use yii\console\ExitCode;

/** Available only through HumHub's native console when Sociolog is enabled. */
class MaintenanceController extends Controller
{
    public function actionStatus(): int
    {
        $this->stdout('[' . date('Y-m-d H:i:s') . "] Sociolog Cron gestartet\n");
        SociologStatusService::run();
        $this->stdout('[' . date('Y-m-d H:i:s') . "] Sociolog Cron beendet\n");
        return ExitCode::OK;
    }

    /** List orphan IDs without changing any data. */
    public function actionReadScopes(): int
    {
        $count = 0;
        foreach (ReadScopeCleanupService::orphanQuery()->each(100) as $row) {
            $this->stdout(json_encode($row, JSON_THROW_ON_ERROR) . "\n");
            $count++;
        }
        $this->stdout("Verwaiste Lesecontainer: {$count}. Nur geprüft; keine Daten gelöscht.\n");
        return ExitCode::OK;
    }
}
