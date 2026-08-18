<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$readJson = static function (string $file) use (&$errors): array {
    try {
        return json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) {
        $errors[] = basename($file) . ': ' . $exception->getMessage();
        return [];
    }
};

$module = $readJson($root . '/module.json');
$composer = $readJson($root . '/composer.json');

foreach (['id', 'name', 'description', 'version', 'humhub', 'licence'] as $field) {
    if (!array_key_exists($field, $module)) {
        $errors[] = "module.json: required field '{$field}' is missing.";
    }
}

$version = (string)($module['version'] ?? '');
if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
    $errors[] = 'module.json: version must use the X.Y.Z format.';
}

if (($composer['version'] ?? null) !== $version) {
    $errors[] = 'composer.json and module.json versions do not match.';
}

$moduleClass = (string)file_get_contents($root . '/Module.php');
if (!preg_match('/public\s+string\s+\$version\s*=\s*[\'\"]([^\'\"]+)[\'\"]/', $moduleClass, $matches)
    || ($matches[1] ?? null) !== $version) {
    $errors[] = 'Module.php and module.json versions do not match.';
}

$readme = (string)file_get_contents($root . '/README.md');
if (!str_contains($readme, '**Version:** ' . $version)) {
    $errors[] = 'README.md does not contain the current module version.';
}

if (($module['licence'] ?? null) !== 'AGPL-3.0-or-later') {
    $errors[] = 'module.json must use licence=AGPL-3.0-or-later.';
}

if (isset($module['license'])) {
    $errors[] = 'module.json uses license; HumHub expects licence.';
}

if (($composer['license'] ?? null) !== 'AGPL-3.0-or-later') {
    $errors[] = 'composer.json must use license=AGPL-3.0-or-later.';
}

if (($composer['require']['php'] ?? null) !== '>=8.2') {
    $errors[] = 'composer.json must require PHP >=8.2 for HumHub 1.18+.';
}

if (($module['humhub']['minVersion'] ?? null) !== '1.18.0') {
    $errors[] = 'The declared minimum HumHub version must remain 1.18.0.';
}

$fallbackCron = (string)file_get_contents($root . '/run.php');
if (!str_contains($fallbackCron, "PHP_SAPI !== 'cli'")) {
    $errors[] = 'run.php must reject non-CLI requests.';
}

$importController = (string)file_get_contents($root . '/controllers/ImportController.php');
if (!str_contains($importController, 'PREVIEW_MAX_AGE = 86400')
    || !str_contains($importController, 'cleanupExpiredPreviews()')) {
    $errors[] = 'Import previews must expire after 24 hours.';
}

$userDeletionServiceFile = $root . '/services/SociologUserDeletionService.php';
$userDeletionService = is_file($userDeletionServiceFile)
    ? (string)file_get_contents($userDeletionServiceFile)
    : '';
if (!str_contains($moduleClass, 'User::EVENT_BEFORE_DELETE')
    || !str_contains($moduleClass, "'onUserDelete'")
    || !str_contains($userDeletionService, 'Content::updateAll')
    || !str_contains($userDeletionService, 'archiveUserId')) {
    $errors[] = 'The user deletion protection for institutional logbook entries is incomplete.';
}

$config = (string)file_get_contents($root . '/config.php');
$entryController = (string)file_get_contents($root . '/controllers/EntryController.php');
$entryModel = (string)file_get_contents($root . '/models/Entry.php');
$settingsForm = (string)file_get_contents($root . '/models/SettingsForm.php');
$events = (string)file_get_contents($root . '/Events.php');
$runtimeWorkflow = (string)file_get_contents($root . '/.github/workflows/runtime-tests.yml');
$moduleChecksWorkflow = (string)file_get_contents($root . '/.github/workflows/module-checks.yml');
$lifecycleTest = (string)file_get_contents($root . '/tests/codeception/unit/EntryLifecycleTest.php');
$migrationTest = (string)file_get_contents($root . '/tests/codeception/unit/MigrationSchemaTest.php');

