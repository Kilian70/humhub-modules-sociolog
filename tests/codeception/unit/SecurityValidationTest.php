<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\Organ;
use humhub\modules\sociolog\models\Protocol;
use humhub\modules\sociolog\models\EntryReview;
use humhub\modules\sociolog\models\ReviewForm;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\sociolog\helpers\RichTextHelper;
use sociolog\SociologTestCase;

class SecurityValidationTest extends SociologTestCase
{
    public function testEntryRejectsOversizedTitle(): void
    {
        $entry = new Entry([
            'title' => str_repeat('x', 256),
            'decision' => 'Decision',
            'organ' => 1,
            'decision_type_id' => 1,
            'decision_date' => date('Y-m-d'),
        ]);

        $this->assertFalse($entry->validate());
        $this->assertArrayHasKey('title', $entry->getErrors());
    }

    public function testProtocolRejectsUnsafeAndOversizedUrls(): void
    {
        $unsafe = new Protocol([
            'entry_id' => 1,
            'title' => 'Unsafe link',
            'url' => 'javascript:alert(1)',
        ]);
        $this->assertFalse($unsafe->validate());
        $this->assertArrayHasKey('url', $unsafe->getErrors());

        $oversized = new Protocol([
            'entry_id' => 1,
            'title' => 'Oversized link',
            'url' => 'https://example.org/' . str_repeat('a', 1000),
        ]);
        $this->assertFalse($oversized->validate());
        $this->assertArrayHasKey('url', $oversized->getErrors());
    }

    public function testOrganRejectsHierarchyCycle(): void
    {
        $organ = new Organ([
            'name' => 'Invalid cyclic organ',
            'parent_id' => 900001,
        ]);
        $organ->id = 900001;

        $this->assertFalse($organ->validate());
        $this->assertArrayHasKey('parent_id', $organ->getErrors());
    }

    public function testReviewRequiresJustificationAndNextDateWhenConfirmed(): void
    {
        $confirmed = new ReviewForm([
            'result' => EntryReview::RESULT_CONFIRMED,
            'justification' => 'Der Entscheid bleibt weiterhin notwendig.',
        ]);
        $this->assertFalse($confirmed->validate());
        $this->assertArrayHasKey('reviewDate', $confirmed->getErrors());

        $confirmed->reviewDate = date('Y-m-d');
        $this->assertFalse($confirmed->validate());
        $this->assertArrayHasKey('reviewDate', $confirmed->getErrors());

        $repealed = new ReviewForm([
            'result' => EntryReview::RESULT_REPEALED,
            'justification' => 'Der Entscheid wird nicht mehr benötigt.',
        ]);
        $this->assertTrue($repealed->validate());
    }

    public function testSelectedWriterGuidsAreStoredAsUniqueJsonValues(): void
    {
        $config = new SpaceConfig();
        $config->setWriterUserGuids(['guid-a', 'guid-a', '', 'guid-b']);

        $this->assertSame(['guid-a', 'guid-b'], $config->getWriterUserGuids());
    }

    public function testRichTextPreviewRemovesFormattingMarkup(): void
    {
        $richText = "**Wichtiger Entscheid**\n\n- Erster Punkt\n- [Zweiter Punkt](https://example.org)";

        $plainText = RichTextHelper::plainText($richText, true);
        $preview = RichTextHelper::preview($richText, 32);

        $this->assertStringContainsString('Wichtiger Entscheid', $plainText);
        $this->assertStringContainsString('Erster Punkt', $plainText);
        $this->assertStringNotContainsString('**', $plainText);
        $this->assertStringNotContainsString('](', $plainText);
        $this->assertLessThanOrEqual(32, mb_strwidth($preview));
    }
}
