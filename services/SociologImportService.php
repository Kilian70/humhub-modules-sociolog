<?php

namespace humhub\modules\sociolog\services;

use DateTimeImmutable;
use Yii;
use humhub\modules\content\models\Content;
use humhub\modules\sociolog\models\DecisionType;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\space\models\Space;

/**
 * Sicherer Einmalimport historischer Logbuch-Einträge.
 */
class SociologImportService
{
    private const REQUIRED_COLUMNS = [
        'source_sheet',
        'source_row',
        'target_organ',
        'decision_type',
        'title',
        'decision',
        'decision_date',
        'review_date',
    ];

    public static function preview(string $csvPath): array
    {
        $handle = fopen($csvPath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('CSV file could not be opened.');
        }

        try {
            $headerLine = fgets($handle);
            if (!is_string($headerLine)) {
                throw new \RuntimeException('CSV header is missing.');
            }

            $delimiter = null;
            $header = [];
            foreach ([',', ';', "\t"] as $candidate) {
                $candidateHeader = str_getcsv($headerLine, $candidate, '"', '\\');
                $candidateHeader = array_map([self::class, 'normalizeHeader'], $candidateHeader);
                if (array_diff(self::REQUIRED_COLUMNS, $candidateHeader) === []) {
                    $delimiter = $candidate;
                    $header = $candidateHeader;
                    break;
                }
            }

            if ($delimiter === null) {
                $detected = array_map(
                    [self::class, 'normalizeHeader'],
                    str_getcsv($headerLine, ',', '"', '\\')
                );
                throw new \RuntimeException(
                    'CSV header does not match the prepared import format. Detected: '
                    . implode(' | ', $detected)
                );
            }

            $rows = [];
            $line = 1;

            while (($values = fgetcsv($handle, null, $delimiter, '"', '\\')) !== false) {
                $line++;
                if (count($rows) >= 500) {
                    throw new \RuntimeException('CSV contains more than 500 rows.');
                }

                if (count(array_filter($values, static fn($v): bool => trim((string)$v) !== '')) === 0) {
                    continue;
                }

                $values = array_pad($values, count($header), '');
                $raw = array_combine($header, array_slice($values, 0, count($header)));
                if (!is_array($raw)) {
                    continue;
                }

                $rows[] = self::validateRow($raw, $line);
            }
        } finally {
            fclose($handle);
        }

        return [
            'rows' => $rows,
            'ready' => count(array_filter($rows, static fn(array $row): bool => $row['ready'])),
            'duplicates' => count(array_filter($rows, static fn(array $row): bool => $row['duplicate'])),
            'errors' => count(array_filter($rows, static fn(array $row): bool => !$row['ready'] && !$row['duplicate'])),
        ];
    }

    private static function validateRow(array $raw, int $csvLine): array
    {
        $clean = [];
        foreach (self::REQUIRED_COLUMNS as $column) {
            $clean[$column] = trim((string)($raw[$column] ?? ''));
        }

        $errors = [];
        $space = null;
        $decisionType = null;

        if ($clean['title'] === '') {
            $errors[] = Yii::t('SociologModule.base', 'Titel fehlt.');
        }
        if ($clean['decision'] === '') {
            $errors[] = Yii::t('SociologModule.base', 'Beschluss fehlt.');
        }
        if (!self::isDate($clean['decision_date'])) {
            $errors[] = Yii::t('SociologModule.base', 'Veröffentlichungsdatum ist ungültig.');
        }
        if ($clean['review_date'] !== '' && !self::isDate($clean['review_date'])) {
            $errors[] = Yii::t('SociologModule.base', 'Überprüfungsdatum ist ungültig.');
        }

        $spaces = Space::find()->where(['name' => $clean['target_organ']])->all();
        if (count($spaces) !== 1) {
            $errors[] = count($spaces) === 0
                ? Yii::t('SociologModule.base', 'Ziel-Space wurde nicht gefunden.')
                : Yii::t('SociologModule.base', 'Der Name des Ziel-Spaces ist nicht eindeutig.');
        } else {
            $space = $spaces[0];
            $enabled = SpaceConfig::find()
                ->where(['space_id' => (int)$space->id, 'enabled' => 1])
                ->exists();
            if (!$enabled) {
                $errors[] = Yii::t('SociologModule.base', 'Der Ziel-Space ist nicht als Logbuch-Organ aktiviert.');
            }
        }

        $decisionType = DecisionType::find()
            ->where(['name' => $clean['decision_type']])
            ->one();
        if (!$decisionType) {
            $errors[] = Yii::t('SociologModule.base', 'Entscheidungstyp wurde nicht gefunden.');
        }

        $duplicate = false;
        if ($space && self::isDate($clean['decision_date']) && $clean['title'] !== '') {
            $duplicate = Entry::find()->where([
                'organ' => (int)$space->id,
                'decision_date' => $clean['decision_date'],
                'title' => $clean['title'],
            ])->exists();
        }

        return [
            'csvLine' => $csvLine,
            'sourceSheet' => $clean['source_sheet'],
            'sourceRow' => $clean['source_row'],
            'targetOrgan' => $clean['target_organ'],
            'spaceId' => $space ? (int)$space->id : null,
            'decisionType' => $clean['decision_type'],
            'decisionTypeId' => $decisionType ? (int)$decisionType->id : null,
            'title' => $clean['title'],
            'decision' => $clean['decision'],
            'decisionDate' => $clean['decision_date'],
            'reviewDate' => $clean['review_date'] ?: null,
            'duplicate' => $duplicate,
            'ready' => $errors === [] && !$duplicate,
            'errors' => $errors,
        ];
    }

