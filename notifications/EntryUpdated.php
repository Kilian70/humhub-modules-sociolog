<?php

namespace humhub\modules\sociolog\notifications;

use humhub\modules\notification\components\BaseNotification;
use Yii;

/**
 * ============================================================
 * ✏️ EntryUpdated Notification
 * ------------------------------------------------------------
 * Wird ausgelöst, wenn ein bestehender Logbuch-Eintrag geändert wurde.
 * - Zeigt modernes Glockenlayout mit Profilbild und Link
 * - Enthält Betreff und Beschreibung für Mail & Web
 * ============================================================
 */
class EntryUpdated extends BaseNotification
{
    /** Modul-ID für Filter und Kategorien */
    public $moduleId = 'sociolog';

    /** Web-Benachrichtigung (Glocke) */
    public $viewName = '@humhub/modules/sociolog/views/notifications/entryUpdated';

    /** Standard-Mail aktivieren (HumHub verwendet eigenes Template) */
    public $sendMail = true;

    /**
     * 📬 Parameter fürs Mail-Template
     */
    public function getMailViewParams()
    {
        return [
            'notification' => $this,
            'entry' => $this->source,
        ];
    }

    /**
     * 📨 Betreffzeile für Mail
     */
    public function getMailSubject()
    {
        $moduleName = Yii::$app->getModule('sociolog')
            ->settings->get('moduleTitle', 'Logbuch');
        $title = $this->source
            ? $this->source->title
            : Yii::t('SociologModule.base', 'Eintrag');

        return Yii::t('SociologModule.base',
            'Eintrag im {module} wurde aktualisiert: {title}',
            ['module' => $moduleName, 'title' => $title]
        );
    }

    /**
     * 🔔 Titel für Glocke (oben)
     */
    public function getTitle()
    {
        $moduleName = Yii::$app->getModule('sociolog')
            ->settings->get('moduleTitle', 'Logbuch');

        return Yii::t('SociologModule.base',
            'Eintrag im {module} wurde aktualisiert',
            ['module' => $moduleName]
        );
    }

    /**
     * 🧾 Beschreibung unter dem Glocken-Titel
     */
    public function getDescription()
    {
        $entry = $this->source;
        if (!$entry) {
            return Yii::t('SociologModule.base', 'Ein Eintrag wurde geändert.');
        }

        $title = trim((string)($entry->title ?? ''));
        $user  = $entry->editor->displayName
            ?? $entry->creator->displayName
            ?? Yii::t('SociologModule.base', 'Unbekannt');

        return Yii::t('SociologModule.base',
            '„{title}“ wurde aktualisiert von {user}.',
            [
                'title' => $title !== '' ? $title : Yii::t('SociologModule.base', 'Eintrag'),
                'user'  => $user,
            ]
        );
    }

    /**
     * 🔗 Ziel-URL beim Klick in Glocke oder Mail
     */
    public function getUrl()
    {
        return $this->source
            ? $this->source->getUrl()
            : '#';
    }

    /**
     * 🗂️ Kategorie für Benachrichtigungen
     */
    public function category()
    {
        return new SociologNotificationCategory();
    }

    /**
     * 🎨 Erzwingt Rendering über eigenes Template
     */
    public function render($mode = null, $params = [])
    {
        return Yii::$app->view->renderFile(
            Yii::getAlias('@humhub/modules/sociolog/views/notifications/entryUpdated.php'),
            ['object' => $this, 'source' => $this->source]
        );
    }
    
    public function isDigestable()
{
    return true;
}
}
