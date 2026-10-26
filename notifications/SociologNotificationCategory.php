<?php

namespace humhub\modules\sociolog\notifications;

if (NotificationCompatibility::usesRecordApi()) {
    // Develop categories are final readonly value objects, not subclassable Yii objects.
    class_alias(\humhub\modules\notification\components\NotificationCategory::class, __NAMESPACE__ . '\\SociologNotificationCategory');
} else {
    class SociologNotificationCategory extends legacy\SociologNotificationCategory
    {
    }
}
