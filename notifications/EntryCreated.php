<?php

namespace humhub\modules\sociolog\notifications;

// The public class name also identifies notifications already stored by HumHub.
if (NotificationCompatibility::usesRecordApi()) {
    class EntryCreated extends modern\EntryCreated
    {
    }
} else {
    class EntryCreated extends legacy\EntryCreated
    {
    }
}