    public static function import(array $preview): array
    {
        $rows = array_values(array_filter(
            (array)($preview['rows'] ?? []),
            static fn(array $row): bool => !empty($row['ready'])
        ));

        $transaction = Yii::$app->db->beginTransaction();
        $imported = 0;
        $duplicates = 0;

        try {
            foreach ($rows as $row) {
                if (Entry::find()->where([
                    'organ' => (int)$row['spaceId'],
                    'decision_date' => $row['decisionDate'],
                    'title' => $row['title'],
                ])->exists()) {
                    $duplicates++;
                    continue;
                }

                $space = Space::findOne((int)$row['spaceId']);
                $decisionType = DecisionType::findOne((int)$row['decisionTypeId']);
                if (!$space || !$decisionType) {
                    throw new \RuntimeException('Import mapping changed after preview.');
                }

                $decisionDate = new DateTimeImmutable($row['decisionDate']);
                $module = Yii::$app->getModule('sociolog');
                $days = (int)$module->settings->get('defaultEffectiveDays', 10);
                $extra = (bool)$module->settings->get('effectiveDateAddExtraDay', true) ? 1 : 0;
                $effectiveDate = $decisionDate->modify('+' . ($days + $extra) . ' days');

                $entry = new Entry();
                $entry->historicalImport = true;
                $entry->setContentContainer($space);
                $entry->title = $row['title'];
                $entry->decision = $row['decision'];
                $entry->organ = (int)$space->id;
                $entry->current_organ = (int)$space->id;
                $entry->decision_type_id = (int)$decisionType->id;
                $entry->decision_date = $row['decisionDate'];
                $entry->published_at = $row['decisionDate'];
                $entry->effective_date = $effectiveDate->format('Y-m-d');
                $entry->review_date = $row['reviewDate'];
                $entry->status = Entry::STATUS_AUTO;

                if (!$entry->save(false)) {
                    throw new \RuntimeException('Historical entry could not be saved.');
                }

                $timestamp = $decisionDate->setTime(12, 0)->getTimestamp();
                Entry::updateAll([
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ], ['id' => (int)$entry->id]);

                if (!empty($entry->content_id)) {
                    Content::updateAll([
                        'visibility' => Content::VISIBILITY_PUBLIC,
                        'state' => Content::STATE_PUBLISHED,
                        'created_at' => date('Y-m-d H:i:s', $timestamp),
                        'updated_at' => date('Y-m-d H:i:s', $timestamp),
                    ], ['id' => (int)$entry->content_id]);
                }

                $imported++;
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }

        return [
            'imported' => $imported,
            'duplicates' => $duplicates,
        ];
    }

    private static function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private static function normalizeHeader($value): string
    {
        $value = (string)$value;
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        // A UTF-8 BOM before the first quote prevents str_getcsv() from
        // recognising that quote as the field enclosure. Remove both safely.
        return trim($value, " \t\n\r\0\x0B\"'");
    }
}
