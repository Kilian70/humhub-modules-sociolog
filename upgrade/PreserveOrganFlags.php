<?php
namespace humhub\modules\sociolog\upgrade;

use humhub\components\Migration;
use yii\db\Query;

/** Safe continuation when retained legacy areas have no guessed organ mapping. */
class PreserveOrganFlags extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('sociolog_space_config', true);
        if (!isset($schema->columns['is_organ_space'])) {
            $this->addColumn('sociolog_space_config', 'is_organ_space', $this->boolean()->notNull()->defaultValue(0)->after('organ_id'));
        }
        $rows = (new Query())->select(['sc.id', 'o.name AS organ_name', 's.name AS space_name'])
            ->from(['sc'=>'sociolog_space_config'])->innerJoin(['o'=>'sociolog_organ'], 'o.id=sc.organ_id')
            ->innerJoin(['s'=>'space'], 's.id=sc.space_id')->all();
        foreach ($rows as $row) {
            if ($row['organ_name'] !== null && $row['space_name'] !== null
                && trim($row['organ_name']) === trim($row['space_name'])) {
                $this->update('sociolog_space_config', ['is_organ_space'=>1], ['id'=>$row['id']]);
            }
        }
        return true;
    }
    public function safeDown() { return false; }
}
