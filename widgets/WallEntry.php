<?php

namespace humhub\modules\sociolog\widgets;

use humhub\modules\content\widgets\stream\WallStreamEntryWidget;
use Yii;

/**
 * Zeigt Sociolog-Einträge im Stream an.
 */
class WallEntry extends WallStreamEntryWidget
{
    /**
     * Rendert den Inhalt des Stream-Eintrags.
     * In HumHub 1.18 wird das Objekt über $this->model bereitgestellt.
     */
    public function renderContent()
    {
        $entry = $this->model;   // statt $this->contentObject
        return $this->render('wallEntry', ['entry' => $entry]);
    }
}
