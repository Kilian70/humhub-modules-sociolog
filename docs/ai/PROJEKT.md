# Sociolog: geprüfter Arbeitsstand vom 11.10.2026

- Auftrag: bestätigte Fehler aus dem Prüfbericht beheben; keine Commits, Pushes oder Veröffentlichungen. Später ausdrücklich autorisiert: Einrichtung und Aktualisierung zweier neuer MAMP-Testinstanzen; bestehende Anwendung gebaeudemanagement unverändert.
- Vereinsregel: alle angemeldeten Vereinsmitglieder dürfen weiterhin alle Logbuch-Einträge lesen. Die zusätzlich autorisierte konfigurierbare Leseberechtigung ist implementiert; alle angemeldeten Mitglieder bleiben die Voreinstellung.
- Modul-ID: `sociolog`; unveränderte Versionsangabe: `1.0.30` (lokaler Arbeitsstand, kein Release).
- Ausgangscode: `Kilian70/humhub-modules-sociolog`, Commit `8a41b49b1cc54f743cb05a55dd8ace3db5984d7c`.
- Vorgaben: `Kilian70/humhub-ai-dev`, Commit `9854ea97c1db1c82e18c7c8dcc097810b6615a4f`; gelesen wurden insbesondere `docs/REGELN.md` und `docs/QUELLEN.md`.

## Tatsächlich verwendete Quellen

Quellenprüfung am 11.10.2026, direkt aus den installierten Kernquellen:

| Ziel | Kern-Commit | Gelesene Vorgaben |
| --- | --- | --- |
| HumHub 1.18.6 | `870e35a44dd8ec03c659bc698e75a9a8ec244dc3` | `CLAUDE.md`, Content-/Permission-/Migrationsklassen |
| HumHub 1.19.0-beta.3 | `2dadb68b8dfbae25243584d8ff877a3ca5311b30` | `docs/develop/module-migrate-1.19.md`, Content-/Migrationsklassen |

Relevante APIs: `humhub/components/Module.php`, `humhub/services/MigrationService.php`, `humhub/commands/MigrateController.php`, `humhub/modules/content/components/ContentActiveRecord.php`, `ActiveQueryContent.php`, `humhub/modules/content/models/Content.php`, Space-Mitgliedschaften und Benutzer-/Container-PermissionManager. Offizielles Modulbeispiel: HumHub-Kernmodul `post`, insbesondere `models/Post.php` und `assets/PostAsset.php` (Modulalias als Ressourcenpfad).

## Prüfung und Grenzen

- Laufzeittests verwenden getrennte Kernkopien und neu angelegte Testdatenbanken, keine Datenbank der vorhandenen MAMP-Installationen.
- CLI-PHP für Laufzeittests: MAMP PHP 8.3.30. Syntaxprüfung zusätzlich mit PHP 8.4.17 und 8.5.2.
- Web-PHP am 11.10.2026 direkt in den beiden eingerichteten MAMP-Testinstanzen bestätigt: 8.3.30, cgi-fcgi. Native Testinstanzen verwenden MySQL 8.0.44.
- Alle 21 veröffentlichten Migrationen sind bytegleich zum Ausgangscode. Die Fehlerkorrekturen enthielten keine neue Datenmigration. Die später beauftragte Einwandsfrist ergänzt eine neue additive Migration für `published_timestamp`.
- Tabellenpräfixe bleiben mit der historischen Migrationsfolge nicht unterstützt. Modulaktivierung und Modul-Migrationsservice brechen vorher mit einer verständlichen Meldung ab. Direkte manuelle Ausführung einzelner alter Migrationen bzw. der globale Kern-Migrationsbefehl umgehen diesen Modulschutz und werden für Präfixdatenbanken nicht unterstützt.
- Vergangene falsche Datumswerte, bereits verlorene Protokollreferenzen und durch alte Migrationen gelöschte Einstellungen werden nicht aus Vermutungen rekonstruiert; dafür ist ein geeigneter älterer Datenbankstand erforderlich.
- Details, Testergebnisse und manuelle Testanleitung stehen in `docs/ai/REVIEW.txt`.

## Einwandsfrist, 11.10.2026

