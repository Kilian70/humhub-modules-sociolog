# Sociolog: geprüfter Arbeitsstand vom 11.10.2026

## Benachrichtigungsschnittstelle Develop, 26.10.2026

- Nutzerbefund aus Lauf 40297771677 / Job 121487992746 nachvollzogen. Quellstand Develop `dee382ccb80838804b8888f3c66146c6cc0eade0`; Modul-main weiterhin `7b185fb1a05879832a68a05866351d9c9b55a0f1`, zentrale Regeln `9854ea97c1db1c82e18c7c8dcc097810b6615a4f` abgeglichen.
- Tatsächliche Notification-Schnittstellen v1.18.6/v1.19.0/Develop, CLAUDE.md, Migrationsleitfäden, Develop concept-notifications.md und offizielle example-basic-Metadaten gelesen.
- Öffentliche Benachrichtigungsklassen wählen Stable-/record-API-Anbindung anhand der Kernfähigkeit. Originalvorlagen bleiben Stable vorbehalten; neue native statische Kategorie/Versand unter Develop. Empfänger/Leserechte bleiben geschützt.
- Nur die ausdrücklich vorgegebenen gemeinsamen MAMP-Instanzen verwendet. Vor erstmaliger Sociolog-Aktivierung zentral gesichert; andere installierte Module unverändert. Keine Codeception-Fixture-Resets der Shared-DBs.
- Je MAMP-Instanz 44 gezielte native Prüfungen erfolgreich, Develop-Quellvertrag mit echten Core-Dateien 20 Prüfungen erfolgreich. Kein vollständiger Develop-Laufzeit-/CI-Nachweis behauptet; kein Commit/Push/Release, Version unverändert.
- Reproduktion/Details: `BENACHRICHTIGUNGEN-DEVELOP-2026-10-26.txt`, `tests/compatibility/README.md`.


## Überprüfung: ausser Kraft setzen und ersetzen, 17.10.2026

- Auftrag „gerne ergänzen“: zuständige Personen dürfen im eingeschränkten Review bestätigen, ausser Kraft setzen oder ersetzen. Neue Pflichtbegründung, Nachfolgelink bei Ersatz, Originaldaten erhalten.
- GitHub-Basis unverändert main `fdf37ef07c7de83a98a3ed05325333fc6da698e0`; Regeln `9854ea97c1db1c82e18c7c8dcc097810b6615a4f` abgeglichen. HumHub 1.18.6/1.19.0-Kernquellen und offizielle example-basic-Metadaten erneut berücksichtigt, siehe neuer Bericht.
- Additive Migration `m261017_120000_add_review_replacement_url`; veröffentlichte Migrationen unverändert. Version weiterhin 1.0.34, keine Veröffentlichung dieser Ergänzung.
- Je Zielversion 106 Tests / 6.313 Assertions grün; nach letzter Formularänderung je 14 Tests / 241 Assertions. MAMP-HTTP-Formularabläufe beider Versionen erfolgreich, keine erneute visuelle Browserprüfung. Neuinstallation mit 26 Migrationen und historischem SQL-Modus, Update/Datenerhalt separat geprüft. Historische Null-Datums-Einschränkung im Bericht offengelegt.
- Details, Reproduktion und Grenzen: `UEBERPRUEFUNG-ERGEBNISSE-2026-10-17.txt`.


## Überprüfungen mit neuer Begründung, 17.10.2026

