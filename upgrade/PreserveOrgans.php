<?php
namespace humhub\modules\sociolog\upgrade;

require_once dirname(__DIR__) . '/migrations/m260314_130000_create_sociolog_organ_table.php';

/** Execute original additive schema steps, retain the raw legacy area column. */
class PreserveOrgans extends \m260314_130000_create_sociolog_organ_table
{
    public function safeUp()
    {
        \humhub\modules\sociolog\services\LegacyUpgradeService::snapshot();
        return parent::safeUp();
    }
    public function dropColumn($table, $column)
    {
        if ($table === 'sociolog_space_config' && $column === 'bereich') {
            echo "Legacy-Bereichswerte bleiben erhalten; Organzuordnung erfolgt ausdrücklich manuell.\n";
            return;
        }
        throw new \RuntimeException('Unerwarteter Spalten-Löschschritt im geschützten Update.');
    }
    public function safeDown() { return false; }
}
