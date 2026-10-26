<?php

namespace humhub\modules\sociolog\notifications;

use humhub\modules\notification\components\BaseNotification;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\user\models\User;

/** Select by API capability, including development versions without a release number. */
final class NotificationCompatibility
{
    public static function usesRecordApi(): bool
    {
        return (new \ReflectionMethod(BaseNotification::class, 'category'))->isStatic();
    }

    /** Keep recipient selection and read access checks in Events unchanged. */
    public static function send(string $notificationClass, Entry $entry, User $actor, User $recipient): void
    {
        if (self::usesRecordApi()) {
            $notificationClass::send($recipient, source: $entry, originator: $actor);
            return;
        }

        $notificationClass::instance()->from($actor)->about($entry)->send($recipient);
    }
}
