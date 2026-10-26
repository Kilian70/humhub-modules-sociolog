# Benachrichtigungsschnittstellen prüfen

## Gemeinsame MAMP-Instanzen (Stable)

Keine neue Installation oder Fixture-Datenbank anlegen. Zuerst die zentrale ANLEITUNG.txt lesen und vor einer bewussten Modulübernahme die gemeinsamen Instanzen sichern. Bestehende Module nicht ungeprüft überschreiben. Sociolog muss bereits aktiviert und migriert sein.

```sh
php tests/compatibility/notification-stable-mamp.php /Applications/MAMP/htdocs/humhub-1.18
php tests/compatibility/notification-stable-mamp.php /Applications/MAMP/htdocs/humhub-1.19
```

Die Prüfung bootet die vorhandene Instanz, prüft native Kategorien/Registrierung und rendert Web-/Mailinhalte mit künstlichen, ungespeicherten Einträgen. Kein Zurücksetzen der gemeinsamen Daten durch Codeception-Fixtures. Sie versendet keine Mail und ersetzt keine vollständige Versandabnahme.

## Develop-Quellvertrag ohne zusätzliche Testinstallation

```sh
php tests/compatibility/notification-record-api.php /Applications/MAMP/htdocs/humhub-1.19 /pfad/zum/develop-quellpaket
```

Das Quellpaket enthält die unveränderten Dateien des festgehaltenen Core-Commits:

- `NotificationPriority.php` aus `protected/humhub/modules/notification/components/`
- `protected/humhub/modules/notification/components/BaseNotification.php`
- `protected/humhub/modules/notification/components/NotificationCategory.php`

Die Prüfung verwendet die vorhandenen MAMP-Vendor-Abhängigkeiten, lädt die echten Develop-Signaturen, den finalen Kategorienwert und die neue statische send-Methode. Nur die Dispatch-Grenze wird aufgezeichnet; keine Develop-Datenbank oder zusätzliche HumHub-Installation. Damit sind Klassenladefehler und der falsche Versandaufruf prüfbar, keine vollständige Develop-Kompatibilität oder Mailzustellung.

Die bestehenden Codeception-Tests bleiben für die CI erhalten. Alte Template-Tests gelten nur für Stable; `NotificationCompatibilityTest` prüft Kategorie/Registrierung beider APIs und auf dem neuen Kern die native Erzeugung eines datensatzgebundenen Objekts. Die Codeception-Fixtures dürfen nicht direkt gegen die gemeinsam genutzten MAMP-Datenbanken laufen.
