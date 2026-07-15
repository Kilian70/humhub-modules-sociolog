<?php

use yii\db\Migration;

class m260316_140000_add_is_organ_space extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'sociolog_space_config',
            'is_organ_space',
            $this->boolean()->notNull()->defaultValue(0)->after('organ_id')
        );

        // Standard: Space dessen Name = Organname → Organ-Space
        $rows = (new \yii\db\Query())
            ->select(['sc.id', 'sc.space_id', 'o.name'])
            ->from(['sc' => 'sociolog_space_config'])
            ->leftJoin('sociolog_organ o', 'o.id = sc.organ_id')
            ->all();

        foreach ($rows as $row) {

            $space = (new \yii\db\Query())
                ->select(['name'])
                ->from('space')
                ->where(['id' => $row['space_id']])
                ->one();

            if (!$space) {
                continue;
            }

            if (trim($space['name']) === trim($row['name'])) {

                $this->update(
                    'sociolog_space_config',
                    ['is_organ_space' => 1],
                    ['id' => $row['id']]
                );
            }
        }
    }

    public function safeDown()
    {
        $this->dropColumn('sociolog_space_config', 'is_organ_space');
    }
}