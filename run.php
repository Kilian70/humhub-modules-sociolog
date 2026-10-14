#!/usr/bin/env php
<?php
error_reporting(E_ALL & ~E_DEPRECATED);

// Dieses Fallback fuehrt administrative Wartungslogik aus und darf daher
// ausschliesslich durch PHP auf der Kommandozeile gestartet werden. Die
// regulaere HumHub-Installation sperrt /protected bereits serverseitig;
// diese Pruefung ist eine zusaetzliche Absicherung bei Fehlkonfigurationen.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

// Das Modul liegt regulär unter protected/modules/sociolog.
$protected = dirname(__DIR__, 2);
$console = $protected . '/yii';

if (!is_file($console) || !is_file($protected . '/vendor/autoload.php')) {
    fwrite(STDERR, "[ERROR] HumHub-Konsole wurde relativ zum Modulpfad nicht gefunden.\n");
    exit(1);
}

if (!chdir($protected)) {
    fwrite(STDERR, "[ERROR] HumHub-Konsole konnte nicht gestartet werden.\n");
    exit(1);
}

// Native bootstrap: .env, gemeinsame/lokale Konfiguration, Aliase und Module.
// Fester Statusbefehl; keine fremden Wartungsbefehle aus Argumenten übernehmen.
$argv = [$console, 'sociolog/status'];
$argc = count($argv);
$_SERVER['argv'] = $argv;
$_SERVER['argc'] = $argc;
require $console;
