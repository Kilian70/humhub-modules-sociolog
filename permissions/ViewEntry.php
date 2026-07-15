<?php

namespace humhub\modules\sociolog\permissions;

use humhub\libs\BasePermission;
use Yii;

class ViewEntry extends BasePermission
{
    public $id = 'viewEntry';

    public $moduleId = 'sociolog';

    // Wichtig: erlaubt grundsätzlich allen
    public $defaultState = self::STATE_ALLOW;

    public $sortOrder = 50;

    public $groupId = 'sociolog';

    public $groupTitle = 'Logbuch';


    /*
     * NICHT einschränken hier
     *
     * Sichtbarkeit steuerst du über:
     *
     * - findVisible()
     * - Organ Logik
     * - Space Logik
     *
     */

    public function getTitle()
    {
        return Yii::t(
            'SociologModule.permissions',
            'Einträge ansehen'
        );
    }

    public function getDescription()
    {
        return Yii::t(
            'SociologModule.permissions',
            'Erlaubt das Ansehen von Logbuch-Einträgen.'
        );
    }


    /*
     * Space Permission aktiv
     */
    public function isSpacePermission()
    {
        return true;
    }
}