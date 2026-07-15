#!/usr/bin/env php
<?php
error_reporting(E_ALL & ~E_DEPRECATED);

define('YII_DEBUG', false);
define('YII_ENV', 'prod');

echo "[" . date('Y-m-d H:i:s') . "] Sociolog Cron gestartet\n";

// Das Modul liegt regulär unter protected/modules/sociolog.
$protected = dirname(__DIR__, 2);
$autoload = $protected . '/vendor/autoload.php';
$yiiBootstrap = $protected . '/vendor/yiisoft/yii2/Yii.php';
$consoleConfig = $protected . '/config/console.php';

if (!is_file($autoload) || !is_file($yiiBootstrap) || !is_file($consoleConfig)) {
    fwrite(STDERR, "[ERROR] HumHub-Konsole wurde relativ zum Modulpfad nicht gefunden.\n");
    exit(1);
}

chdir($protected);

require_once $autoload;
require_once $yiiBootstrap;

$config = require $consoleConfig;

if (empty($config['components']['db'])) {
    $dyn = require $protected . '/config/dynamic.php';
    $config['components']['db'] = $dyn['components']['db'];
}

new yii\console\Application([
    'id'         => 'sociolog-cron',
    'basePath'   => $protected,
    'vendorPath' => $protected . '/vendor',
    'components' => $config['components'],
]);

try {
    \humhub\modules\sociolog\services\SociologStatusService::run();
    echo "[" . date('Y-m-d H:i:s') . "] Sociolog Cron beendet\n";
} catch (Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