- Auftrag: aktuellen GitHub-Stand berücksichtigen, eingeschränkte Pflege fälliger Einträge ermöglichen und bei jeder Überprüfung eine neue Pflichtbegründung erfassen, auch bei ausgeschalteter Pflegeoption. Ursprüngliche Begründung und frühere Überprüfungen erhalten.
- Modulbasis: `f42cb3955ce67d1da25f4af4d9060f05ae60f4c6` (main, Version 1.0.33), am 17.10.2026 frisch von GitHub geladen. Zentrale Regeln lokal mit GitHub abgeglichen: `9854ea97c1db1c82e18c7c8dcc097810b6615a4f`, README.md und docs/REGELN.md berücksichtigt.
- Zielprüfungen anhand der bestehenden Modulziele: offizielle HumHub-Kerncommits 1.18.6 `870e35a44dd8ec03c659bc698e75a9a8ec244dc3` und 1.19.0 `8f22a72361841404b3b442306126650fc0f8153b`. Die installierte Version der Nutzerinstallation wurde nicht ermittelt.
- Quellen erneut gelesen: jeweilige CLAUDE.md und MIGRATE-DEV.md bzw. docs/develop/module-migrate-1.19.md; ContentActiveRecord und Controller beider Refs. Offizielles Beispiel `humhub/example-basic`: module.json (minVersion 1.18) und controllers/IndexController.php auf master, Blob `c3c075fc15709837ce50ff386d698d345187eaac`. Keine neue HumHub-API-Abhängigkeit eingeführt.
- Separate neue Kernkopien und zwei leere Prüfdatenbanken auf eigenem MySQL-8.0.40-Prozess; PHP 8.3.14. Bestehende MAMP-Installationen und ihre Datenbanken nicht geändert. Vorhandene Vendor-Verzeichnisse nur lesend als Abhängigkeiten genutzt.
- Beide Zielversionen: je 100 Tests / 6.183 Assertions erfolgreich, darunter acht neue Regressionstests. PHP-Syntax auf 8.3.14, 8.4.17 und 8.5.2 geprüft; Modulmetadaten gültig.
- Zusätzlich ausdrücklich beauftragt: MAMP-Prüfung beider offiziellen Versionen. Eigene neue MAMP-Adressen, Web-PHP 8.3.14 (cgi-fcgi), künstliche Konten. Eingeschränktes Speichern, Pflichtbegründung, weitere Überprüfung mit ausgeschalteter Pflegeoption, Erhalt vorheriger Texte/Links und unberechtigter Direktzugriff im echten Browser geprüft. Dabei Feldselektor und wiederverwendete Editor-Entwürfe korrigiert; Quellen AbstractRichTextEditor und humhub.ui.richtext.prosemirror.js beider Refs zusätzlich gelesen.
- Keine Migration und keine Versionsanhebung. Kein Commit, Push, Release oder Deployment. Ergebnis und Prüfgrenzen: `UEBERPRUEFUNG-2026-10-17.txt`.

## Aktueller Entwicklungsauftrag: Löschschutz, 13.10.2026

- Ausdrücklich beauftragt: zuerst die zwei Benutzerlöschschutzfehler korrigieren und unter offiziellen HumHub-Versionen 1.18.6 und 1.19.0 prüfen. Keine bestehenden Anwendungsdaten löschen und nichts auf GitHub hochladen.
- Ausgangsstand: veröffentlichter Modulcommit `429554c468ca8bdad820d57eea0fa57b339cad55`, Version 1.0.32. Änderungen sind unveröffentlicht; keine Versionsanhebung, Commits oder Veröffentlichung.
- Weiterhin Regel-Commit `9854ea97c1db1c82e18c7c8dcc097810b6615a4f`; die älteren Abschnitte unten bleiben als historische Dokumentation erhalten.
- Zielquellen: HumHub 1.18.6 `870e35a44dd8ec03c659bc698e75a9a8ec244dc3` und offizielle 1.19.0 `8f22a72361841404b3b442306126650fc0f8153b`, jeweilige CLAUDE.md/Migrationshinweise sowie User-, Space-, Formular-, Queue- und File-Lifecycle gelesen. PHP für Laufzeittests: 8.3.30.
- Schutz integriert in die nativen Löschformulare, Statusspeicherung und die echte Queue-Ausführung. Betroffene Logbuch-Spaces müssen vor einer Benutzerlöschung übergeben werden. Keine Coreänderung und keine neue Migration.
- Zusätzliche native Dateiquellen: beide `file/Events.php::onUserDelete`, `File` und `FileHistory`; direkt zugeordnete Logbuchdateien und deren Versionen werden über Referenzen erhalten, keine Dateibytes umgeschrieben.
- Offizielles Vergleichsmuster: Core `queue/config.php` registriert Queue-Events statisch. Yii Queue BEFORE_PUSH/BEFORE_EXEC/ExecEvent sowie HumHub UserDeleteForm und AccountDelete in beiden Zielständen geprüft. Früh zu priorisierende native User-Handler bleiben wegen `append=false` in Module.php.
- Eigene Fremd-/Wartungsskripte müssen den geschützten `SociologUserDeletionService::deleteUser()` verwenden; direkte Core-Aufrufe bei Spacebesitz sind nicht allgemein durch einen frühen User-Event abfangbar. Details und Teststand im Löschschutzbericht.

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

