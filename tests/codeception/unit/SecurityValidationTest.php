<?php

namespace humhub\modules\sociolog\tests\codeception\unit;

use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\Organ;
use humhub\modules\sociolog\models\Protocol;
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
}
