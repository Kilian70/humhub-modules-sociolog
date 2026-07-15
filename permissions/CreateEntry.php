<?php

namespace humhub\modules\sociolog\permissions;

use humhub\libs\BasePermission;
use humhub\modules\space\models\Space;
use Yii;

class CreateEntry extends BasePermission
{
    public $id = 'createEntry';

    public $moduleId = 'sociolog';

    public $defaultState = self::STATE_DENY;

    public $defaultAllowedGroups = [
        Space::USERGROUP_OWNER,
        Space::USERGROUP_ADMIN,
    ];

    public $sortOrder = 100;

    public $groupId = 'sociolog';

    public $groupTitle = 'Logbuch';

    public function getTitle()
    {
        return Yii::t(
            'SociologModule.permissions',
            'Einträge erstellen'
        );
    }


    public function getDescription()
    {
        return Yii::t(
            'SociologModule.permissions',
            'Erlaubt das Erstellen neuer Logbuch-Einträge.'
        );
    }


    public function isSpacePermission()
    {
        return true;
    }
}
