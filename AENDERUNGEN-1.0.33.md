# Logbuch 1.0.33 – was sich verbessert

Release 1.0.33 vom 14.10.2026.

Diese Version enthält alle Korrekturen aus der HumHub-Kompatibilitätsprüfung und den anschließenden Browser- und Digest-Tests. Die vorhandenen Funktionen und die Unterstützung von HumHub 1.18 bleiben erhalten.

## Beschlüsse bleiben bei einer Benutzerlöschung erhalten

Bei eingeschaltetem Löschschutz und einem aktiven Archivkonto bleiben Beschlüsse, Protokolle, Verlauf, Reviews sowie direkt zugeordnete Anhänge und deren Dateiversionen erhalten. Die Zuordnung wird auf das Archivkonto übertragen. Das funktioniert auch bei der normalen Selbstlöschung eines Mitglieds.

Das Archivkonto selbst ist gegen Löschung geschützt. Ein Logbuch-Space darf bei der Benutzerlöschung nicht gleichzeitig entfernt werden: Die Space-Löschoption ausschalten, damit HumHub dessen Eigentum an den löschenden Administrator übergibt. Eine verständliche Meldung erklärt eine abgewiesene Löschung.

## Logbucheinträge erscheinen in der E-Mail-Zusammenfassung

Neue Logbucheinträge erscheinen mit Titel und Link im täglichen oder wöchentlichen Digest. Dabei gelten die aktuellen Leserechte und die persönlichen Space-Einstellungen des Empfängers. Wer das Leserecht verliert, erhält den Eintrag bei einem späteren Versand nicht mehr.

Normale HumHub-Aktivitäten bleiben enthalten. Eigene Einträge, gelöschte Inhalte und alte Einträge außerhalb des Digest-Zeitraums werden nicht als neue Meldungen verschickt. Bearbeitungen alter Einträge werden durch diese Digest-Ergänzung nicht zusätzlich aufgenommen; die bestehenden Einzelbenachrichtigungen bleiben erhalten.

## Formulare und Benachrichtigungen funktionieren zuverlässiger

- Formularinitialisierung und Rückkehr nach dem Speichern korrigiert; Protokollfelder werden bei wiederholtem Laden nicht mehrfach verarbeitet.
- Berechtigte Bearbeiter können das bisherige Organ behalten, auch wenn sie dort keine neuen Einträge erstellen dürfen. Organwechsel und Erstellen bleiben gesondert geschützt.
- E-Mail-Benachrichtigungen enthalten wieder Titel, Ereignis und Link. Sonderzeichen und wörtliche HTML-Zeichen erscheinen sicher in E-Mails und Glockenmeldungen.

## Betrieb, Löschen und ältere Updates sind abgesichert

- Der Cron-Fallback startet mit der vollständigen HumHub-Konfiguration.
- Einträge verwenden den nativen Löschablauf. Technische Lesecontainer werden bei endgültiger Löschung nur entfernt, wenn sie nicht anderweitig benötigt werden. Papierkorb und Wiederherstellung behalten sie.
- Gefährdete historische Updates werden vor einem Datenverlust angehalten. Ein geschützter Updater erhält alte Berechtigungsauswahlen und Bereichswerte und legt ein Rohdatenarchiv an.

Es werden keine bereits vorhandenen verwaisten Container automatisch bereinigt. Früher durch alte Updates verlorene Daten lassen sich nur aus einer passenden Sicherung wiederherstellen.

## Geprüft

Unter **HumHub 1.18.6 und offizieller 1.19.0**, mit PHP 8.3.30 und MySQL 8.0.44:

- Je 92 automatische Tests mit 6.070 Assertions bestanden.
- Browserprüfungen für Formulare, Rechte, Löschschutz, Dateierhalt und Zugangssperren bestanden.
- Geplanter täglicher und wöchentlicher Digest über echte Queuejobs und einen lokalen SMTP-Testserver bestanden.

Externe SMTP-Zustellung mit Anmeldung/TLS und optionale Fremdmodule wurden nicht vollständig abgenommen. Tabellenpräfixe bleiben nicht unterstützt. Die bekannten Grenzen eingeschränkter Lesecontainer in fremden Such-/Streamfunktionen bleiben bestehen.

**Vor dem Update:** Datenbank und Dateien sichern. Diese Version enthält zwei zusätzliche Migrationen; die 23 bereits veröffentlichten Migrationen bleiben unverändert. Für ältere, noch nicht vollständig migrierte Installationen gilt der geschützte Updatepfad.

[Update-Anleitung](UPDATE-1.0.33.md) · [Technisches Änderungsprotokoll](CHANGELOG.md) · [Abnahmebericht](docs/ai/ABNAHME-2026-10-14.txt)
