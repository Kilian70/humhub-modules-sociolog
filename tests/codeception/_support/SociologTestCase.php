<?php

namespace sociolog;

use humhub\modules\sociolog\models\DecisionType;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

abstract class SociologTestCase extends HumHubDbTestCase
{
    protected function disableNotifications(): void
    {
        $settings = Yii::$app->getModule('sociolog')->settings;
        $settings->set('notificationsEnabled', false);
        $settings->set('defaultEffectiveDays', 10);
        $settings->set('effectiveDateAddExtraDay', true);
    }

    protected function createEntry(Space $space, string $title = 'Runtime test entry'): Entry
    {
        $decisionType = DecisionType::find()->orderBy(['id' => SORT_ASC])->one();
        $this->assertNotNull($decisionType, 'A decision type must exist after the module migrations.');

        $entry = new Entry([
            'title' => $title,
            'decision' => 'Automated HumHub runtime test decision.',
            'description' => 'Created by the Sociolog Codeception test suite.',
            'organ' => (int)$space->id,
            'decision_type_id' => (int)$decisionType->id,
            'decision_date' => date('Y-m-d'),
        ]);
        $entry->setContentContainer($space);

        $this->assertTrue(
            $entry->save(),
            'Entry could not be saved: ' . json_encode($entry->getErrors(), JSON_UNESCAPED_UNICODE)
        );
        $this->assertTrue($entry->refresh());

        return $entry;
    }
}
