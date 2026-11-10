# Update auf Logbuch 1.0.37

Das Paket **sociolog-1.0.37.zip** enthält den Modulordner `sociolog`. Eine GitHub-Veröffentlichung aktualisiert deine HumHub-Installation nicht automatisch.

## Von Version 1.0.36

1. Datenbank, Dateien und bisherigen Modulordner sichern. Lokale Anpassungen vergleichen und das Update zuerst in einer Testumgebung prüfen.
2. Den bisherigen Modulordner vollständig durch den ZIP-Ordner `sociolog` ersetzen, gewöhnlich unter `protected/modules/sociolog`. Auch die neue Datei `models/InfoBoard.php` übernehmen. Kein zusätzliches `sociolog/sociolog` anlegen und das Modul nicht deinstallieren.
3. Die normale HumHub-Modulaktualisierung ausführen und den Cache über die Administration leeren. Die Modulversion muss **1.0.37** anzeigen. Gegenüber 1.0.36 ist keine neue Datenbankmigration erforderlich.
4. Unter **Moduleinstellungen → Informationsseite** die bisherige Kachelansicht kontrollieren. Die bestehenden Texte bleiben erhalten und Kacheln bleiben die Voreinstellung.
5. Bei Bedarf **Infotafel** auswählen, eigenen Text, Bilder und Links ergänzen und speichern. Ein Bild über das Einfügen-Menü des Editors hochladen. Die Informationsseite öffnen und kontrollieren.
6. Zurück zu **Kacheln** wechseln und speichern; die bisherigen Kacheltexte kontrollieren. Zur Infotafel zurückwechseln und Text sowie Bild erneut prüfen. Beide Ansichten speichern ihre Inhalte unabhängig voneinander.

Titel, Einleitung und Dokumentlink stehen in beiden Ansichten zur Verfügung. Nur die Administration verändert diese Moduleinstellungen. Die Infotafel bleibt von Beschlüssen und deren Überprüfungen getrennt.

## Ältere Versionen und Rücknahme

Die bisherigen Update- und Migrationshinweise gelten weiter: [UPDATE-1.0.36.md](UPDATE-1.0.36.md), bei Versionen vor 1.0.35 zusätzlich [UPDATE-1.0.35.md](UPDATE-1.0.35.md), bei älteren Ständen [UPDATE-1.0.33.md](UPDATE-1.0.33.md). Historische Migrationen nicht einzeln ausführen oder deren Protokolle manuell verändern.

Bei Rücknahme zuerst Datenbank und Dateien sichern und die vorherige vollständige Modulversion wiederherstellen. Diese Version ergänzt keine Spalten. Frühere Versionen zeigen die zusätzliche Infotafel nicht an; die ursprünglichen Kacheltexte bleiben gespeichert.

## Geprüfte Umgebung

Gemeinsame MAMP-Instanzen HumHub 1.18.7 und 1.19.3, PHP 8.3.30, MySQL 8.0.44: je 115 Tests / 6.421 Assertions, je ein versionsbedingt ausgelassener Test; keine Fehler. Je ein Browsertest / 19 Assertions erfolgreich. Die GitHub-Matrix und der konkrete Develop-Stand sind in den Release-Hinweisen dokumentiert. Kein Produktions-HTTPS/CSP-Test und keine Zusage für künftige Entwicklerversionen.

[Änderungen](AENDERUNGEN-1.0.37.md) · [Detaillierter Prüfbericht](docs/ai/INFOTAFEL-2026-11-10.txt).
