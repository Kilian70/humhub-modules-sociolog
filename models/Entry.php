<?php

namespace humhub\modules\sociolog\models;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\behaviors\BlameableBehavior;
use humhub\modules\user\models\User;
use humhub\modules\space\models\Membership;
use humhub\modules\user\models\GroupUser;
use humhub\modules\sociolog\models\SpaceConfig;
use humhub\modules\sociolog\models\Organ;
use humhub\modules\sociolog\models\Protocol;
use humhub\modules\space\models\Space;
use humhub\modules\sociolog\models\DecisionType;
use humhub\modules\content\components\ContentContainerPermissionManager;
use humhub\modules\sociolog\permissions\CreateEntry;
use humhub\modules\sociolog\permissions\UpdateEntry;
use humhub\modules\sociolog\permissions\DeleteEntry;

/**
 * ============================================================
 * 🧭 Entry – Web-Modell (erbt EntryBase)
 * ------------------------------------------------------------
 * UI-, Rechte- und Hilfsmethoden.
 * Stream- & Kalenderfunktionen über Services ausgelagert.
 * ============================================================
 */
class Entry extends EntryBase
{
    /**
     * WallEntry-Renderer für Stream-Einträge
     */
    public $wallEntryClass = 'humhub\modules\sociolog\widgets\WallEntry';

    /** Unterdrückt Benachrichtigungen, Kalender- und aktuelle Stream-Aktivität beim Altimport. */
    public bool $historicalImport = false;

    private static ?array $organCache = null;

    // Cache für Spaces
    private static array $spaceCache = [];

    // Cache für SpaceConfig
    private static array $spaceConfigCache = [];

    private static function getSpaceByName(string $name)
    {
        if (!isset(self::$spaceCache['name'][$name])) {
            self::$spaceCache['name'][$name] = \humhub\modules\space\models\Space::find()
                ->where(['name' => $name])
                ->one();
        }

        return self::$spaceCache['name'][$name];
    }

    private static function getSpaceById(int $id)
    {
        if (!isset(self::$spaceCache['id'][$id])) {
            self::$spaceCache['id'][$id] = \humhub\modules\space\models\Space::findOne($id);
        }

        return self::$spaceCache['id'][$id];
    }

private static function getSpaceConfigBySpaceId(int $spaceId)
    {
        if (!isset(self::$spaceConfigCache[$spaceId])) {
            self::$spaceConfigCache[$spaceId] = SpaceConfig::find()
                ->where(['space_id' => $spaceId])
                ->one();
        }

        return self::$spaceConfigCache[$spaceId];
    }

private static function resolveOrgan(?int $spaceId): ?array
{
    if (!$spaceId) {
        return null;
    }

    $space = self::getSpaceById((int)$spaceId);

    if (!$space) {
        return null;
    }

    $config = self::getSpaceConfigBySpaceId((int)$space->id);

    if (!$config || !$config->enabled) {
        return null;
    }

    $organ = null;

    if ($config->organ_id) {
        $organ = Organ::findOne($config->organ_id);
    }

    return [
        'space'  => $space,
        'config' => $config,
        'organ'  => $organ,
    ];
}

/**
 * Prüft eine Sociolog-Permission im Kontext eines HumHub-Spaces.
 */
private static function canInSpace(Space $space, string $permissionClass, User $user): bool
{
    $permissionManager = new ContentContainerPermissionManager([
        'subject' => $user,
        'contentContainer' => $space,
    ]);

    return $permissionManager->can(new $permissionClass());
}

// ============================================================
// 🔹 Behaviors
// ============================================================
public function behaviors(): array
{
    $behaviors = parent::behaviors();

    $behaviors['blameable'] = [
        'class' => BlameableBehavior::class,
        'createdByAttribute' => 'created_by',
        'updatedByAttribute' => 'updated_by',
        'value' => function () {
            return Yii::$app->user && !Yii::$app->user->isGuest
                ? Yii::$app->user->id
                : null;
        },
    ];

    return $behaviors;
}

// ============================================================
// 🔹 Relationen (immer optional behandeln!)
// ============================================================
public function getCreator()
{
    return $this->hasOne(User::class, ['id' => 'created_by']);
}

public function getEditor()
{
    return $this->hasOne(User::class, ['id' => 'updated_by']);
}

public function getDecisionType()
{
    return $this->hasOne(DecisionType::class, ['id' => 'decision_type_id']);
}
 

