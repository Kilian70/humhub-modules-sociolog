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
     * Use the module's configurable read ACL. Core readable() only supports
     * Space/Profile membership and would also drop genuine legacy rows.
     */
    public function publishedOrLegacy(bool $applyReadAccess = true): self
    {
        $contentAlias = 'sociolog_content_state';
        $entryTable = Entry::tableName();

        $this
            // ContentActiveRecord::getContent() contains a relation-level
            // WHERE condition. Used through joinWith(), that condition turns
            // the intended LEFT JOIN into an effective INNER JOIN and drops
            // genuine legacy rows. Keep the model condition in the JOIN
            // clause so rows without Content remain visible.
            ->leftJoin(
                [$contentAlias => Content::tableName()],
                "{$contentAlias}.object_id = {$entryTable}.id AND {$contentAlias}.object_model = :sociologObjectModel",
                [':sociologObjectModel' => Entry::class]
            )
            ->andWhere([
                'or',
                ['!=', $contentAlias . '.state', Content::STATE_DELETED],
                [$contentAlias . '.id' => null],
            ]);
        if ($applyReadAccess) {
            \humhub\modules\sociolog\services\ReadAccess::filterQuery($this, $entryTable);
        }
        return $this;
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
