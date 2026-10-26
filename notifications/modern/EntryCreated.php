<?php

namespace humhub\modules\sociolog\notifications\modern;

class EntryCreated extends EntryNotification
{
    protected const MESSAGE = 'Neuer Eintrag im {module}: {title}';
}
