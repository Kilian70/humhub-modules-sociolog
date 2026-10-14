<?php
namespace humhub\modules\sociolog\upgrade;

/** CLI updater only: prevent external-module initialization against an old schema. */
class CoreOnlyModuleManager extends \humhub\components\ModuleManager
{
    public function register(string $basePath, ?array $config = null)
    {
        if ($config === null && is_file($basePath . '/config.php')) $config = include $basePath . '/config.php';
        if (empty($config['isCoreModule']) && empty($config['isInstallerModule'])) return;
        return parent::register($basePath, $config);
    }
}
