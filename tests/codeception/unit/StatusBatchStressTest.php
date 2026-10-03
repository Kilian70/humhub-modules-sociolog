<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\sociolog\models\DecisionType;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\services\SociologStatusService;
use sociolog\SociologTestCase;
use Yii;

class StatusBatchStressTest extends SociologTestCase
{
    private const DEFAULT_ENTRY_COUNT = 750;

    public function testStatusRunProcessesLargeLogbookInBatches(): void
    {
        $this->disableNotifications();
        $entryCount = max(
            1,
            (int)(getenv('SOCIOLOG_STRESS_ENTRY_COUNT') ?: self::DEFAULT_ENTRY_COUNT)
        );
        $decisionTypeId = (int)DecisionType::find()->select('id')->scalar();
        $this->assertGreaterThan(0, $decisionTypeId);

        $prefix = 'stress-' . bin2hex(random_bytes(6)) . '-';
        $rows = [];
        $now = time();

        for ($i = 0; $i < $entryCount; $i++) {
            $rows[] = [
                $prefix . $i,
                '4',
                'Stress test decision',
                $decisionTypeId,
                '2020-01-01',
                '2020-01-02',
                '2099-01-01',
                Entry::STATUS_PENDING,
                $now,
                $now,
            ];
        }

        Yii::$app->db->createCommand()->batchInsert(
            Entry::tableName(),
            [
                'title',
                'organ',
                'decision',
                'decision_type_id',
                'decision_date',
                'effective_date',
                'review_date',
                'status',
                'created_at',
                'updated_at',
            ],
            $rows
        )->execute();

        $prefixCondition = ['like', Entry::tableName() . '.title', $prefix];
        $this->assertSame(
            $entryCount,
            (int)Entry::find()->where($prefixCondition)->count(),
            'The stress fixture rows were not inserted.'
        );
        $this->assertSame(
            $entryCount,
            (int)Entry::find()->publishedOrLegacy()->andWhere($prefixCondition)->count(),
            'Legacy rows without HumHub content must be included in the status run.'
        );

        SociologStatusService::run();

        $updated = Entry::find()
            ->where($prefixCondition)
            ->andWhere(['status' => Entry::STATUS_VALID])
            ->count();

        $this->assertSame($entryCount, (int)$updated);
    }
}