## Cron-Fallback und Lesecontainer, 13.10.2026

- Zusätzlich autorisiert: Auditbefunde F8 und F4 korrigieren, ohne Bestandsbereinigung oder GitHub-Veröffentlichung.
- Fallback über native HumHub-Konsole; eigene Befehle `sociolog/status` und rein lesend `sociolog/read-scopes` registriert.
- Beim endgültigen Löschen den eigenen unbenutzten Scope nativ entfernen. Papierkorb/Wiederherstellung und fremde oder widersprüchliche Referenzen erhalten; alte Orphans ausschließlich auflisten.
- Offizielle HumHub 1.18.6 und 1.19.0: je 67 Tests / 5.732 Assertions bestanden, inklusive aller vorherigen Löschschutzfälle; CLI-PHP 8.3.30.
- Echte Fallback-Starts mit .env und lokaler Konfiguration, Statusänderungen und Fehlerstatus geprüft. Reine Orphan-Auflistung lässt sämtliche 52/53 Tabellen unverändert.
- 120 PHP-Dateien und Modulmetadaten unter PHP 8.3.30/8.4.17/8.5.2 geprüft. 23 Migrationen und Modulversion unverändert; keine bestehende MAMP-Installation aktualisiert.
- Details und manuelle Kontrolle: `docs/ai/CRON-LESECONTAINER-2026-10-13.txt`. Zentrale Regeln bleiben auf Commit 9854ea97c1db1c82e18c7c8dcc097810b6615a4f festgelegt.

## Formular-JavaScript und Benachrichtigungen, 13.10.2026

- Zusätzlich autorisiert: Befunde F3/F5 korrigieren und beide offiziellen Zielversionen testen; weiterhin keine bestehenden Daten löschen oder auf GitHub hochladen.
- Native UI-Quellen ui.modal/ui.additions/ui.widget/core/client und Yii-ActiveForm beider Refs gelesen. Offizielles post-Modul und BaseNotification/Image-Widgets als Vergleich; zentrale Regeln bleiben auf Commit 9854ea97c1db1c82e18c7c8dcc097810b6615a4f.
- Erfundenes modal.initForm/modal.form und DOM-Abschlussereignis ersetzt; klassisches ActiveForm bleibt erhalten. Echter jQuery-Modalabschluss nur bei erfolgreicher Logbuchspeicherung, serverseitige cards/table-Rückkehr-URL, fremde Formulare ausgenommen.
- Zusätzlich doppelte Protokoll-Listener bei wiederholtem Formularrendering behoben; zeilenbezogene Änderungen auf das betroffene Formular begrenzt.
- Webtitel/Modulname genau einmal HTML-kodiert, kein HTML-Dekodieren bestehender Texte. Mailbetreff und Empfänger-/Berechtigungslogik unverändert.
- HumHub 1.18.6 und offizielle 1.19.0: je 71 Tests / 5.833 Assertions mit CLI-PHP 8.3.30, neue künstliche Prüfdatenbanken. Zusätzlich elf isolierte Chrome-Browserfälle je Version mit realen Kernskripten; Transport/Bootstrap-Hide als Testdoubles, kein vollständiger angemeldeter HTTP-Test oder echter Mailversand.
- 122 PHP-Dateien unter 8.3.30/8.4.17/8.5.2 sowie sechs JS-Dateien und Browserprüfer syntaktisch kontrolliert. 23 Migrationen und Version 1.0.32 unverändert. Keine MAMP-Aktualisierung oder Veröffentlichung.
- Prüfbericht, Grenzen und manuelle Anleitung: `docs/ai/FORMULAR-BENACHRICHTIGUNGEN-2026-10-13.txt`.

## Native Eintragslöschung (D1), 13.10.2026

