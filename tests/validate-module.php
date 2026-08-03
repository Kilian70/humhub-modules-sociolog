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

if (($module['humhub']['minVersion'] ?? null) !== '1.18.0') {
    $errors[] = 'The declared minimum HumHub version must remain 1.18.0.';
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
