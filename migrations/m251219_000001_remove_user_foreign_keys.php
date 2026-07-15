<?php

use yii\db\Migration;

class m251219_000001_remove_user_foreign_keys extends Migration
{
    public function safeUp()
    {
        $table = '{{%sociolog_entry}}';
        $schema = $this->db->schema->getTableSchema($table, true);

        if ($schema === null) {
            echo "Tabelle sociolog_entry nicht gefunden – übersprungen.\n";
            return;
        }

        foreach ($schema->foreignKeys as $fkName => $fkData) {
            // fkData[0] = referenzierte Tabelle
            if (in_array($fkName, [
                'fk_sociolog_entry_created_by',
                'fk_sociolog_entry_updated_by',
            ], true)) {
                echo "Entferne Foreign Key {$fkName}\n";
                $this->dropForeignKey($fkName, $table);
            }
        }
    }

    public function safeDown()
    {
        echo "Diese Migration wird bewusst nicht rückgängig gemacht.\n";
        return false;
    }
}