- Zusätzlich autorisiert: optionale Anzeige „Schwerwiegender Einwand möglich bis“.
- 10 volle Tage (240 Stunden) ab tatsächlicher Veröffentlichung des Logbuch-Entscheids; keine Verlängerung durch den Zusatztag beim Inkrafttreten.
- Einstellung `showObjectionDeadline`, standardmäßig aus, in beiden lokalen Testinstanzen aktiviert. Keine Änderung von Leser-/Schreibrechten.
- Neue Spalte `published_timestamp` als nullable BIGINT, serverseitig gesetzt, nicht per Formulareingabe übernehmbar. Altdaten werden nicht mit erfundenen Uhrzeiten aufgefüllt.
- Veröffentlichte Kern-API `humhub/components/Migration.php` und vorhandene Yii-ColumnSchemaBuilder-Methoden in beiden Zielversionen geprüft.
- Neue Hilfsansicht verwendet `humhub/helpers/Html.php`, in beiden Zielversionen vorhanden.
- Neuinstallation mit 22 Modulmigrationen und Update von 21 auf 22 geprüft.
- Vollständige Tests: 35 Tests / 5.278 Assertions je Version, PHP 8.3.30, isoliertes MySQL 5.7.
- Bericht und Anleitung: `docs/ai/EINWANDSFRIST.txt`.

## Konfigurierbare Leserechte, 11.10.2026

- Drei globale Modi: alle angemeldeten Mitglieder (Voreinstellung), Mitglieder des ursprünglichen Spaces, ausgewählte Benutzer/Gruppen.
- Autor- und Inhaltsadministratoren-Zugriff bleibt wie bei HumHubs privaten Inhalten bestehen. Andere Schreibrechte ersetzen kein Leserecht.
- Neue additive Migration `m261011_170000_add_read_scope`; die vorherigen 22 Migrationen sind unverändert. Private native `ContentContainerActiveRecord`-Instanzen schützen auch Core-Inhaltslinks und Kommentare.
- Kern-APIs von ContentContainerActiveRecord, ContentContainer, ContentActiveRecord, Content::canView, StreamQuery, Kommentarcontrollern, SettingsManager und UserHelper auf beiden oben genannten Refs gelesen. 1.19 Impersonation wird nur bei vorhandener API verwendet.
- Eingeschränkte Leserechte gelten im Logbuch einschließlich eigener Suche und Widget. Space-Stream/allgemeine HumHub-Suche unterstützen diese Container nicht vollständig. Automatische Space-Kalendererinnerungen dürfen nicht gleichzeitig aktiv sein; vorhandene Erinnerungen blockieren den Wechsel.
- 45 Tests / 5.370 Assertions je Zielversion bestanden; 10 neue Zugriffsregressionen. Neuinstallation mit 23 Modulmigrationen je Version erfolgreich. Alle 116 PHP-Dateien syntaktisch unter PHP 8.3.30, 8.4.17 und 8.5.2 geprüft.
- Bericht, Grenzen und MAMP-Testanleitung: `docs/ai/LESERECHTE.txt`. Modulversion bleibt bis zur Veröffentlichungsfreigabe bei 1.0.30.

## Übersetzungen und aktueller Abschlussstand, 11.10.2026

- Native ModuleMessageSource-Klasse und I18N::getMessageSource in beiden Zielversionen gelesen. Exakte Kategorieinstanzen erhalten die tatsächliche deutsche Ausgangssprache und native Konfigurationsüberschreibungen.
- Standardbeschriftungen sprachunabhängig speichern; Deutsch und Englisch einschließlich regionaler Varianten sowie eigene Beschriftungen geprüft.
- Abschließend je Zielversion 47 Tests / 5.402 Assertions bestanden; zwei neue Sprachregressionen. Zuletzt geänderte Dateien syntaktisch mit PHP 8.3.30, 8.4.17, 8.5.2 geprüft.
- Aktueller Gesamtstand und verbleibende Grenzen: `docs/ai/AKTUELLER_TESTSTAND.txt`. Frühere Zahlen in den einzelnen Abschnitten dokumentieren vorherige Prüfschritte.

## Präfixprüfung, 11.10.2026

- Zwei echte leere Kerninstallationen mit hh_ scheitern bereits in einer offiziellen Space-Kernmigration. Core-Modelle User, Space, Setting und Content verwenden feste Tabellennamen in beiden Zielständen.
- Keine Unterstützung einer vollständig präfixierten HumHub-Installation behaupten. Experimenteller Moduladapter vollständig zurückgenommen; geprüfter Code unverändert. Korrigierte Einordnung: `docs/ai/PRAEFIXPRUEFUNG.txt`.
