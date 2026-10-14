<?php

namespace humhub\modules\sociolog\components;

use humhub\helpers\Html;
use humhub\modules\activity\components\MailSummary;
use humhub\modules\activity\models\MailSummaryForm;
use humhub\modules\content\models\Content;
use humhub\modules\sociolog\models\Entry;
use humhub\modules\sociolog\services\ReadAccess;
use humhub\modules\user\models\User;
use Yii;
use yii\db\Expression;
use yii\helpers\Url;

/** Native digest delivery with recipient-specific logbook access, without activity copies. */
class LogbookMailSummary extends MailSummary
{
    public static function register(): void
    {
        // Respect an installation's explicit custom summary implementation.
        if (!Yii::$container->has(MailSummary::class)) {
            Yii::$container->set(MailSummary::class, static::class);
        }
    }

    public function getLogbookEntries(): array
    {
        if (!Yii::$app->hasModule('sociolog') || !$this->user
            || (int)$this->user->status !== User::STATUS_ENABLED || $this->maxActivityCount <= 0) {
            return [];
        }
        $activity = Yii::$app->getModule('activity')->settings;
        $settings = $activity->user($this->user);
        $suppressed = explode(',', (string)$settings->get('mailSummaryActivitySuppress', $activity->get('mailSummaryActivitySuppress', '')));
        if (in_array('humhub\\modules\\content\\activities\\ContentCreated', $suppressed, true)) {
            return [];
        }
        $last = (int)$settings->get('mailSummaryLast', 0);
        $hours = [self::INTERVAL_HOURLY => 1, self::INTERVAL_DAILY => 24,
            self::INTERVAL_WEEKLY => 168, self::INTERVAL_MONTHLY => 720][$this->interval] ?? 1;
        $since = $last ? date('Y-m-d H:i:s', $last) : new Expression('NOW() - INTERVAL ' . $hours . ' HOUR');
        if (property_exists($this, 'lastSummaryDate') && $this->lastSummaryDate !== null) {
            $since = $this->lastSummaryDate;
        }
        $table = Entry::tableName();
        $query = Entry::find()->publishedOrLegacy(false)
            ->andWhere(['sociolog_content_state.state' => Content::STATE_PUBLISHED])
            ->andWhere(['>', 'sociolog_content_state.created_at', $since])
            ->andWhere(['!=', $table . '.created_by', (int)$this->user->id]);
        ReadAccess::filterQuery($query, $table, $this->user);
        // Digest Space preferences refer to the original Space, not its ACL container.
        $guids = array_filter(explode(',', (string)$settings->get('mailSummaryLimitSpaces', $activity->get('mailSummaryLimitSpaces', ''))));
        if ($guids) {
            $ids = \humhub\modules\content\models\ContentContainer::find()->select('id')->where(['guid' => $guids])->column();
            $query->leftJoin(['digest_scope' => \humhub\modules\sociolog\models\ReadScope::tableName()], 'digest_scope.entry_id=' . $table . '.id');
            $original = new Expression('COALESCE(digest_scope.original_container_id, sociolog_content_state.contentcontainer_id)');
            $include = (int)$settings->get('mailSummaryLimitSpacesMode', $activity->get('mailSummaryLimitSpacesMode', '')) === MailSummaryForm::LIMIT_MODE_INCLUDE;
            $query->andWhere([$include ? 'in' : 'not in', $original, $ids ?: [-1]]);
        }
        return $query->orderBy(['sociolog_content_state.created_at' => SORT_DESC, $table . '.id' => SORT_DESC])
            ->limit(max(0, (int)$this->maxActivityCount))->all();
    }

    public function send()
    {
        if (!Yii::$app->hasModule('sociolog')) {
            return parent::send();
        }
        if (!$this->user || !$this->user->email || (int)$this->user->status !== User::STATUS_ENABLED) {
            return false;
        }
        Yii::$app->i18n->setUserLocale($this->user);
        try {
            $items = [];
            $modern = class_exists('humhub\\modules\\activity\\services\\RenderService');
            foreach (parent::getActivities() as $record) {
                $source = $modern ? $record->content?->getPolymorphicRelation() : $record->source;
                if ($source instanceof Entry && !ReadAccess::canRead($source, $this->user)) {
                    continue;
                }
                $renderer = $modern ? new \humhub\modules\activity\services\RenderService($record)
                    : new \humhub\modules\activity\components\ActivityMailRenderer();
                $items[] = ['date' => $modern ? $record->created_at : $record->record->content->created_at,
                    'html' => $modern ? $renderer->getMailHtml() : $renderer->render($record),
                    'text' => $modern ? $renderer->getMailText() : $renderer->renderText($record)];
            }
            foreach ($this->getLogbookEntries() as $entry) {
                if (!ReadAccess::canRead($entry, $this->user)) {
                    continue;
                }
                $url = Url::to(['/sociolog/entry/view', 'id' => $entry->id], true);
                $label = Yii::t('SociologModule.base', 'Neuer Logbucheintrag');
                $items[] = ['date' => $entry->content->created_at,
                    'html' => Html::tag('p', Html::encode($label) . ': ' . Html::a(Html::encode($entry->title), $url)),
                    'text' => $label . ': ' . $entry->title . "\n" . $url . "\n\n"];
            }
            usort($items, static fn($a, $b) => strcmp($b['date'], $a['date']));
            $items = array_slice($items, 0, max(0, (int)$this->maxActivityCount));
            if (!$items) {
                return false;
            }
            Yii::$app->view->params['showUnsubscribe'] = true;
            Yii::$app->view->params['unsubscribeUrl'] = Url::to(['/activity/user'], true);
            $mail = Yii::$app->mailer->compose(['html' => $this->layout, 'text' => $this->layoutPlaintext],
                ['activities' => implode('', array_column($items, 'html')),
                    'activitiesPlaintext' => implode('', array_column($items, 'text'))]);
            $mail->setTo($this->user->email);
            $mail->setSubject(match ($this->interval) {
                self::INTERVAL_DAILY => Yii::t('ActivityModule.base', 'Your daily summary'),
                self::INTERVAL_WEEKLY => Yii::t('ActivityModule.base', 'Your weekly summary'),
                self::INTERVAL_MONTHLY => Yii::t('ActivityModule.base', 'Your monthly summary'),
                default => Yii::t('ActivityModule.base', 'Latest news'),
            });
            if (!$mail->send()) {
                return false;
            }
            Yii::$app->getModule('activity')->settings->user($this->user)->set('mailSummaryLast', time());
            return true;
        } finally {
            Yii::$app->i18n->autosetLocale();
        }
    }
}
