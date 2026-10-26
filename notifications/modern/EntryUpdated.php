<?php

namespace humhub\modules\sociolog\notifications\modern;

class EntryUpdated extends EntryNotification
{
    protected const MESSAGE = 'Eintrag im {module} wurde aktualisiert: {title}';
}