- Zusätzlich autorisiert: beide beanstandeten veralteten Content-Löschaufrufe korrigieren, ohne bestehende Daten zu löschen oder auf GitHub hochzuladen.
- Native ContentActiveRecord-Soft-/Harddelete-Ereignisse und Content-Lifecycle beider offizieller Core-Refs erneut geprüft. Zentraler Regelpin bleibt 9854ea97c1db1c82e18c7c8dcc097810b6615a4f.
- Controller verwendet Entry::softDelete(). Stream-Nachlaufcallback bleibt erhalten, kontrolliert die native Bereinigung rein lesend und startet keine zweite oder rekursive Löschung. Historischer Legacy-Fallback unverändert.
- Fünf neue Integrationsfälle für tatsächliche Controlleraktion, Vorab-Abbruch, Rechte, Wiederherstellung und sicheren Nachlaufcallback. HumHub 1.18.6 und offizielle 1.19.0: je 76 Tests / 5.885 Assertions bestanden, CLI-PHP 8.3.30, zwei neu angelegte künstliche Prüfdatenbanken.
- 123 PHP-Dateien und Modulprüfer unter PHP 8.3.30/8.4.17/8.5.2 geprüft. 23 Migrationen und Version 1.0.32 unverändert. Keine neue durchgehende HTTP-/CSRF-Prüfung behauptet; direkte Controllerintegration getestet.
- Belege und manuelle Anleitung: `docs/ai/CONTENT-LOESCHABLAUF-2026-10-13.txt`. Keine Aktualisierung bestehender MAMP-Installationen oder GitHub-Veröffentlichung; F6/F7 weiterhin offen.

## Sicherer historischer Updatepfad (F6/F7), 13.10.2026

- Zusätzlich autorisiert: verlustfreien Updatepfad für noch ausstehende F6/F7-Schritte erstellen und beide offiziellen Versionen testen. Keine bestehenden Anwendungsdaten löschen, veröffentlichte Migrationen nicht ändern, nichts auf GitHub hochladen.
- Native MigrationService/MigrateController/Yii-Migrationsfactory und Historie, BaseSettingsManager, ModuleManager/ModuleAutoLoader sowie EnvHelper beider Core-Refs geprüft; zentraler Regelpin bleibt 9854ea97c1db1c82e18c7c8dcc097810b6615a4f.
- Neuer CLI-Einstieg upgrade.php: Vorprüfung, ausdrückliches --apply und reine --areas-Auflistung. Native Konfiguration; prozessbezogener Core-only-ModuleManager verhindert externe Modulinitialisierung gegen das alte Schema.
- Aktivierung/normaler Modulupdateweg bei alten ausstehenden Schritten gesperrt. Neue vor F6 einsortierte Guard-Migration stoppt native Standardläufe bei gefährdeten Altwerten; additive Archivmigration vor kontrollierten historischen Schritten.
- F6-Auswahlen in-place von geprüften JSON-/PHP-Listen nach GUID-/Gruppen-JSON umwandeln; Rohzeilen erhalten. F7-Additionen nativ ausführen, Bereichsspalte erhalten, Organzuordnung bewusst manuell. Originale Historienkennungen erst nach erfolgreichen sicheren Ersatzoperationen markieren.
- Notwendige sichere Fortsetzung für NULL-Organname im historischen is_organ_space-Schritt ergänzt; veröffentlichte Datei unverändert. Unbekannte Auswahlen und teilweise Organ-Schemata vor Änderungen stoppen; Guard-Freigabe wird auch nach Fehlern zurückgesetzt.
- Je Version 82 Tests / 5.906 Assertions, CLI-PHP 8.3.30. Tatsächliche Updateprozesse auf künstlichen Altständen mit aktiviertem Modul, Berechtigungen, Bereichswerten, Altentscheid und Protokoll erfolgreich; Vorprüfung/Auflistung/Wiederholung ohne Fachdatenänderung. Core-Fehlerlogs bei absichtlichen Abbrüchen separat behandelt.
- Jetzt 25 Migrationen: die ursprünglichen veröffentlichten 23 bytegleich, zwei neue Dateien. Version 1.0.32 bleibt bis zur Veröffentlichung unverändert. 133 PHP-Dateien syntaktisch unter PHP 8.3.30/8.4.17/8.5.2 geprüft; keine aktualisierten MySQL-8-/Browser-/Mail-Laufzeittests behauptet.
- Verlorene Altdaten werden nicht erfunden; passende frühere Sicherung erforderlich. Anleitung und Grenzen: `docs/ai/SICHERER-ALTUPDATE-2026-10-13.txt`. Keine bestehende MAMP-Installation aktualisiert und keine GitHub-Aktion.

