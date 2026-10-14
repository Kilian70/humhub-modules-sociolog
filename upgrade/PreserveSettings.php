<?php
namespace humhub\modules\sociolog\upgrade;

use humhub\components\Migration;
use humhub\modules\sociolog\services\LegacyUpgradeService;

/** Safe replacement for F6, tracked under its original history identifier. */
class PreserveSettings extends Migration
{
    public function safeUp()
    {
        $plan = LegacyUpgradeService::settingsPlan(); // Validate everything before writes.
        LegacyUpgradeService::snapshot();
        foreach ($plan as $item) {
            $this->update('setting', ['value' => $item['value']], ['id' => $item['row']['id']]);
        }
        return true;
    }
    public function safeDown() { return false; }
}
