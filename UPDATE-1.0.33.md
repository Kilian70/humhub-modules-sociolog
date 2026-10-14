# Update auf Logbuch 1.0.33

Diese Anleitung beschreibt das Update auf Version 1.0.33. Die Veröffentlichung des Pakets aktualisiert keine bestehende Installation automatisch.

## Vorbereiten

1. Die gesamte HumHub-Datenbank und die Dateien einschließlich `uploads`, Konfiguration und bisherigem Modul sichern. Prüfen, dass die Sicherung wiederherstellbar ist.
2. Das Update zuerst auf einer getrennten Kopie durchführen. Geprüfte Versionen: HumHub 1.18.6 und offizielle 1.19.0, PHP 8.3.30, MySQL 8.0.44. Das ist kein gleichzeitiges HumHub-Core-Update.
3. Schreibzugriffe während des Updates stoppen. Den vorhandenen Modulordner sichern und die neuen Moduldateien vollständig einsetzen. Persönliche lokale Anpassungen vorher vergleichen und erhalten. Das Modul nicht deinstallieren.

Der Paketordner heißt `sociolog`. Er gehört an die Stelle des bisherigen Sociolog-Modulordners, gewöhnlich `protected/modules/sociolog`. Nicht einen zweiten Ordner darin verschachteln.

## Vorprüfung

Vom HumHub-Hauptordner aus mit der PHP-Version der Installation ausführen:

```sh
php protected/modules/sociolog/upgrade.php
```

Ohne Zusatzargument prüft dieser Einstieg den Updatezustand und zeigt ausstehende Migrationen an. Er ändert keine Fachdaten. Bei MAMP gegebenenfalls den vollständigen Pfad zur dort verwendeten PHP-Version einsetzen.

## Bereits vollständig migrierte Installation, beispielsweise 1.0.32

Die normale HumHub-Modulaktualisierung durchführen. 1.0.33 ergänzt zwei Migrationen: eine Schutzprüfung vor historischen Schritten und eine additive Archivtabelle für gesicherte Altupdates. Bestehende Beschlüsse werden dadurch nicht bereinigt oder pauschal archiviert.

Falls HumHub stattdessen auf den geschützten Updater verweist, nicht versuchen, die Meldung mit einem globalen Migrationslauf oder einzeln gestarteten alten Migrationen zu umgehen. Dann den folgenden Abschnitt verwenden.

## Ältere Installation mit noch ausstehenden historischen Schritten

Nach vollständiger Sicherung und erfolgreicher Vorprüfung den geschützten Modul-Updater verwenden:

```sh
php protected/modules/sociolog/upgrade.php --apply
```

Dieser Weg erhält alte Berechtigungsauswahlen und Bereichswerte und archiviert die ursprünglichen Zeilen vor ihrer Umwandlung. Bei unbekannten Datenformaten oder widersprüchlichem Schema bricht er ab. Die Meldung prüfen, statt Daten zu löschen oder Erfolgsmarkierungen manuell zu setzen.

Alte Bereichswerte werden nicht automatisch zu Organen umgedeutet. Erhaltene Werte lassen sich anschließend lesend auflisten:

```sh
php protected/modules/sociolog/upgrade.php --areas
```

Die fachliche Organzuordnung bei Bedarf manuell prüfen. Ein Rohdatenarchiv ersetzt keine vollständige Datenbank- und Dateisicherung. Bereits vor diesem Update verlorene Werte können nicht aus Vermutungen wiederhergestellt werden.

## Nach dem Update

1. Prüfen, dass HumHub die Modulversion **1.0.33** anzeigt und keine Migration mehr aussteht.
2. Leser-, Schreiber- und Löschrechte sowie vorhandene Berechtigungsauswahlen kontrollieren. Eine neue allgemeine Öffnung von Rechten ist nicht erforderlich.
3. Vorhandenen Beschluss, Protokoll und privaten Anhang mit einem berechtigten Konto öffnen; mit einem unberechtigten Konto den Zugriff prüfen.
4. Falls Beschlüsse bei Benutzerlöschung erhalten bleiben sollen, Löschschutz einschalten und ein dauerhaftes aktives Archivkonto wählen. Löschabläufe ausschließlich mit künstlichen Konten auf der Kopie prüfen.
5. HumHubs vorhandenen Cron und Queueworker beibehalten. Der Digest benötigt keinen zusätzlichen Cronjob. Zunächst eine Zusammenfassung an eigene Testempfänger senden; externe SMTP-Konfiguration separat prüfen.

Tabellenpräfixe sind weiterhin nicht unterstützt. Eingeschränkte Leserechte bleiben mit bestimmten nativen Streams, fremden Suchfunktionen und automatischen Space-Kalendererinnerungen begrenzt; vorhandene Schutzprüfungen nicht umgehen.

Bei einem fehlgeschlagenen Update keine Datenbereinigung oder Deinstallation versuchen. Logs und Sicherungen erhalten. Ein Zurückkopieren alter Moduldateien allein macht Datenbankänderungen nicht rückgängig; eine Wiederherstellung muss zum gesicherten Datenbank- und Dateistand passen.

[Änderungen in 1.0.33](AENDERUNGEN-1.0.33.md) · [Detaillierter historischer Updatepfad](docs/ai/SICHERER-ALTUPDATE-2026-10-13.txt)
