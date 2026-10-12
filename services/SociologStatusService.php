<?php
declare(strict_types=1);

namespace humhub\modules\sociolog\services;

use Yii;
use DateTimeImmutable;
use humhub\modules\sociolog\models\Entry;

class SociologStatusService
{

public static function run(): void
{
    $start = microtime(true);

    try {

        file_put_contents(
            Yii::getAlias('@runtime/sociolog-cron.log'),
            date('Y-m-d H:i:s') . " Sociolog Daily Cron läuft\n",
            FILE_APPEND
        );

        $db = Yii::$app->db;
        $today = new DateTimeImmutable('today');

        $rows = Entry::find()
            ->publishedOrLegacy(false)
            ->select([
				Entry::tableName() . '.id',
				Entry::tableName() . '.status',
				Entry::tableName() . '.published_at',
				Entry::tableName() . '.effective_date',
				Entry::tableName() . '.review_date',
				Entry::tableName() . '.forwarded_to'
			])
            ->andWhere(['!=', Entry::tableName() . '.status', 'expired'])
            ->asArray();

        $total = 0;
        $updated = 0;

        // In Batches lesen, damit der tägliche Lauf auch bei grossen
        // Logbüchern keinen proportional wachsenden PHP-Speicher benötigt.
        foreach ($rows->each(500) as $row) {
            $total++;

			// Wenn Entscheid weitergeleitet ist → Status NICHT ändern
			if (!empty($row['forwarded_to'])) {
				continue;
			}

            $newStatus = self::calculateStatus($row, $today);

            if ($newStatus !== null && $newStatus !== $row['status']) {

                $db->createCommand()
                    ->update(
                        Entry::tableName(),
                        ['status' => $newStatus],
                        ['id' => (int)$row['id']]
                    )
                    ->execute();

                $updated++;
            }
        }

        Yii::info(
            "Cron SociologStatusService: {$total} geprüft / {$updated} geändert",
            'sociolog.cron'
        );

        Yii::$app->getModule('sociolog')->settings->set(
            'lastStatusRun',
            date('Y-m-d H:i:s')
        );

        Yii::$app->getModule('sociolog')->settings->set(
			'lastStatusRunSuccess',
			true
		);
		
		Yii::$app->getModule('sociolog')->settings->set(
			'lastStatusRunError',
			null
		);

    } catch (\Throwable $e) {

        Yii::$app->getModule('sociolog')->settings->set(
            'lastStatusRunSuccess',
            false
        );

        Yii::$app->getModule('sociolog')->settings->set(
            'lastStatusRunError',
            $e->getMessage()
        );

        throw $e;
    }

    Yii::$app->getModule('sociolog')->settings->set(
        'lastStatusRunDuration',
        round(microtime(true) - $start, 2)
    );
}

    public static function calculateStatus(array $row, DateTimeImmutable $today): ?string
    {
        $status = (string)($row['status'] ?? '');
        if (!empty($row['forwarded_to']) || Entry::isManualProtectedStatus($status)) {
            return null;
        }
        $effective = !empty($row['effective_date']) ? new DateTimeImmutable((string)$row['effective_date']) : null;
        if ($effective === null && !empty($row['published_at'])) {
            $effective = (new DateTimeImmutable((string)$row['published_at']))->modify('+' . self::effectiveDays() . ' days');
        }
        $review = !empty($row['review_date']) ? new DateTimeImmutable((string)$row['review_date']) : $effective?->modify('+2 years');
        // MySQL DATE_ADD clamps leap days; use the same calendar rule in PHP.
        if (empty($row['review_date']) && $effective !== null && $review !== null
            && $review->format('m') !== $effective->format('m')) {
            $review = $review->modify('last day of previous month');
        }
        if ($review !== null && $review <= $today) {
            return Entry::STATUS_REVIEW;
        }
        if ($effective !== null) {
            return $effective > $today ? Entry::STATUS_PENDING : Entry::STATUS_VALID;
        }
        return $status;
    }

    private static function effectiveDays(): int
    {
        $settings = Yii::$app->getModule('sociolog')->settings;
        return (int)$settings->get('defaultEffectiveDays', 10)
            + ((bool)$settings->get('effectiveDateAddExtraDay', true) ? 1 : 0);
    }

    /** MySQL expression matching calculateStatus(), including legacy date fallbacks. */
    public static function statusExpression(): \yii\db\Expression
    {
        $table = Yii::$app->db->quoteTableName(Entry::tableName());
        $days = self::effectiveDays();
        $effective = "COALESCE({$table}.effective_date, DATE_ADD({$table}.published_at, INTERVAL {$days} DAY))";
        $review = "COALESCE({$table}.review_date, DATE_ADD({$effective}, INTERVAL 2 YEAR))";
        return new \yii\db\Expression(
            "CASE WHEN {$table}.status IN ('expired', 'objection', 'replaced') "
            . "OR COALESCE({$table}.forwarded_to, '') <> '' THEN {$table}.status "
            . "WHEN {$review} <= :sociologStatusToday THEN 'review' "
            . "WHEN {$effective} > :sociologStatusToday THEN 'pending' "
            . "WHEN {$effective} <= :sociologStatusToday THEN 'valid' "
            . "ELSE {$table}.status END",
            [':sociologStatusToday' => date('Y-m-d')]
        );
    }
}
