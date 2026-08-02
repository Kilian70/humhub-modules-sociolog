<?php

namespace humhub\modules\sociolog\models;

use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\models\Content;

class EntryQuery extends ActiveQueryContent
{
    /**
     * Excludes soft-deleted HumHub content while retaining historical Sociolog
     * rows which were created without an associated content record.
     *
     * We intentionally do not use readable() here: Sociolog entries are global
     * logbook records and must be readable by every authenticated user,
     * independently of membership in the entry's Space.
     */
    public function publishedOrLegacy(): self
    {
        $contentTable = Content::tableName();

        return $this
            ->joinWith('content')
            ->andWhere([
                'or',
                ['!=', $contentTable . '.state', Content::STATE_DELETED],
                [$contentTable . '.id' => null],
            ]);
    }

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