    // ============================================================
    // 🔹 Status-Badge (NULL- & zukunftssicher)
    // ============================================================
    public function getStatusBadge(): string
    {
        return self::getStatusBadgeForStatus((string)$this->status);
    }

    /**
     * Rendert Statusfarben unabhängig von den Bootstrap-Klassen des Themes.
     */
    public static function getStatusBadgeForStatus(string $status): string
    {
        $cfg = self::getStatusConfig()[$status] ?? null;

        if (!$cfg) {
            return Html::tag('span', Html::encode(Yii::t('SociologModule.base', 'Unbekannt')), [
                'class' => 'badge sociolog-status-badge badge-sociolog-pending',
            ]);
        }

        $classes = [
            self::STATUS_AUTO => 'badge-sociolog-pending',
            self::STATUS_PENDING => 'badge-sociolog-pending',
            self::STATUS_VALID => 'badge-sociolog-valid',
            self::STATUS_REVIEW => 'badge-sociolog-review',
            self::STATUS_EXPIRED => 'badge-sociolog-expired',
            self::STATUS_OBJECTION => 'badge-sociolog-objection',
            self::STATUS_REPLACED => 'badge-sociolog-replaced',
        ];

        return Html::tag('span', Html::encode((string)$cfg['label']), [
            'class' => 'badge sociolog-status-badge '
                . ($classes[$status] ?? 'badge-sociolog-pending'),
        ]);
    }

// ============================================================
// 🔐 BERECHTIGUNGEN
// ============================================================


///////////////////////////////////////////////////////////////
// ➜ GLOBAL ERSTELLEN (z.B. "+ Neuer Eintrag")
///////////////////////////////////////////////////////////////
public static function canCreateGlobal($user = null, $targetOrgan = null): bool
{
    $user = $user ?: (Yii::$app->user->identity ?? null);

    if (!$user) {
        return false;
    }

    // Dieselbe Liste verwenden wie das Formular. Damit kann ein
    // manipuliertes organ-Feld keine zusätzlichen Rechte verleihen.
    $writableOrgans = static::getWritableOrgansForUser($user);

    if ($targetOrgan === null) {
        return $writableOrgans !== [];
    }

    return array_key_exists((int)$targetOrgan, $writableOrgans);
}


///////////////////////////////////////////////////////////////
// ➜ SCHREIBEN / BEARBEITEN
///////////////////////////////////////////////////////////////
public function canWrite($user = null): bool
{
    $user = $user ?: (Yii::$app->user->identity ?? null);

    if (!$user) {
        return false;
    }

    // --------------------------------------------------------
    // 1️⃣ SYSTEM ADMIN
    // --------------------------------------------------------
    if (Yii::$app->user->isAdmin()) {
        return true;
    }

    // Optionaler Veröffentlichungs-Schutz. Standardmässig ist er aus,
    // damit das bisherige Rechteverhalten vollständig erhalten bleibt.
    $lockPublished = (bool)Yii::$app->getModule('sociolog')
        ->settings
        ->get('lockPublishedEntries', false);

    if ($lockPublished && !empty($this->published_at)) {
        return static::isLogbookManager($user);
    }

    // --------------------------------------------------------
    // 2️⃣ BENUTZER MIT SCHREIBRECHT
    // --------------------------------------------------------
    $writerUsers = (array)(
        Yii::$app->getModule('sociolog')->settings->getSerialized('writerUsers') ?? []
    );

    if (in_array($user->guid, $writerUsers, true)) {
        return true;
    }

    // --------------------------------------------------------
    // 3️⃣ GRUPPEN MIT SCHREIBRECHT
    // --------------------------------------------------------
    $writerGroups = array_filter(array_map(
        'intval',
        (array)(Yii::$app->getModule('sociolog')->settings->getSerialized('writerGroups') ?? [])
    ));

    if (!empty($writerGroups)) {

        $userGroupIds = GroupUser::find()
            ->select('group_id')
            ->where(['user_id' => (int)$user->id])
            ->column();

        if (!empty(array_intersect($userGroupIds, $writerGroups))) {
            return true;
        }
    }

    // --------------------------------------------------------
    // 4️⃣ AKTUELLES ENTSCHEIDUNGSORGAN
    // --------------------------------------------------------
    if (static::canCreateGlobal($user, $this->getDecisionOrgan())) {
        return true;
    }

    $currentSpace = self::getSpaceById((int)$this->getDecisionOrgan());

    if ($currentSpace instanceof Space
        && self::canInSpace($currentSpace, UpdateEntry::class, $user)) {
        return true;
    }

    return false;
}

public static function isLogbookManager($user = null): bool
{
    $user = $user ?: (Yii::$app->user->identity ?? null);

    if (!$user) {
        return false;
    }

    if (Yii::$app->user->isAdmin()) {
        return true;
    }

    $settings = Yii::$app->getModule('sociolog')->settings;
    $managerUsers = (array)($settings->getSerialized('managerUsers') ?? []);

    if (in_array($user->guid, $managerUsers, true)) {
        return true;
    }

    $managerGroups = array_filter(array_map(
        'intval',
        (array)($settings->getSerialized('managerGroups') ?? [])
    ));

    if ($managerGroups === []) {
        return false;
    }

    $userGroupIds = GroupUser::find()
        ->select('group_id')
        ->where(['user_id' => (int)$user->id])
        ->column();

    return array_intersect($userGroupIds, $managerGroups) !== [];
}

///////////////////////////////////////////////////////////////
// ➜ EDIT (HumHub Standardfunktion)
///////////////////////////////////////////////////////////////
public function canEdit($user = null): bool
{
    return $this->canWrite($user);
}

/**
 * Darf ausschließlich das nächste Überprüfungsdatum pflegen und ein
 * zusätzliches Protokoll verlinken.
 */
public function canMaintainReview($user = null): bool
{
    $user = $user ?: (Yii::$app->user->identity ?? null);

    if (!$user) {
        return false;
    }

    if ($this->canWrite($user)) {
        return true;
    }

    $enabled = (bool)Yii::$app->getModule('sociolog')
        ->settings
        ->get('limitedReviewMaintenanceEnabled', false);

    return $enabled
        && !empty($this->published_at)
        && !empty($this->review_date)
        && (string)$this->review_date <= date('Y-m-d')
        && static::canCreateGlobal($user, (int)$this->getDecisionOrgan());
}


///////////////////////////////////////////////////////////////
// ➜ LÖSCHEN
///////////////////////////////////////////////////////////////
public function canDelete($user = null): bool
{
    $user = $user ?: (Yii::$app->user->identity ?? null);

    if (!$user) {
        return false;
    }

    // --------------------------------------------------------
    // 1️⃣ SYSTEM ADMIN
    // --------------------------------------------------------
    if (Yii::$app->user->isAdmin()) {
        return true;
    }

    // --------------------------------------------------------
    // 2️⃣ BENUTZER MIT LÖSCHRECHT (MODULE SETTINGS)
    // --------------------------------------------------------
    $deleterUsers = (array)(
        Yii::$app->getModule('sociolog')->settings->getSerialized('deleterUsers') ?? []
    );

    if (is_array($deleterUsers) && in_array($user->guid, $deleterUsers, true)) {
        return true;
    }

    // --------------------------------------------------------
    // 3️⃣ GRUPPEN MIT LÖSCHRECHT
    // --------------------------------------------------------
    $deleterGroups = array_filter(array_map(
        'intval',
        (array)(Yii::$app->getModule('sociolog')->settings->getSerialized('deleterGroups') ?? [])
    ));

    if (!empty($deleterGroups)) {

        $userGroupIds = GroupUser::find()
            ->select('group_id')
            ->where(['user_id' => (int)$user->id])
            ->column();

        if (!empty(array_intersect($userGroupIds, $deleterGroups))) {
            return true;
        }
    }

    // --------------------------------------------------------
    // 4️⃣ SPACE KONFIGURATION
    // --------------------------------------------------------
    $spaces = SpaceConfig::find()
        ->where(['enabled' => 1])
        ->with('space')
        ->all();

    foreach ($spaces as $config) {

        if (!$config->can_delete) {
            continue;
        }

        $space = $config->space;

        if (!$space) {
            continue;
        }

        if (!self::canInSpace($space, DeleteEntry::class, $user)) {
            continue;
        }

        // globales Schreiben erlaubt
        if ($config->global_write) {
            return true;
        }

        // Organ stimmt überein
        if ((int)$space->id === (int)$this->getDecisionOrgan()) {
            return true;
        }
    }

    return false;
}

