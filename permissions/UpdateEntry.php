<?php

namespace humhub\modules\sociolog\permissions;

use humhub\libs\BasePermission;
use humhub\modules\space\models\Space;
use Yii;

class UpdateEntry extends BasePermission
{
    public $id = 'updateEntry';

    public $moduleId = 'sociolog';

    public $defaultState = self::STATE_DENY;

    public $defaultAllowedGroups = [
        Space::USERGROUP_OWNER,
        Space::USERGROUP_ADMIN,
    ];

    public $sortOrder = 200;

    public $groupId = 'sociolog';

    public $groupTitle = 'Logbuch';

    public function getTitle()
    {
        return Yii::t(
            'SociologModule.permissions',
            'Einträge bearbeiten'
        );
    }


    public function getDescription()
    {
        return Yii::t(
            'SociologModule.permissions',
            'Erlaubt das Bearbeiten bestehender Logbuch-Einträge.'
        );
    }


    public function isSpacePermission()
    {
        return true;
    }
}