## Mailinhalt und Manager-Bearbeitung (F9/F10), 13.10.2026

- Ausdrücklich beauftragt: F9/F10 beheben; beide offiziellen HumHub-Versionen prüfen; keine bestehenden Daten löschen und nichts auf GitHub hochladen.
- Native Mailrenderer über gemeinsame html()/text()/getViewParams()-Implementierung angebunden. Erstell-/Änderungsmails enthalten Titel, Ereignis/Akteur und Link; Glocke und Empfängerrechte erhalten.
- Beim Bearbeiten vorhandenes Organ für tatsächlich berechtigte Bearbeiter erhalten; Create- und Organwechselrechte nicht erweitern. Keine Migration/Coreänderung und Version weiterhin 1.0.32.
- Je 85 Tests / 5.989 Assertions unter HumHub 1.18.6 und offizieller 1.19.0, PHP 8.3.30/MySQL 8.0.44; je sechs echte Browserfälle sowie native Queue/MIME-Prüfung erfolgreich. PHP 8.4/8.5 nur Syntax/Metadaten.
- Regelpin bleibt 9854ea97c1db1c82e18c7c8dcc097810b6615a4f. Details/Anleitung: docs/ai/MAIL-ORGAN-2026-10-13.txt. Nur lokale Arbeitsdateien/Testkopien; kein Commit/Push/Release/Deployment.

## Löschschutz B1 beim ersten nativen Aufruf, 13.10.2026

- Autorisiert: B1 beheben und native Browserlöschung auf gesicherten Testkopien für 1.18.6 und offizielle 1.19.0 wiederholen. Keine bestehenden Daten löschen oder GitHub-Aktion.
- Drei Schutzprüfungen laden das aktivierte Modul über Application::getModule(); keine Verwechslung mit ModuleManager::getModule(), das auch deaktivierte Module erzeugen kann. APIs an beiden gepinnten Cores geprüft.
- Archivfehler ohne eigene Spaces am sichtbaren deleteContributions-Feld anzeigen; zwei Regressionen für nicht geladenes Modul, native Formulare, Statuswechsel und serialisierte Jobs ergänzen.
- Details, Testergebnisse, Grenzen und manuelle Anleitung: docs/ai/B1-LOESCHSCHUTZ-2026-10-13.txt. Regelpin unverändert 9854ea97c1db1c82e18c7c8dcc097810b6615a4f. Keine neue Version, Migration oder Coreänderung.
- Ergebnisse: je 87 Tests / 6025 Assertions; nach finaler Fehlerortänderung je 14 Löschschutztests / 274 Assertions. Alle nativen Browser-/Workerfälle einschließlich all/space/selected, Archivadmin/-selbstlöschung, normaler Selbstlöschung, Leserzugriff und Gast-/Mitglied-/CSRF-Abweisung bestanden. Originaltabellen/Core/veröffentlichte Migrationen unverändert. B2 bleibt offen.

## Digest B2, 13.–14.10.2026

- Autorisiert: Logbucheinträge in Digest integrieren, Leserechte erhalten, HumHub 1.18.6/offizielle 1.19.0 prüfen; keine Bestandslöschung und keine GitHub-Aktion.
- Native MailSummary-Factory über DI/Request-/Queue-Einstieg erweitert. Zeitnahe neue Einträge mit expliziter Empfänger-ACL, Original-Space-Präferenzen, HTML/Klartext und kombinierter Anzahlgrenze; normale Aktivitäten bleiben erhalten. Keine historischen Activity-Kopien oder Migration.
- Je 92 Tests/6069 Assertions; endgültige Digestklasse je 5 Tests/44 Assertions. 48 lokale native Daily/Weekly-MIME-Prüfungen auf getrennten MAMP-Kopien bestanden, einschließlich ausgewähltem Außenkonto, Rechteentzug und Space-Ausschluss.
- B2 lokal behoben. Grenzen und Anleitung: docs/ai/B2-DIGEST-2026-10-14.txt. Regelpin/Core-Refs unverändert; Version 1.0.32 unveröffentlicht, kein Commit/Push/Release/Deployment.

