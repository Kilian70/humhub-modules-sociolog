<?php

namespace humhub\modules\sociolog\notifications\modern;

use humhub\modules\notification\components\BaseNotification;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\services\ReadAccess;
use humhub\modules\user\models\User;
use Yii;

/** Native record-bound notifications for the new HumHub notification API. */
abstract class EntryNotification extends BaseNotification
{
    protected const MESSAGE = '';

    protected function getMessage(array $params): string
    {
        // The core encodes message parameters for HTML and preserves plain text for mail.
        return Yii::t('SociologModule.base', static::MESSAGE, $params);
    }

    protected function getMessageParams(): array
    {
        $entry = $this->getSubjectRecord();
        return [
            'module' => Yii::$app->getModule('sociolog')->getName(),
            'title' => $entry instanceof Entry ? $entry->title : Yii::t('SociologModule.base', 'Eintrag'),
        ];
    }

    public function canReceive(User $user): bool
    {
        $entry = $this->getSubjectRecord();
        return $entry instanceof Entry && ReadAccess::canRead($entry, $user);
    }
}
