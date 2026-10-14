<?php

namespace humhub\modules\sociolog\notifications;

use Yii;
use yii\helpers\Html;

/** Shared content for the native HumHub 1.18/1.19 MailTarget renderer. */
trait NotificationMailContent
{
    public function html()
    {
        return Yii::$app->view->renderFile(
            __DIR__ . '/../views/notifications/mail.php',
            $this->getMailViewParams()
        );
    }

    public function text()
    {
        return $this->getTitle() . "\n" . $this->getDescription()
            . ($this->source ? "\n" . $this->getUrl() : '');
    }

    public function getViewParams($params = [])
    {
        $result = parent::getViewParams($params);
        // The native plain-mail renderer strips tags, then decodes entities.
        // Encode at the template boundary to retain literal <tags> and &amp;.
        // text() itself stays plain for live notifications and other consumers.
        $result['text'] = Html::encode((string)$result['text']);
        return $result;
    }
}
