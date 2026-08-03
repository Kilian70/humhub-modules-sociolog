<?php

namespace humhub\modules\sociolog\assets;

use humhub\components\assets\AssetBundle;

/**
 * Sociolog AssetBundle
 * Lädt CSS & JS für das Logbuch-Modul (HumHub 1.18+).
 */
class SociologAsset extends AssetBundle
{
    /** 📂 Ordner mit Ressourcen relativ zum Modul */
    public $sourcePath = '@humhub/modules/sociolog/resources';

    /** 🎨 CSS-Dateien */
    public $css = [
        // Modul-CSS
        'css/sociolog.css',
        'css/icon.css',
    ];

    /** ⚙️ JavaScript-Dateien */
    public $js = [
        // Modul-JS
        'js/sociolog-search.js',
        'js/sociolog-form.js',
        'js/sociolog-decision-type-sort.js',

        // 🔽 DRUCK
        'js/sociolog-print.js',
        'js/sociolog-print-entry.js',
    ];

    /** 🔗 Abhängigkeiten */
    public $depends = [
        'humhub\assets\AppAsset',
    ];
}