## Abschließende Abnahme, 14.10.2026

- Ausdrücklich beauftragt: Abnahme auf gesicherten Kopien beider offiziellen Versionen einschließlich geplantem Digest und SMTP; keine bestehenden Daten löschen und nichts auf GitHub hochladen. Benutzer wählte lokalen SMTP-Testserver ohne externe Zustellung.
- Endgültiger Stand je 92 Tests/6070 Assertions bestanden. Native Browserlöschungen mit zehn neuen künstlichen Konten je Core, normaler Formular-/Managerablauf, Rechte-/CSRF-/Gast-/Passwort-/Wartungssperren bestanden.
- Native tägliche/wöchentliche Zeitplanjobs über echte MySQL-Queue/Core-Processor mit Empfänger-Testbegrenzung verarbeitet. Vier echte SMTP-Nachrichten insgesamt korrekt angenommen; nicht berechtigte Empfänger ausgeschlossen und fremder Testempfänger beim SMTP-RCPT mit 550 zurückgewiesen.
- Originaltabellen/Einstellungen, ursprüngliche Beschlüsse, Core-Dateien und veröffentlichte Migrationen unverändert. Kein Modulcode geändert, keine Migration oder GitHub-Aktion. Testserver beendet.
- Umfang, zeitlich simulierte Fälligkeit und verbleibende Grenzen: docs/ai/ABNAHME-2026-10-14.txt. Externe Provider/TLS/Auth/echte OS-Crontermine nicht geprüft. Keine allgemeine Produktionsfreigabe.

## Version 1.0.33 vorbereitet, 14.10.2026

- Ausdrücklich beauftragt: neue Version mit sämtlichen Korrekturen und verständlichem Änderungsprotokoll vorbereiten; nichts auf GitHub hochladen.
- Module.php/module.json/composer.json/README auf 1.0.33, minimum weiterhin 1.18.0. Neue Anwenderübersicht und Update-Anleitung; frühere technische Nachweise erhalten. Keine Funktionsänderung seit Abnahme.
- Entpacktes 1.0.33-Paket je 92 Tests/6070 Assertions auf eigenen Fixture-Testdatenbanken; je zehn isolierte JS-Browserfälle, 137 PHP-Syntaxprüfungen und Paket-/Metadatenprüfung bestanden. Kein produktives Update, keine Anwendungsdatenmigration und keine GitHub-Aktion.
- ZIP mit Prüfsumme lokal erzeugt, 23 veröffentlichte Migrationen unverändert. Details: docs/ai/VERSION-1.0.33-2026-10-14.txt.

## Veröffentlichungsfreigabe, 17.10.2026

- Nutzer hat ausdrücklich die Übernahme von PR #22 nach main und eine neue Veröffentlichung beauftragt. PR #22 wurde mit Mergecommit b07da07 übernommen.
- Release 1.0.34 erhöht ausschließlich die Versionsangaben in Module.php, module.json und composer.json und aktualisiert die Release-/Update-Dokumentation. Die getestete Funktionslogik bleibt unverändert; keine neue Migration.
- GitHub-Stable-Prüfungen des Funktionsstands erfolgreich unter PHP 8.2 bis 8.5. Develop scheiterte vor Modultests an HTTP 404 des Testservers. Keine erfolgreiche Develop-Abnahme behaupten.

## Veröffentlichungsauftrag 1.0.35, 17.10.2026

- Nutzerauftrag „veröffentlichen“ nach Übergabe der geprüften Ergänzung. Commit, Push, PR/Merge und reguläres Release für diesen Stand sind autorisiert.
- GitHub-main weiterhin `fdf37ef07c7de83a98a3ed05325333fc6da698e0`, letztes Release v1.0.34. Keine fremden Änderungen zu übernehmen.
- Versionsangaben in Module.php, module.json und composer.json auf 1.0.35 angehoben; Änderungen/Update-Anleitung ergänzt. Funktionsstand bleibt gegenüber dem geprüften Stand unverändert. Historische Angaben zu unveröffentlichten Ständen bleiben als solche erhalten.
- Release enthält die neue additive Migration; Update-Anleitung beschreibt Migration, Datenerhalt und historische NO_ZERO_DATE-Grenze. Kein Deployment auf die Vereinsinstallation.