if (!str_contains($config, 'ActiveRecord::EVENT_AFTER_INSERT')
    || !str_contains($config, 'ActiveRecord::EVENT_AFTER_UPDATE')
    || str_contains($moduleClass, 'ActiveRecord::EVENT_AFTER_INSERT')
    || str_contains($moduleClass, 'ActiveRecord::EVENT_AFTER_UPDATE')) {
    $errors[] = 'Entry events must be registered exactly through config.php.';
}

if (!str_contains($entryController, 'Space::findOne((int)$model->current_organ)')
    || !str_contains($entryController, '$model->isAwaitingTakeover()')
    || !str_contains($entryModel, 'function isAwaitingTakeover()')
    || !str_contains($entryModel, 'function getOrganSpaceId(Organ $organ)')
    || !str_contains($entryModel, "'is_organ_space' => 1")) {
    $errors[] = 'Forwarding and takeover must use the stable target Space ID.';
}

if (!str_contains($events, '->distinct()')
    || !str_contains($events, '->each(200)')
    || !str_contains($events, "['<>', 'user.id', (int)\$actor->id]")) {
    $errors[] = 'Notification recipients must be unique, batched and exclude the actor.';
}

if (!str_contains($settingsForm, "NOTIFICATION_MODE_NONE = 'none'")
    || !str_contains($settingsForm, "NOTIFICATION_MODE_GROUPS = 'groups'")
    || !str_contains($settingsForm, "NOTIFICATION_MODE_SPACE = 'space'")
    || !str_contains($settingsForm, "NOTIFICATION_MODE_ALL = 'all'")
    || !str_contains($settingsForm, 'validateNotificationGroups')
    || !str_contains($events, "'notificationRecipientMode'")
    || !str_contains($events, 'Membership::STATUS_MEMBER')) {
    $errors[] = 'The explicit notification recipient modes are incomplete.';
}

if (!str_contains($runtimeWorkflow, 'humhub-branch: v1.18.4')
    || !str_contains($runtimeWorkflow, 'humhub-branch: v1.19.0-beta.1')
    || !str_contains($lifecycleTest, 'testCreateEditAndSoftDelete')
    || !str_contains($lifecycleTest, 'testConfiguredWriteAndDeleteRights')
    || !str_contains($migrationTest, 'testCompleteMigrationResult')) {
    $errors[] = 'HumHub runtime coverage for lifecycle, permissions and migrations is incomplete.';
}

if (!str_contains($moduleChecksWorkflow, "php-version: ['8.2', '8.3']")
    || str_contains($moduleChecksWorkflow, "php-version: ['8.1'")) {
    $errors[] = 'Static module checks must use the supported PHP 8.2 and 8.3 matrix.';
}

if (is_file($root . '/permissions/ViewEntry.php')
    || str_contains($moduleClass, 'ViewEntry')
    || str_contains((string)file_get_contents($root . '/module.json'), 'ViewEntry')) {
    $errors[] = 'The obsolete ViewEntry permission is registered again.';
}

$scanPaths = ['assets', 'controllers', 'resources', 'views', 'widgets'];
$forbiddenPatterns = [
    'cdn.datatables.net',
    'cdn.jsdelivr.net',
    'cdnjs.cloudflare.com',
    'fa-solid',
];

foreach ($scanPaths as $scanPath) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root . '/' . $scanPath, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'js', 'css'], true)) {
            continue;
        }

        $contents = (string)file_get_contents($file->getPathname());
        foreach ($forbiddenPatterns as $pattern) {
            if (str_contains($contents, $pattern)) {
                $errors[] = $file->getPathname() . ": forbidden external/legacy asset reference '{$pattern}'.";
            }
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "Module validation failed:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo "Sociolog module metadata and asset policy are valid (version {$version}).\n";
