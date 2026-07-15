<?php

namespace humhub\modules\sociolog\notifications;

use humhub\modules\notification\components\BaseNotification;
use humhub\modules\sociolog\models\Entry;
use Yii;

/**
 * ============================================================
 * 🔔 EntryCreated
 * ------------------------------------------------------------
 * Wird gesendet, wenn ein neuer Logbuch-Eintrag erstellt wird.
 * - Zeigt modernes Glockenlayout mit farbigem Icon
 * - Enthält Betreff und Inhalt für Mail & Web
 * ============================================================
 */
class EntryCreated extends BaseNotification
{
    /** Modul-ID für Filter und Kategorien */
    public $moduleId = 'sociolog';

    /** Web-Benachrichtigung (Glocke) */
    public $viewName = '@humhub/modules/sociolog/views/notifications/entryCreated';

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
            'Neuer Eintrag im {module}: {title}',
            ['module' => $moduleName, 'title' => $title]
        );
    }

    /**
     * 🔔 Titel in der Glocke (oben fett)
     */
    public function getTitle()
    {
        $moduleName = Yii::$app->getModule('sociolog')
            ->settings->get('moduleTitle', 'Logbuch');

        return Yii::t('SociologModule.base',
            'Neuer Eintrag im {module}',
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
            return Yii::t('SociologModule.base', 'Eintrag im Logbuch erstellt.');
        }

        $title = trim((string)($entry->title ?? ''));
        $user  = $entry->creator->displayName ?? Yii::t('SociologModule.base', 'Unbekannt');

        return Yii::t('SociologModule.base',
            '„{title}“ wurde erstellt von {user}.',
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
            Yii::getAlias('@humhub/modules/sociolog/views/notifications/entryCreated.php'),
            ['object' => $this, 'source' => $this->source]
        );
    }
    
/**
 * 📬 Darf in der täglichen Aktivitäts-Zusammenfassung erscheinen
 */
public function isDigestable()
{
    return true;
}
}
