<?php

namespace humhub\modules\sociolog\widgets;

use Yii;
use yii\base\Widget;
use humhub\modules\sociolog\models\Entry;

/**
 * ============================================================
 * 🔹 Widget: "Neueste Beschlüsse" für das Dashboard
 * ------------------------------------------------------------
 * - Zeigt die letzten Einträge aus dem Logbuch-Modul (Sociolog)
 * - Anzahl wird aus den Modul-Einstellungen gelesen
 * - Kompatibel mit HumHub 1.18+ (Bootstrap 5)
 * ============================================================
 */
class LatestEntries extends Widget
{
    /** @var int Anzahl der Einträge, die im Dashboard angezeigt werden */
    public $limit = 3;

    /**
     * 🔧 Initialisierung
     */
    public function init()
{
    parent::init();

    // Modul-Einstellungen laden
    $settings = Yii::$app->getModule('sociolog')->settings;

    // Richtigen Setting-Key lesen (aus Admin-Formular)
    $this->limit = (int)($settings->get('latestEntriesLimit', 3) ?? 3);

    // Sicherheitsrahmen 1–50
    $this->limit = max(1, min($this->limit, 50));

    Yii::info("📊 Sociolog: Dashboard zeigt {$this->limit} Einträge", 'sociolog.widgets');
}


    /**
     * 🔁 Ausführung (Widget-Ausgabe)
     */
    public function run()
    {
        // 🔹 Lade Einträge nach Beschlussdatum (neueste zuerst)
        $entries = Entry::find()
            ->orderBy(['decision_date' => SORT_DESC])
            ->limit($this->limit)
            ->all();

        return $this->render('latestEntries', [
            'entries' => $entries,
        ]);
    }
}
