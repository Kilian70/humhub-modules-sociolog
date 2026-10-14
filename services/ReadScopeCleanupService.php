<?php

namespace humhub\modules\sociolog\services;

use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\ReadScope;
use Yii;
use yii\db\Query;

/** Cleanup only the private container of an entry just permanently deleted. */
class ReadScopeCleanupService
{
    public static function deleteForEntry(int $entryId): bool
    {
        if (Entry::find()->where(['id' => $entryId])->exists()) {
            return false;
        }
        $scope = ReadScope::findOne(['entry_id' => $entryId]);
        if (!$scope) {
            return true;
        }

        return Yii::$app->db->transaction(function () use ($scope): bool {
            // Lock the parent before checking references: concurrent FK inserts
            // must wait until its deletion/retention has been decided.
            $container = Yii::$app->db->createCommand(
                'SELECT * FROM ' . Yii::$app->db->quoteTableName(ContentContainer::tableName())
                . ' WHERE id = :id FOR UPDATE',
                [':id' => (int)$scope->contentcontainer_id]
            )->queryOne();
            $safe = $container && $container['class'] === ReadScope::class
                && (int)$container['pk'] === (int)$scope->id
                && $container['guid'] === $scope->guid
                && (int)ContentContainer::find()->where(['class' => ReadScope::class, 'pk' => $scope->id])->count() === 1
                && !Content::find()->where(['contentcontainer_id' => $scope->contentcontainer_id])->exists()
                && !ReadScope::find()->where(['contentcontainer_id' => $scope->contentcontainer_id])
                    ->andWhere(['<>', 'id', $scope->id])->exists();
            if (!$safe) {
                Yii::warning('Read scope retained after permanent entry deletion: ' . $scope->id, 'sociolog');
                return false;
            }
            // Use the version-specific native container lifecycle, never deleteAll().
            if ($scope->delete() !== 1) {
                throw new \RuntimeException('Could not remove Sociolog read scope.');
            }
            return true;
        });
    }

    /** Read-only inventory; deliberately no bulk cleanup of historical rows. */
    public static function orphanQuery(): Query
    {
        return (new Query())->select(['scope.id', 'scope.entry_id', 'scope.contentcontainer_id'])
            ->from(['scope' => ReadScope::tableName()])
            ->leftJoin(['entry' => Entry::tableName()], 'entry.id = scope.entry_id')
            ->where(['entry.id' => null])->orderBy(['scope.id' => SORT_ASC]);
    }
}
