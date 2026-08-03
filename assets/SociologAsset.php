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
        // DataTables (Bootstrap 5)
        'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css',

        // Das Modul verwendet Font-Awesome-6-Klassen (fa-solid).
        // HumHubs gebündelte Icon-Version deckt diese nicht vollständig ab.
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',

        // Modul-CSS
        'css/sociolog.css',
        'css/icon.css',
    ];

    /** ⚙️ JavaScript-Dateien */
    public $js = [
        // DataTables
        'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
        'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js',

        // Modul-JS
        'js/sociolog-search.js',
        'js/sociolog-table.js',
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
