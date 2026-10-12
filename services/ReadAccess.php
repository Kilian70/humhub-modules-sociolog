<?php

namespace humhub\modules\sociolog\services;

use humhub\libs\UUID;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\models\ReadScope;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\helpers\UserHelper;
use humhub\modules\user\models\GroupUser;
use humhub\modules\user\models\User;
use Yii;
use yii\db\Query;

class ReadAccess
{
    public const ALL = 'all';
    public const SPACE = 'space';
    public const SELECTED = 'selected';

    public static function mode(): string
    {
        return (string)Yii::$app->getModule('sociolog')->settings->getUncached('readerMode', self::ALL);
    }

    public static function isSelected(?User $user): bool
    {
        if (!$user || (int)$user->status !== User::STATUS_ENABLED) {
            return false;
        }
        if (in_array($user->guid, self::selection('readerUsers'), true)) {
            return true;
        }
        $groups = array_values(array_filter(self::selection('readerGroups'), static fn($id): bool => is_int($id) && $id > 0));
        return $groups !== [] && GroupUser::find()->where([
            'user_id' => (int)$user->id, 'group_id' => $groups,
        ])->exists();
    }

    private static function selection(string $name): array
    {
        try {
            $value = \yii\helpers\Json::decode(Yii::$app->getModule('sociolog')->settings->getUncached($name, '[]'));
            return is_array($value) ? $value : [];
        } catch (\yii\base\InvalidArgumentException $error) {
            return [];
        }
    }

    private static function canAccessPrivateAsCurrentUser(User $user): bool
    {
        // The impersonation service is new in HumHub 1.19.
        return !$user->isCurrentUser() || !method_exists(Yii::$app->user, 'getImpersonation')
            || Yii::$app->user->getImpersonation()->canAccessPrivateContent();
    }

    public static function canRead(Entry $entry, $user = null): bool
    {
        $user = UserHelper::getUserByParam($user);
        if (!$user || (int)$user->status !== User::STATUS_ENABLED) {
            return false;
        }
        if (!self::canAccessPrivateAsCurrentUser($user)
            && (self::mode() !== self::ALL || (!$entry->content->isNewRecord && $entry->content->isPrivate()))) {
            return false;
        }
        // HumHub also grants authors and content administrators access to private content.
        if ($user->canManageAllContent() || $user->isSystemAdmin()
            || (int)$entry->created_by === (int)$user->id
            || (!$entry->content->isNewRecord && (int)$entry->content->created_by === (int)$user->id)) {
            return true;
        }
        switch (self::mode()) {
            case self::ALL:
                return true;
            case self::SELECTED:
                return self::isSelected($user);
            case self::SPACE:
                $containerId = self::originalContainerId($entry);
                $container = ContentContainer::findOne($containerId);
                return $container && $container->class === Space::class
                    && Membership::find()->where([
                        'space_id' => (int)$container->pk,
                        'user_id' => (int)$user->id,
                        'status' => Membership::STATUS_MEMBER,
                    ])->exists();
            default:
                return false;
        }
    }

    public static function originalContainerId(Entry $entry): ?int
    {
        $scope = ReadScope::findOne(['entry_id' => (int)$entry->id]);
        if ($scope) {
            return $scope->original_container_id ? (int)$scope->original_container_id : null;
        }
        if (!$entry->content->isNewRecord && $entry->content->contentcontainer_id) {
            return (int)$entry->content->contentcontainer_id;
        }
        $space = Space::findOne((int)$entry->organ);
        return $space ? (int)$space->contentcontainer_id : null;
    }

