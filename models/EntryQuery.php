<?php

namespace humhub\modules\sociolog\models;

use humhub\modules\content\components\ActiveQueryContent;

class EntryQuery extends ActiveQueryContent
{
    public function visible(): self
    {
        return $this->readable();
    }

    public function valid(): self
    {
        return $this->andWhere([
            Entry::tableName() . '.status' => Entry::STATUS_VALID
        ]);
    }

    public function expired(): self
    {
        return $this->andWhere([
            Entry::tableName() . '.status' => Entry::STATUS_EXPIRED
        ]);
    }

    public function byOrgan(string $organ): self
    {
        return $this->andWhere([
            Entry::tableName() . '.organ' => $organ
        ]);
    }

    public function latest(): self
    {
        return $this->orderBy([
            Entry::tableName() . '.decision_date' => SORT_DESC,
            Entry::tableName() . '.id' => SORT_DESC,
        ]);
    }
}