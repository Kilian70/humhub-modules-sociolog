<?php

namespace humhub\modules\sociolog\helpers;

use humhub\modules\content\widgets\richtext\RichText;

/**
 * Gemeinsame Rich-Text-Konfiguration und sichere Kurztext-Ausgabe.
 */
final class RichTextHelper
{
    /**
     * Logbuchtexte bleiben formal: keine Uploads, Erwähnungen oder Einbettungen.
     */
    public const EXCLUDED_FEATURES = ['upload', 'mention', 'oembed', 'emoji'];

    public static function plainText(?string $text, bool $singleLine = false): string
    {
        $plainText = trim(RichText::convert($text));

        if ($singleLine) {
            $plainText = preg_replace('/\s+/u', ' ', $plainText) ?? $plainText;
        }

        return $plainText;
    }

    public static function preview(?string $text, int $length): string
    {
        $plainText = self::plainText($text, true);

        return mb_strimwidth($plainText, 0, $length, ' …');
    }
}
