<?php

use yii\db\Migration;

class m260316_220000_fix_decision_date extends Migration
{
    public function safeUp()
    {
        // 1️⃣ falsche Daten korrigieren
        $this->execute("
            UPDATE sociolog_entry
            SET decision_date = NULL
            WHERE decision_date = '0000-00-00'
        ");

        // 2️⃣ Spalte korrekt definieren
        $this->alterColumn(
            'sociolog_entry',
            'decision_date',
            $this->date()->null()->defaultValue(null)
        );
    }

    public function safeDown()
    {
        // optional – alten Zustand wiederherstellen
        $this->alterColumn(
            'sociolog_entry',
            'decision_date',
            $this->date()->notNull()
        );
    }
}