    // ============================================================
    // 🔹 Listen & Helper
    // ============================================================
    public static function getAvailableYears(): array
    {
        $years = static::find()
            ->publishedOrLegacy()
            ->select(['YEAR(decision_date) AS year'])
            ->where(['IS NOT', 'decision_date', null])
            ->distinct()
            ->orderBy(['year' => SORT_DESC])
            ->column();

        if (empty($years)) {
            $current = date('Y');
            return [$current => $current];
        }

        return array_combine($years, $years);
    }

public static function getOrganList(): array
{
    if (self::$organCache !== null) {
        return self::$organCache;
    }

    $configs = SpaceConfig::find()
        ->where(['enabled' => 1])
        ->all();

    $list = [];

    foreach ($configs as $config) {

        $space = self::getSpaceById((int)$config->space_id);

        if ($space) {
            $list[$space->id] = $space->name;
        }
    }

    ksort($list, SORT_NATURAL | SORT_FLAG_CASE);

    return self::$organCache = $list;
}

    public static function getDecisionTypeList(?int $includeId = null, bool $includeHidden = false): array
    {
        $types = DecisionType::find()->orderBy(['sort_order' => SORT_ASC])->all();
        $result = [];
        $hiddenIds = [];

        if (!$includeHidden) {
            $settings = Yii::$app->getModule('sociolog')->settings;
            $hiddenIds = array_map(
                'intval',
                (array)($settings->getSerialized('hiddenDecisionTypeIds') ?? [])
            );
        }

        foreach ($types as $type) {
            if (
                in_array((int)$type->id, $hiddenIds, true)
                && (int)$type->id !== (int)$includeId
            ) {
                continue;
            }

            $name = trim((string)$type->name);
            if ($name !== '') {
                $result[$type->id] = $name;
            }
        }

        asort($result, SORT_NATURAL | SORT_FLAG_CASE);
        return $result;
    }

