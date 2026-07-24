<?php

namespace humhub\modules\sociolog\models;

use Yii;
use humhub\modules\content\components\ContentActiveRecord;
use yii\behaviors\TimestampBehavior;
use humhub\modules\sociolog\models\EntryQuery;

/**
 * ============================================================
 * 🧭 EntryBase – Basisklasse für Sociolog-Einträge
 * ------------------------------------------------------------
 * - Zentrale Validierung & Statuslogik
 * - NULL-sicher (HumHub-typisch, ohne Foreign Keys)
 * - CLI-/Cron-sicher
 * - Single Source of Truth für Status
 * ============================================================
 */
class EntryBase extends ContentActiveRecord
{
    // ============================================================
    // 🔹 Status-Konstanten (DB-Werte)
    // ============================================================
    const STATUS_AUTO    = 'auto';
    const STATUS_PENDING = 'pending';
    const STATUS_VALID   = 'valid';
    const STATUS_REVIEW  = 'review';
    const STATUS_EXPIRED = 'expired';
    const STATUS_OBJECTION = 'objection';
    const STATUS_REPLACED = 'replaced';

    // ============================================================
    // 🔹 Tabellenname
    // ============================================================
    public static function tableName(): string
    {
        return '{{%sociolog_entry}}';
    }

    // ============================================================
    // 🔹 Modul-Settings (CLI-/Cron-sicher)
    // ============================================================
    protected static function getSetting(string $key, $default = null)
    {
        try {
            $module = Yii::$app->getModule('sociolog');
            if ($module && $module->settings) {
                return $module->settings->get($key, $default);
            }

            // Fallback direkt auf DB (Cron / CLI)
            if (Yii::$app->has('db')) {
                $value = (new \yii\db\Query())
                    ->select('value')
                    ->from('{{%setting}}')
                    ->where([
                        'module_id' => 'sociolog',
                        'name' => $key,
                    ])
                    ->scalar();

                if ($value !== false && $value !== null) {
                    return $value;
                }
            }
        } catch (\Throwable $e) {
            // bewusst still – Cron darf nicht abbrechen
        }

        return $default;
    }

    // ============================================================
    // 🔹 Behaviors
    // ============================================================
    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => time(),
            ],
        ];
    }

    // ============================================================
    // 🔹 Validierungsregeln (fachlich final)
    // ============================================================
    public function rules(): array
{
    return [

        // Pflichtfelder
        [['title', 'decision', 'organ', 'decision_date', 'decision_type_id'], 'required'],

        // Textfelder
        [['decision', 'description'], 'string'],

        // Datum
        [['decision_date', 'effective_date', 'review_date'], 'safe'],
        [['review_date'], 'required',
            'when' => static function ($model): bool {
                return $model->isNewRecord
                    && (bool)self::getSetting('reviewDateRequiredForNewEntries', false);
            },
            'enableClientValidation' => false,
        ],

        // Strings
        [['title', 'topic_owner'], 'string', 'max' => 255],

        // Organ = Space ID
        [['organ'], 'integer'],

        // Entscheid-Typ
        [['decision_type_id'], 'integer'],

        // optionale Felder
        [['description', 'topic_owner'], 'default', 'value' => null],
        [['effective_date', 'review_date'], 'default', 'value' => null],

        // Status
        [['status'], 'in', 'range' => array_keys(self::getStatusConfig())],
    ];
}

    // ============================================================
    // 🔹 Status-Automatik beim Speichern
    // ============================================================
    public function beforeSave($insert)
{
    if (!parent::beforeSave($insert)) {
        return false;
    }

    $days = (int) self::getSetting('defaultEffectiveDays', 0);

    // Inkrafttreten berechnen
    if (
        !self::isManualProtectedStatus((string)$this->status)
        && !empty($this->decision_date)
        && empty($this->effective_date)
    ) {

        if ($days > 0) {
            $this->status = self::STATUS_PENDING;

            $this->effective_date = date(
                'Y-m-d',
                strtotime($this->decision_date . " +{$days} days")
            );

        } else {
            $this->status = self::STATUS_VALID;
            $this->effective_date = $this->decision_date;
        }
    }

    return true;
}

    // ============================================================
    // 🔹 ZENTRALE Status-Definition (Single Source of Truth)
    // ============================================================
    public static function getStatusConfig(): array
    {
        return [
            self::STATUS_AUTO => [
                'label' => Yii::t('SociologModule.base', 'Automatisch'),
                'color' => 'secondary',
            ],
            self::STATUS_PENDING => [
                'label' => (string)self::getSetting(
                    'pendingStatusLabel',
                    Yii::t('SociologModule.base', 'Nicht in Kraft')
                ),
                'color' => 'secondary',
            ],
            self::STATUS_VALID => [
                'label' => Yii::t('SociologModule.base', 'Gültig'),
                'color' => 'success',
            ],
            self::STATUS_REVIEW => [
                'label' => Yii::t('SociologModule.base', 'Überprüfung fällig'),
                'color' => 'sociolog-review',
            ],
            self::STATUS_EXPIRED => [
                'label' => Yii::t('SociologModule.base', 'Nicht mehr gültig'),
                'color' => 'dark',
            ],
            self::STATUS_OBJECTION => [
                'label' => Yii::t('SociologModule.base', 'Schwerwiegender Einwand'),
                'color' => 'danger',
            ],
            self::STATUS_REPLACED => [
                'label' => Yii::t('SociologModule.base', 'Ersetzt'),
                'color' => 'dark',
            ],
        ];
    }

    public static function isManualProtectedStatus(string $status): bool
    {
        return in_array($status, [
            self::STATUS_EXPIRED,
            self::STATUS_OBJECTION,
            self::STATUS_REPLACED,
        ], true);
    }

    // ============================================================
    // 🔹 Helper für Dropdowns
    // ============================================================
    public static function getStatusOptions(): array
    {
        $options = [];

        foreach (self::getStatusConfig() as $key => $cfg) {
            // STATUS_AUTO wird über "Automatisch" / Prompt abgebildet
            if ($key === self::STATUS_AUTO) {
                continue;
            }

            if (
                in_array($key, [self::STATUS_OBJECTION, self::STATUS_REPLACED], true)
                && !(bool)self::getSetting('extendedStatusesEnabled', false)
            ) {
                continue;
            }

            $options[$key] = $cfg['label'];
        }

        return $options;
    }
    
    // ============================================================
// 🔹 Content Name (Stream, Notifications)
// ============================================================
public function getContentName(): string
{
    return Yii::t('SociologModule.base', 'Logbuch-Eintrag');
}

// ============================================================
// 🔹 Content Titel (sehr wichtig für Stream, Notifications)
// ============================================================
public function getContentTitle(): string
{
    return (string)$this->title;
}


// ============================================================
// 🔹 Content Beschreibung (optional, aber empfohlen)
// ============================================================
public function getContentDescription(): string
{
    return (string)$this->title;
}


// ============================================================
// 🔹 Content URL (wird für Stream, Notifications genutzt)
// ============================================================
public function getUrl(): string
{
    return \yii\helpers\Url::to([
        '/sociolog/entry/view',
        'id' => $this->id,
    ]);
}

// ============================================================
// 🔹 Content Container
// ============================================================
public function getContentContainer()
{
    return $this->content->container;
}

// ============================================================
// 🔹 Eigene Query-Klasse verwenden (sehr wichtig)
// ============================================================
public static function find()
{
    return new EntryQuery(static::class);
}

// ============================================================
// 🔹 Content Container setzen
// ============================================================
public function setContentContainer($container)
{
    $this->content->container = $container;
}
}
