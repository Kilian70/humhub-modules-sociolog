<?php

namespace humhub\modules\sociolog\models;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * ============================================================
 * 🔹 LocalCreatorBehavior
 * ------------------------------------------------------------
 * - Setzt created_by und updated_by automatisch
 * - Wird von Entry.php eingebunden
 * ============================================================
 */
class LocalCreatorBehavior extends Behavior
{
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
        ];
    }

    public function beforeInsert($event)
    {
        if (Yii::$app->user && !Yii::$app->user->isGuest) {
            $this->owner->created_by = Yii::$app->user->id;
            $this->owner->updated_by = Yii::$app->user->id;
        }
    }

    public function beforeUpdate($event)
    {
        if (Yii::$app->user && !Yii::$app->user->isGuest) {
            $this->owner->updated_by = Yii::$app->user->id;
        }
    }
}
