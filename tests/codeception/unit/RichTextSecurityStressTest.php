<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\sociolog\helpers\RichTextHelper;
use sociolog\SociologTestCase;

class RichTextSecurityStressTest extends SociologTestCase
{
    private const DEFAULT_TEXT_COUNT = 1000;

    public function testActiveContentIsNotRendered(): void
    {
        $payload = <<<'MARKDOWN'
**Sicherer Text**

<script>alert('xss')</script>
<img src=x onerror=alert('xss')>
[Unsicher](javascript:alert('xss'))
MARKDOWN;

        $html = RichText::convert($payload, RichText::FORMAT_HTML, [
            'exclude' => RichTextHelper::EXCLUDED_FEATURES,
        ]);
        $plainText = RichTextHelper::plainText($payload, true);

        $this->assertStringContainsString('Sicherer Text', $html);
        $this->assertDoesNotMatchRegularExpression('/<(script|img)\b/i', $html);
        $this->assertDoesNotMatchRegularExpression('/href\s*=\s*["\']javascript:/i', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringNotContainsString('**', $plainText);
    }

    public function testManyFormattedPreviewsCanBeConverted(): void
    {
        $textCount = max(
            1,
            (int)(getenv('SOCIOLOG_RICHTEXT_STRESS_COUNT') ?: self::DEFAULT_TEXT_COUNT)
        );

        for ($i = 0; $i < $textCount; $i++) {
            $text = "## Entscheid {$i}\n\n- **Punkt A**\n- [Punkt B](https://example.org/{$i})";
            $preview = RichTextHelper::preview($text, 140);

            $this->assertStringContainsString("Entscheid {$i}", $preview);
            $this->assertStringNotContainsString('**', $preview);
            $this->assertStringNotContainsString('](', $preview);
            $this->assertLessThanOrEqual(140, mb_strwidth($preview));
        }
    }
}
