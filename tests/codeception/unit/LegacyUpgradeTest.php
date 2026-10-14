<?php
namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\sociolog\services\LegacyUpgradeService;
use humhub\modules\user\models\User;
use sociolog\SociologTestCase;
use Yii;
use yii\db\Query;

class LegacyUpgradeTest extends SociologTestCase
{
    private function withPendingSettings(callable $callback): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand()->delete('migration', ['version' => LegacyUpgradeService::SETTINGS])->execute();
            $callback();
        } finally { $transaction->rollBack(); }
    }

    public function testLegacyIdsAndSerializedSelectionsResolveWithoutDeletingRows(): void
    {
        $this->withPendingSettings(function () {
            $user = User::findOne(2);
            Yii::$app->getModule('sociolog')->settings->set('writerUsers', serialize([$user->id, $user->guid]));
            Yii::$app->getModule('sociolog')->settings->set('writerGroups', json_encode(['1']));
            $before = (new Query())->from('setting')->where(['module_id' => 'sociolog'])->all();
            $plan = LegacyUpgradeService::settingsPlan();
            $byName = array_column(array_map(fn($p) => ['name' => $p['row']['name'], 'value' => json_decode($p['value'], true)], $plan), 'value', 'name');
            $this->assertSame([$user->guid, $user->guid], $byName['writerUsers']);
            $this->assertSame([1], $byName['writerGroups']);
            $this->assertSame($before, (new Query())->from('setting')->where(['module_id' => 'sociolog'])->all());
        });
    }

    public function testUnknownFormatAndUnresolvableIdsAbortWithoutWrites(): void
    {
        $this->withPendingSettings(function () {
            foreach (['not an array', serialize([99999999]), serialize([new \stdClass()])] as $raw) {
                Yii::$app->getModule('sociolog')->settings->set('writerUsers', $raw);
                $before = (new Query())->from('setting')->where(['module_id' => 'sociolog'])->all();
                try { LegacyUpgradeService::settingsPlan(); $this->fail('Invalid selection accepted'); }
                catch (\RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
                $this->assertSame($before, (new Query())->from('setting')->where(['module_id' => 'sociolog'])->all());
            }
        });
    }

    public function testModuleRouteRejectsUnprotectedPendingHistory(): void
    {
        $this->withPendingSettings(function () {
            $before = (new Query())->from('migration')->orderBy('version')->all();
            try { Yii::$app->getModule('sociolog')->getMigrationService()->migrateUp(); $this->fail('Unsafe route accepted'); }
            catch (\yii\base\InvalidConfigException $e) { $this->assertStringContainsString('upgrade.php', $e->getMessage()); }
            $this->assertSame($before, (new Query())->from('migration')->orderBy('version')->all());
            $enabled = (new Query())->from('module_enabled')->all();
            try { Yii::$app->getModule('sociolog')->enable(); $this->fail('Unsafe activation accepted'); }
            catch (\yii\base\InvalidConfigException $e) { $this->assertStringContainsString('upgrade.php', $e->getMessage()); }
            $this->assertSame($enabled, (new Query())->from('module_enabled')->all());
        });
    }

    public function testRawArchiveIsAdditiveAndNeverOverwritesOriginalSnapshot(): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->getModule('sociolog')->settings->set('legacyArchiveProbe', 'Original & unverändert');
            $row = (new Query())->from('setting')->where(['module_id'=>'sociolog','name'=>'legacyArchiveProbe'])->one();
            LegacyUpgradeService::snapshot();
            $archive = (new Query())->from('sociolog_upgrade_archive')->where(['source_table'=>'setting','source_id'=>$row['id']])->one();
            $this->assertSame($row, json_decode($archive['payload'], true));
            Yii::$app->getModule('sociolog')->settings->set('legacyArchiveProbe', 'Later value');
            LegacyUpgradeService::snapshot();
            $this->assertSame($archive, (new Query())->from('sociolog_upgrade_archive')->where(['id'=>$archive['id']])->one());
            $this->assertSame('Later value', (new Query())->select('value')->from('setting')->where(['id'=>$row['id']])->scalar());
        } finally { $transaction->rollBack(); }
    }
    public function testControlledGuardFlagIsClearedEvenWhenMigrationThrows(): void
    {
        $seen = false;
        $migration = new class extends \yii\db\Migration {
            public $observe;
            public function up() { ($this->observe)(); throw new \RuntimeException('Synthetic guard failure'); }
        };
        $migration->observe = function () use (&$seen) { $seen = LegacyUpgradeService::$controlledUpdate; };
        $controller = new class('probe', Yii::$app) extends \humhub\modules\sociolog\commands\SafeUpdateController {
            public $stub;
            protected function createMigration($class): \yii\db\MigrationInterface { return $this->stub; }
            public function probe(): void { $this->migrateUp('m260228_175900_guard_legacy_upgrade'); }
        };
        $controller->stub = $migration;
        ob_start();
        try {
            try { $controller->probe(); $this->fail('Expected synthetic failure'); }
            catch (\RuntimeException $e) { $this->assertSame('Synthetic guard failure', $e->getMessage()); }
            $this->assertTrue($seen);
            $this->assertFalse(LegacyUpgradeService::$controlledUpdate);
        } finally { ob_end_clean(); LegacyUpgradeService::$controlledUpdate = false; }
    }

    public function testPartialOrganSchemaStopsBeforeAnyDataChanges(): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand()->delete('migration', ['version'=>LegacyUpgradeService::ORGANS])->execute();
            $before = (new Query())->from('sociolog_upgrade_archive')->orderBy('id')->all();
            try { LegacyUpgradeService::validateSchema(); $this->fail('Partial schema accepted'); }
            catch (\RuntimeException $e) { $this->assertStringContainsString('Teilweise Organmigration', $e->getMessage()); }
            $this->assertSame($before, (new Query())->from('sociolog_upgrade_archive')->orderBy('id')->all());
        } finally { $transaction->rollBack(); }
    }

}
