<?php

namespace humhub\modules\sociolog\models;

use Yii;
use humhub\components\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

class Organ extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%sociolog_organ}}';
    }

    public function rules()
{
    return [
        [['name'], 'required'],

        [['parent_id', 'sort_order', 'created_by', 'updated_by', 'organ_space_id'], 'integer'],

        [['created_at', 'updated_at'], 'safe'],

        [['name'], 'string', 'max' => 255],

        [['color'], 'string', 'max' => 20],
    ];
}



   public function behaviors()
{
    return [
        [
            'class' => TimestampBehavior::class,
            'value' => function () {
                return date('Y-m-d H:i:s');
            },
        ],
        BlameableBehavior::class,
    ];
}

    /**
     * Eltern-Organ
     */
    public function getParent()
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    /**
     * Kinder-Organe
     */
    public function getChildren()
    {
        return $this->hasMany(self::class, ['parent_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    /**
     * Spaces dieses Organs
     */
    public function getSpaces()
    {
        return $this->hasMany(SpaceConfig::class, ['organ_id' => 'id']);
    }
    
    public static function getParentOptions($excludeId = null)
{
    $query = self::find()->orderBy(['sort_order' => SORT_ASC]);

    if ($excludeId !== null) {
        $query->andWhere(['!=', 'id', $excludeId]);
    }

    return \yii\helpers\ArrayHelper::map(
        $query->all(),
        'id',
        'name'
    );
}
public function getFullPath()
{
    $path = [$this->name];
    $parent = $this->parent;

    while ($parent) {
        array_unshift($path, $parent->name);
        $parent = $parent->parent;
    }

    return implode(' → ', $path);
}

/**
 * ============================================================
 * 🔹 Hierarchische Organliste (für Dropdowns)
 * ------------------------------------------------------------
 * Beispiel Ergebnis:
 *
 * Hausverein
 * — Leitungskreis
 * — — BK Unterhalt und Reinigung
 * — — — BG Sicherheit
 * — — BK Gemeinschaftsräume
 *
 * ============================================================
 */
public static function getHierarchicalOptions(): array
{
    $organs = self::find()
        ->orderBy(['sort_order' => SORT_ASC])
        ->all();

    $tree = [];

    // Baumstruktur aufbauen
    foreach ($organs as $organ) {

        $parent = $organ->parent_id ?: 0;

        $tree[$parent][] = $organ;
    }

    $result = [];

    $walk = function ($parentId, $level = 0) use (&$walk, &$tree, &$result) {

        if (!isset($tree[$parentId])) {
            return;
        }

        foreach ($tree[$parentId] as $organ) {

            $prefix = str_repeat('— ', $level);

            $result[$organ->id] = $prefix . $organ->name;

            $walk($organ->id, $level + 1);
        }
    };

    // Start bei Root
    $walk(0);

    return $result;
}
}
