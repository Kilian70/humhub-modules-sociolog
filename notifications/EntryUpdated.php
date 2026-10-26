<?php

namespace humhub\modules\sociolog\notifications;

// The public class name also identifies notifications already stored by HumHub.
if (NotificationCompatibility::usesRecordApi()) {
    class EntryUpdated extends modern\EntryUpdated
    {
    }
} else {
    class EntryUpdated extends legacy\EntryUpdated
    {
    }
}
