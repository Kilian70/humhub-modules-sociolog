#!/usr/bin/env php
<?php
// Explicit fixed console route; default is a read-only pending-migration check.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
if ($argc > 2 || ($argc === 2 && !in_array($argv[1], ['--apply', '--areas'], true))) {
    fwrite(STDERR, "Aufruf: php upgrade.php [--apply|--areas]\n"); exit(1);
}
$action = $argc === 2 ? ($argv[1] === '--apply' ? 'up' : 'legacy-areas') : 'new';
$protected = dirname(__DIR__, 2);
if (!is_file($protected . '/yii') || !is_file($protected . '/vendor/autoload.php')) {
    fwrite(STDERR, "HumHub-Konsole nicht gefunden.\n"); exit(1);
}
$loader = require $protected . '/vendor/autoload.php';
$loader->addPsr4('humhub\\modules\\sociolog\\', __DIR__ . '/');
$_ENV['HUMHUB_CLI_CONFIG__CONTROLLER_MAP__SOCIOLOGSAFEUPDATE'] = \humhub\modules\sociolog\commands\SafeUpdateController::class;
$_ENV['HUMHUB_CLI_CONFIG__COMPONENTS__MODULE_MANAGER__CLASS'] = \humhub\modules\sociolog\upgrade\CoreOnlyModuleManager::class;
$argv = [$protected . '/yii', 'sociologsafeupdate/' . $action, '--interactive=0'];
$argc = count($argv); $_SERVER['argv'] = $argv; $_SERVER['argc'] = $argc;
chdir($protected);
require $protected . '/yii';
