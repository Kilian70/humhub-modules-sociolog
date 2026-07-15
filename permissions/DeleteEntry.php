<?php

namespace humhub\modules\sociolog\permissions;

use humhub\libs\BasePermission;
use humhub\modules\space\models\Space;
use Yii;

class DeleteEntry extends BasePermission
{
    public $id = 'deleteEntry';

    public $moduleId = 'sociolog';

    public $defaultState = self::STATE_DENY;

    public $defaultAllowedGroups = [
        Space::USERGROUP_OWNER,
        Space::USERGROUP_ADMIN,
    ];

    public $sortOrder = 300;

    public $groupId = 'sociolog';

    public $groupTitle = 'Logbuch';

    public function getTitle()
    {
        return Yii::t(
            'SociologModule.permissions',
            'Einträge löschen'
        );
    }

    public function getDescription()
    {
        return Yii::t(
            'SociologModule.permissions',
            'Erlaubt das Löschen von Logbuch-Einträgen.'
        );
    }


    public function isSpacePermission()
    {
        return true;
    }
}
