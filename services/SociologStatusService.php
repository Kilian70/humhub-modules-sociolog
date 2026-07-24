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

        $rows = (new \yii\db\Query())
            ->select([
				'id',
				'status',
				'published_at',
				'effective_date',
				'review_date',
				'forwarded_to'
			])
            ->from(Entry::tableName())
            ->where(['!=', 'status', 'expired'])
            ->all($db);

        $total = count($rows);
        $updated = 0;

        foreach ($rows as $row) {

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

    private static function calculateStatus(array $row, DateTimeImmutable $today): ?string
{
    $status = (string)($row['status'] ?? '');

		// Wenn weitergeleitet → Status nicht automatisch ändern
		if (!empty($row['forwarded_to'])) {
			return null;
		}

    // Endzustand: nie automatisch ändern
    if (Entry::isManualProtectedStatus($status)) {
        return null;
    }

    $effective = !empty($row['effective_date'])
        ? new DateTimeImmutable((string)$row['effective_date'])
        : null;

    // 🔹 Fallback: effective_date aus published_at berechnen
    if ($effective === null && !empty($row['published_at'])) {

        $days = (int)Yii::$app->getModule('sociolog')->settings->get('defaultEffectiveDays', 10);
        $addExtraDay = (bool)Yii::$app->getModule('sociolog')
            ->settings
            ->get('effectiveDateAddExtraDay', true);

        $published = new DateTimeImmutable((string)$row['published_at']);

        $effective = $published->modify("+{$days} days");
        if ($addExtraDay) {
            $effective = $effective->modify('+1 day');
        }
    }

    $review = !empty($row['review_date'])
        ? new DateTimeImmutable((string)$row['review_date'])
        : null;

        // optional: auto → pending / valid
        if ($status === 'auto') {
            $status = $effective ? 'pending' : 'valid';
        }

        // pending → valid
        if ($status === 'pending' && $effective && $effective <= $today) {
            $status = 'valid';
        }

        // Review-Datum bestimmen
        if ($review === null && $effective !== null) {
            $review = $effective->modify('+2 years');
        }

        // valid → review (nur valid!)
        if ($status === 'valid' && $review !== null && $review <= $today) {
            $status = 'review';
        }

        return $status;
    }

}
