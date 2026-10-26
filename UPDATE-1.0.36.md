# Update auf Logbuch 1.0.36

Das Paket **sociolog-1.0.36.zip** enthält den Modulordner `sociolog`. Eine GitHub-Veröffentlichung aktualisiert deine HumHub-Installation nicht automatisch.

## Von Version 1.0.35

1. Datenbank, Dateien und bisherigen Modulordner sichern. Lokale Anpassungen vergleichen und das Update zuerst auf einer Testkopie prüfen.
2. Den bisherigen Modulordner vollständig durch den Inhalt des ZIP-Ordners `sociolog` ersetzen, gewöhnlich unter `protected/modules/sociolog`. Die neuen Unterordner `notifications/legacy` und `notifications/modern` müssen mitkopiert werden. Kein zusätzliches `sociolog/sociolog` anlegen und das Modul nicht deinstallieren.
3. Die normale HumHub-Modulaktualisierung ausführen und den Cache über die Administration leeren. Die Modulversion muss **1.0.36** anzeigen. Gegenüber 1.0.35 ist keine neue Datenbankmigration erforderlich.
4. Auf der Testinstallation einen Eintrag erstellen und ändern. Als berechtigte andere Person die Glockenbenachrichtigung und den Link zum Eintrag prüfen. Bei aktivierter Mailzustellung zusätzlich Betreff und Inhalt über das lokale Testpostfach prüfen. Empfängerauswahl und Leserechte müssen weiterhin gelten.
5. Vorhandene Einträge, Überprüfungen und Protokolle stichprobenartig kontrollieren. Diese Aktualisierung ändert deren Daten nicht.

## Ältere Versionen

Die bisherigen Migrations- und Sicherungshinweise gelten weiter. Insbesondere benötigt ein Update von vor 1.0.35 die damals ergänzte Spalte `replacement_url`. Zuerst [UPDATE-1.0.35.md](UPDATE-1.0.35.md) sowie bei älteren Ständen [UPDATE-1.0.33.md](UPDATE-1.0.33.md) lesen. Historische Migrationen nicht einzeln ausführen oder ihre Protokolle manuell verändern.

## Unterstützte Prüfstände

Gemeinsame MAMP-Testinstanzen HumHub 1.18.6 und 1.19.0, PHP 8.3.14, MySQL 8.0.40. Syntax und Develop-Quellvertrag zusätzlich unter PHP 8.5.2 geprüft. Die GitHub-Stable-/Develop-Matrix und der konkrete Develop-Commit sind in den Release-Hinweisen dokumentiert. Entwicklerversionen künftig vor Einsatz erneut prüfen.

Vorhandene Einschränkungen bei Tabellenpräfixen, nativer Suche/Streams und historischen Null-Datumswerten bleiben bestehen. Mails wurden nicht an externe Empfänger zugestellt. Bei Rücknahme die vorherige gesicherte Modulversion vollständig wiederherstellen; dieses Update ergänzt keine Spalten.

[Änderungen](AENDERUNGEN-1.0.36.md) · [Detaillierter Prüfbericht](docs/ai/BENACHRICHTIGUNGEN-DEVELOP-2026-10-26.txt).
