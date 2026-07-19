<?php

namespace humhub\modules\sociolog\models;

use Yii;
use humhub\components\ActiveRecord;

/**
 * ============================================================
 * 🔹 Modell: DecisionType
 * ------------------------------------------------------------
 * Definiert die verfügbaren Entscheidtypen (z. B. Grundsatzentscheid,
 * Richtlinie, Prozessentscheid) inkl. Farbe, Beschreibung und Sortierung.
 *
 * 🔐 Fachregel:
 * - Entscheidungstypen dürfen NICHT gelöscht werden,
 *   solange sie noch von Einträgen verwendet werden.
 * ============================================================
 */
class DecisionType extends ActiveRecord
{
    /**
     * Tabellenname
     */
    public static function tableName()
    {
        return '{{%sociolog_decision_type}}';
    }

    // ============================================================
    // 🔹 Regeln (defensiv & konsistent)
    // ============================================================
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 150],
            [['description'], 'string', 'max' => 500],
            [['color'], 'string', 'max' => 20],
            [['sort_order'], 'integer'],

            [['color'], 'default', 'value' => '#777777'],
            [['sort_order'], 'default', 'value' => 100],

            // HEX-Farbvalidierung
            [
                'color',
                'match',
                'pattern' => '/^#([A-Fa-f0-9]{3}){1,2}$/',
                'message' => Yii::t('SociologModule.base', 'Bitte gültigen HEX-Farbcode eingeben.')
            ],

            // Name eindeutig
            [
                ['name'],
                'unique',
                'message' => Yii::t('SociologModule.base', 'Dieser Name ist bereits vergeben.')
            ],
        ];
    }

    // ============================================================
    // 🔹 Beschriftungen
    // ============================================================
    public function attributeLabels()
    {
        return [
            'id'          => Yii::t('SociologModule.base', 'ID'),
            'name'        => Yii::t('SociologModule.base', 'Bezeichnung'),
            'description' => Yii::t('SociologModule.base', 'Beschreibung'),
            'color'       => Yii::t('SociologModule.base', 'Farbe'),
            'sort_order'  => Yii::t('SociologModule.base', 'Sortierung'),
        ];
    }

    // ============================================================
    // 🔹 Beziehungen (bewusst ohne FK-Zwang)
    // ============================================================
    public function getEntries()
    {
        return $this->hasMany(Entry::class, ['decision_type_id' => 'id']);
    }

    // ============================================================
    // 🔐 Löschschutz (fachlich entscheidend!)
    // ============================================================
    public function beforeDelete()
    {
        if (parent::beforeDelete() === false) {
            return false;
        }

        // Falls noch Einträge existieren → Löschen verbieten
        if ($this->getEntries()->exists()) {
            $this->addError(
                'id',
                Yii::t(
                    'SociologModule.base',
                    'Dieser Entscheidungstyp kann nicht gelöscht werden, da er noch von Einträgen verwendet wird.'
                )
            );

            return false;
        }

        return true;
    }

    // ============================================================
    // 🔹 UI-Helfer
    // ============================================================

    /**
     * Gibt einen HTML-Badge zurück.
     * Immer NULL-safe.
     */
    public function getBadge(): string
    {
        $name  = trim((string)$this->name);
        $color = trim((string)$this->color);

        if ($name === '') {
            $name = Yii::t('SociologModule.base', 'Unbekannt');
        }

        if ($color === '' || !preg_match('/^#([A-Fa-f0-9]{3}){1,2}$/', $color)) {
            $color = '#6c757d';
        }

        $textColor = self::getAccessibleTextColor($color);

        return sprintf(
            '<span class="badge text-uppercase" style="background-color:%s;color:%s;border-radius:6px;">%s</span>',
            htmlspecialchars($color, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($textColor, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Wählt anhand der relativen WCAG-Luminanz die kontrastreichere
     * Textfarbe Schwarz oder Weiss für einen Hex-Hintergrund.
     */
    public static function getAccessibleTextColor(string $color): string
    {
        if (!preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color)) {
            $color = '#6c757d';
        }

        $hex = substr($color, 1);
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $channels = [];
        foreach ([0, 2, 4] as $offset) {
            $value = hexdec(substr($hex, $offset, 2)) / 255;
            $channels[] = $value <= 0.04045
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        }

        $luminance = 0.2126 * $channels[0]
            + 0.7152 * $channels[1]
            + 0.0722 * $channels[2];

        $contrastWhite = 1.05 / ($luminance + 0.05);
        $contrastBlack = ($luminance + 0.05) / 0.05;

        return $contrastWhite >= $contrastBlack ? '#ffffff' : '#000000';
    }

    /**
     * Liefert alle Entscheidtypen sortiert für Dropdowns.
     */
    public static function getList(): array
    {
        return static::find()
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->select(['name'])
            ->indexBy('id')
            ->column() ?: [];
    }
}