    public static function getOrganColor(?string $organ): string
    {
        if (!$organ) {
            return '#6c757d';
        }

        $settings = Yii::$app->getModule('sociolog')->settings;
        $raw = trim((string)$settings->get('organColors', ''));

        if ($raw !== '') {
            foreach (preg_split('/[\r\n]+/', $raw) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $line = str_replace([',', ';'], ':', $line);

                if (strpos($line, ':') !== false) {
                    [$name, $color] = array_map('trim', explode(':', $line, 2));
                    if (mb_strtolower($name) === mb_strtolower($organ) && $color !== '') {
                        return strpos($color, '#') === 0 ? $color : "#{$color}";
                    }
                }
            }
        }

        $palette = [
            '#A7C7E7', '#A8E6CF', '#FFD3B6', '#FFAAA5',
            '#D4A5A5', '#CBAACB', '#F7B7A3', '#B5EAD7',
            '#E2F0CB', '#F6EAC2', '#B7CECE', '#D6CDEA',
        ];

        $organs = static::getOrganList();
        $index = array_search($organ, $organs, true);

        if ($index !== false && isset($palette[$index])) {
            return $palette[$index];
        }

        $fallback = [
            '#F7BFBF', '#F7D7BF', '#F7E8BF', '#F7F7BF',
            '#E8F7BF', '#D7F7BF', '#BFF7BF', '#BFF7D7',
            '#BFF7E8', '#BFF7F7', '#BFE8F7', '#BFD7F7',
            '#BFBFF7', '#D7BFF7', '#E8BFF7', '#F7BFF7',
            '#F7BFE8', '#F7BFD7',
        ];

        return $fallback[abs(crc32(mb_strtolower($organ))) % count($fallback)];
    }

public static function getOrganLink(?int $spaceId): ?string
{
    if (!$spaceId) {
        return null;
    }

    $resolved = self::resolveOrgan((int)$spaceId);

    if (!$resolved) {
        return null;
    }

    $space  = $resolved['space'];
    $config = $resolved['config'];

    $spaceUrl = rtrim($space->getUrl(), '/');

    switch ($config->link_mode) {

        case 'space':
            return $spaceUrl;

        case 'about':
            return $spaceUrl . '/about';

        case 'custom':
            return $config->link ?: null;

        default:
            return null;
    }
}

public static function getWritableOrgansForUser($user = null): array
{
    $user = $user ?: (Yii::$app->user->identity ?? null);
    if (!$user) {
        return [];
    }

    // System Admin → alle Organe
    if (Yii::$app->user->isAdmin()) {
        return static::getOrganList();
    }

    $normalize = fn($v) => trim(mb_strtolower((string)$v));

    $organs = static::getOrganList();


// --------------------------------------------------------
// Benutzer mit Schreibrecht (GUID)
// --------------------------------------------------------
$writerUsers = (array)(
    Yii::$app->getModule('sociolog')->settings->getSerialized('writerUsers') ?? []
);

if (in_array($user->guid, $writerUsers, true)) {
    return $organs;
}

    // --------------------------------------------------------
    // Gruppen mit Schreibrecht
    // --------------------------------------------------------
    $writerGroups = array_filter(array_map(
        'intval',
        (array)(Yii::$app->getModule('sociolog')->settings->getSerialized('writerGroups') ?? [])
    ));

    if (!empty($writerGroups)) {

        $userGroupIds = GroupUser::find()
            ->select('group_id')
            ->where(['user_id' => (int)$user->id])
            ->column();

        if (!empty(array_intersect($userGroupIds, $writerGroups))) {
            return $organs;
        }
    }

// --------------------------------------------------------
// Space-Mitgliedschaften prüfen
// --------------------------------------------------------
$memberships = Membership::find()
    ->with('space')
    ->where(['user_id' => (int)$user->id])
    ->all();

$allowed = [];

foreach ($memberships as $membership) {

    $space = $membership->space ?? null;

    if (!$space || !$space->name) {
        continue;
    }

    // Nur Spaces berücksichtigen, die im Logbuch aktiviert sind
    $config = self::getSpaceConfigBySpaceId((int)$space->id);

    if (!$config || !$config->enabled) {
        continue;
    }

    // HumHub-Container-Permission entscheidet. Standardmässig sind
    // Space-Eigentümer und Space-Administratoren berechtigt.
    if (!self::canInSpace($space, CreateEntry::class, $user)) {
        continue;
    }

    // Globaler Space → darf in allen Organen schreiben
    if ($config->global_write) {
        return $organs;
    }

    // Organ entspricht dem Space
	if (isset($organs[$space->id])) {
		$allowed[$space->id] = $organs[$space->id];
	}
}

return $allowed;
}

// ============================================================
// 🔹 URL
// ============================================================
public function getUrl(): string
{
    try {
        return Url::to(['/sociolog/entry/view', 'id' => $this->id], true);
    } catch (\Throwable $e) {
        return '#';
    }
}

/**
 * ============================================================
 * 🔹 Automatische Berechnung vor dem Speichern
 * ------------------------------------------------------------
 * Logik:
 * 1️⃣ Wenn ein Entscheid beschlossen wurde → Veröffentlichung setzen
 * 2️⃣ Wenn Veröffentlichung existiert → Inkrafttreten berechnen
 *
 * Inkrafttreten = Ende Einsprachefrist + 1 Tag
 * Einsprachefrist wird über Modul-Einstellung gesteuert
 * ============================================================
 */
public function beforeSave($insert)
{
    if (!parent::beforeSave($insert)) {
        return false;
    }

    // =====================================================
    // 🔹 Startorgan setzen
    // =====================================================

    if ($insert && empty($this->current_organ) && !empty($this->organ)) {
        $this->current_organ = (int)$this->organ;
    }


    // =====================================================
    // 🔹 Veröffentlichung setzen (falls Entscheid existiert)
    // =====================================================

    if (!empty($this->decision_date) && empty($this->published_at)) {
		$this->published_at = date('Y-m-d');
	}


    // =====================================================
    // 🔹 Inkrafttreten berechnen
    // =====================================================

    if (!empty($this->published_at) && empty($this->effective_date)) {

        $days = (int) Yii::$app->getModule('sociolog')
            ->settings
            ->get('defaultEffectiveDays', 10);

        $addExtraDay = (bool)Yii::$app->getModule('sociolog')
            ->settings
            ->get('effectiveDateAddExtraDay', true);
        $extraDay = $addExtraDay ? ' +1 day' : '';
        $effective = strtotime($this->published_at . " +{$days} days" . $extraDay);

        $this->effective_date = date('Y-m-d', $effective);
    }


// =====================================================
// 🔹 Automatische Statuslogik
// =====================================================

if (!self::isManualProtectedStatus((string)$this->status)) {

    $today = date('Y-m-d');

    // Überprüfung erreicht → REVIEW
    if (!empty($this->review_date) && $this->review_date <= $today) {

        $this->status = self::STATUS_REVIEW;

    }

    // Noch nicht in Kraft → PENDING
    elseif (
        !empty($this->effective_date) &&
        $this->effective_date > $today
    ) {

        $this->status = self::STATUS_PENDING;

    }

    // Inkrafttreten erreicht → VALID
    elseif (
        !empty($this->effective_date) &&
        $this->effective_date <= $today
    ) {

        $this->status = self::STATUS_VALID;

    }

}

    return true;
}

// ============================================================
// 🔹 afterSave / afterDelete (defensiv)
// ============================================================

public function afterSave($insert, $changedAttributes)
{
    parent::afterSave($insert, $changedAttributes);

    if ($this->historicalImport) {
        return;
    }

    $module = Yii::$app->getModule('sociolog');

    if (!$module) {
        Yii::warning('Sociolog: Modul fehlt in afterSave()', 'sociolog');
        return;
    }

    $calendarEnabled = (bool)$module->settings->get('showReviewInCalendar', false);

    try {

        \humhub\modules\sociolog\services\SociologStreamService::onAfterSave($this);

        if ($calendarEnabled) {

            \humhub\modules\sociolog\services\SociologCalendarService::onAfterSave($this);

        }

    } catch (\Throwable $e) {

        Yii::error(
            "Sociolog afterSave Fehler (ID {$this->id}): " . $e->getMessage(),
            'sociolog'
        );

    }
}


public function afterDelete()
{
    parent::afterDelete();

    Yii::info(
        "afterDelete Entry {$this->id} gestartet",
        'sociolog.calendar'
    );

    try {
        // Den zugehörigen Content-/Stream-Datensatz entfernt bereits
        // ContentActiveRecord::afterDelete(). Hier bleibt nur die
        // modulbezogene Kalenderbereinigung.
        \humhub\modules\sociolog\services\SociologCalendarService::deleteByEntryId($this->id);

    } catch (\Throwable $e) {

        Yii::error(
            "Sociolog afterDelete Fehler (ID {$this->id}): " . $e->getMessage(),
            'sociolog'
        );

    }
}
    
/**
 * ============================================================
 * 🔹 Aktuelles Entscheidungsorgan bestimmen
 * ------------------------------------------------------------
 * Reihenfolge:
 * 1️⃣ current_organ → wenn Entscheid bereits weitergereicht wurde
 * 2️⃣ organ → ursprüngliches Entscheidungsorgan
 * ============================================================
 */

public function getDecisionOrgan(): ?int
{
    if (!empty($this->current_organ)) {
        return (int)$this->current_organ;
    }

    if (!empty($this->organ)) {
        return (int)$this->organ;
    }

    return null;
}

/**
 * ============================================================
 * 🔹 Nächstes Entscheidungsorgan bestimmen
 * ============================================================
 */
public function getNextOrgan(): ?int
{
    $spaceId = $this->getDecisionOrgan();
    if (!$spaceId) {
        return null;
    }

    $config = self::getSpaceConfigBySpaceId((int)$spaceId);
    if (!$config || !$config->organ_id) {
        return null;
    }

    // aktuelles Organ
    $organ = Organ::findOne((int)$config->organ_id);
    if (!$organ) {
        return null;
    }

    /*
     * Wenn aktueller Space NICHT der Organ-Space ist
     * → zuerst zum Organ-Space desselben Organs
     */
    if ($organ->organ_space_id && (int)$organ->organ_space_id !== (int)$spaceId) {
        return (int)$organ->organ_space_id;
    }

    /*
     * Wenn bereits im Organ-Space
     * → Parent-Organ
     */
    if (!$organ->parent_id) {
        return null;
    }

    $parent = Organ::findOne((int)$organ->parent_id);

    if (!$parent || !$parent->organ_space_id) {
        return null;
    }

    return (int)$parent->organ_space_id;
}

/**
 * Vorheriges Organ im Workflow bestimmen
 */
public function getPreviousOrgan()
{
    $flow = EntryFlow::find()
        ->where(['entry_id' => $this->id])
        ->orderBy(['id' => SORT_DESC])
        ->one();

    if (!$flow) {
        return null;
    }

    $space = \humhub\modules\space\models\Space::findOne($flow->from_organ_id);

    return $space ? $space->name : null;
}


// ============================================================
// 🔹 Organ-Anzeige (UI)
// ============================================================

public function getOrganLabel(): ?string
{
    if ($this->organName) {
        return $this->organName;
    }

    return Yii::t('SociologModule.base', '(ohne Bereich)');
}


/**
 * ============================================================
 * 🔹 Entscheidungs-Kette erzeugen
 * ------------------------------------------------------------
 * Beispiel Ergebnis:
 *
 * [
 *   "BG Sicherheit",
 *   "BK Unterhalt",
 *   "Leitungskreis",
 *   "Hausverein"
 * ]
 *
 * ============================================================
 */
public function getDecisionChain(): array
{
    $chain = [];

    $current = $this->getDecisionOrgan(); // Space-ID

    while ($current) {

        $space = self::getSpaceById((int)$current);

        if (!$space) {
            break;
        }

        // Für Anzeige speichern
        $chain[] = $space->name;

        $config = self::getSpaceConfigBySpaceId((int)$space->id);

        if (!$config || !$config->organ) {
            break;
        }

        $organ = $config->organ;

        // Parent Organ suchen
        if (!$organ->parent_id) {
            break;
        }

        $parentOrgan = Organ::findOne($organ->parent_id);

        if (!$parentOrgan) {
            break;
        }

        $parentConfig = SpaceConfig::find()
            ->where([
                'organ_id' => $parentOrgan->id,
                'is_organ_space' => 1
            ])
            ->one();

        if (!$parentConfig) {
            break;
        }

        $current = $parentConfig->space_id; // wieder ID
    }

    return $chain;
}

public function getOrganName(): ?string
{
    $spaceId = $this->getDecisionOrgan();

    if (!$spaceId) {
        return null;
    }

    $space = self::getSpaceById((int)$spaceId);

    return $space ? $space->name : null;
}

/**
 * ============================================================
 * 🔹 Beschluss fassen
 * ============================================================
 */
public function makeDecision(): void
{
    $this->decision_date = date('Y-m-d');

    // Einsprachefrist läuft → noch nicht gültig
    $this->status = self::STATUS_PENDING;

    // Veröffentlichung neu berechnen lassen
    $this->published_at = null;

    // Inkrafttreten neu berechnen lassen
    $this->effective_date = null;
}
/**
 * ============================================================
 * 🔹 Entscheid wieder öffnen
 * ------------------------------------------------------------
 * Wird verwendet wenn ein Beschluss:
 * - weitergeleitet wird (forward)
 * - zurückgegeben wird (return)
 *
 * In diesem Fall ist der Entscheid wieder offen
 * und darf kein Beschlussdatum oder Inkrafttreten haben.
 * ============================================================
 */
public function reopenDecision(): void
{
    $this->status = self::STATUS_PENDING;

    $this->decision_date = null;
    $this->published_at = null;
    $this->effective_date = null;
}

/**
 * ============================================================
 * Protocol
 * ============================================================
 */
public function getProtocols()
{
    return $this->hasMany(Protocol::class, ['entry_id' => 'id'])
        ->orderBy(['id' => SORT_ASC]);
}


}