    public static function filterQuery($query, string $entryTable, $perspective = null): void
    {
        $user = UserHelper::getUserByParam($perspective);
        if (!$user || (int)$user->status !== User::STATUS_ENABLED) {
            $query->andWhere('0=1');
            return;
        }
        if (!self::canAccessPrivateAsCurrentUser($user)) {
            if (self::mode() !== self::ALL) {
                $query->andWhere('0=1');
                return;
            }
            $public = (new Query())->select('imp_entry.id')->from(['imp_entry' => Entry::tableName()])
                ->leftJoin(['imp_content' => Content::tableName()], 'imp_content.object_id=imp_entry.id AND imp_content.object_model=:imp_model', [':imp_model' => Entry::class])
                ->where(['or', ['imp_content.visibility' => Content::VISIBILITY_PUBLIC], ['imp_content.id' => null]]);
            $query->andWhere(['in', $entryTable . '.id', $public]);
        }
        if ($user->canManageAllContent() || $user->isSystemAdmin() || self::mode() === self::ALL) {
            return;
        }
        $allowed = (new Query())->select('acl_entry.id')->from(['acl_entry' => Entry::tableName()])
            ->leftJoin(['acl_content' => Content::tableName()],
                'acl_content.object_id=acl_entry.id AND acl_content.object_model=:acl_model', [':acl_model' => Entry::class]);
        $condition = ['or', ['acl_entry.created_by' => (int)$user->id], ['acl_content.created_by' => (int)$user->id]];
        if (self::mode() === self::SELECTED && self::isSelected($user)) {
            return;
        }
        if (self::mode() === self::SPACE) {
            $allowed->leftJoin(['acl_scope' => ReadScope::tableName()], 'acl_scope.entry_id=acl_entry.id')
                ->leftJoin(['acl_legacy_space' => Space::tableName()], 'acl_content.id IS NULL AND acl_legacy_space.id=acl_entry.organ')
                ->leftJoin(['acl_container' => ContentContainer::tableName()],
                    'acl_container.id=CASE WHEN acl_scope.id IS NOT NULL THEN acl_scope.original_container_id ELSE COALESCE(acl_content.contentcontainer_id,acl_legacy_space.contentcontainer_id) END')
                ->leftJoin(['acl_membership' => Membership::tableName()],
                    'acl_container.class=:acl_space AND acl_membership.space_id=acl_container.pk AND acl_membership.user_id=:acl_user AND acl_membership.status=:acl_status',
                    [':acl_space' => Space::class, ':acl_user' => (int)$user->id, ':acl_status' => Membership::STATUS_MEMBER]);
            $condition[] = ['not', ['acl_membership.user_id' => null]];
        }
        $query->andWhere(['in', $entryTable . '.id', $allowed->where($condition)]);
    }

    /** Configure before ContentActiveRecord saves Content and sends notifications. */
    public static function apply(Entry $entry): void
    {
        $content = $entry->content;
        $scope = ReadScope::findOne(['entry_id' => (int)$entry->id]);
        $originalId = $scope ? $scope->original_container_id : self::originalContainerId($entry);
        $original = $originalId ? ContentContainer::findOne((int)$originalId)?->getPolymorphicRelation() : null;
        // Public content in an ALL-visible Space (or a global container) may be
        // exposed to guests by HumHub. Use our private container in that case too.
        if (self::mode() === self::ALL && $original instanceof Space && (int)$original->visibility !== Space::VISIBILITY_ALL) {
            $content->setContainer($original);
            $content->hidden = $scope ? (int)$scope->original_hidden : (int)$content->hidden;
            $content->visibility = Content::VISIBILITY_PUBLIC;
            return;
        }
        if (!$scope) {
            $scope = new ReadScope();
            $scope->guid = UUID::v4();
            $scope->entry_id = (int)$entry->id;
            $scope->original_container_id = self::originalContainerId($entry);
            $scope->original_hidden = (int)$content->hidden;
            if (!$scope->save(false) || !$scope->contentcontainer_id) {
                throw new \RuntimeException('Could not create private Sociolog read container.');
            }
        }
        $content->setContainer($scope);
        $content->visibility = Content::VISIBILITY_PRIVATE;
        $content->hidden = 1;
    }

    /** Called inside the settings transaction; never catches partial synchronization errors. */
    public static function synchronize(): void
    {
        foreach (Entry::find()->each(100) as $entry) {
            if ($entry->content->isNewRecord) {
                continue;
            }
            self::apply($entry);
            if ($entry->content->getDirtyAttributes(['contentcontainer_id', 'visibility', 'hidden']) !== []
                && !$entry->content->save(false, ['contentcontainer_id', 'visibility', 'hidden'])) {
                throw new \RuntimeException('Could not synchronize Sociolog read access.');
            }
        }
    }
}
