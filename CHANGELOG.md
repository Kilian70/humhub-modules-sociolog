
# 📄 Changelog – Sociolog

Alle relevanten Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

## [1.0.34] – 2026-10-17

- MAMP-Browserprüfung: Rich-Text-Feld korrekt an die Formularvalidierung anbinden; jede neue Überprüfung mit eigenem Editor ohne Übernahme vorheriger Editor-Entwürfe öffnen.
- „Überprüfung dokumentieren“ öffnet unabhängig von der Pflegeoption immer ein eigenes Formular mit neuer Pflichtbegründung und speichert diese im Verlauf, ohne die ursprüngliche Begründung zu überschreiben.
- Eingeschränkte Überprüfung auch ohne Entscheidungsworkflow zugänglich machen. Zuständige Personen können ein zukünftiges Datum, eine neue Pflichtbegründung im Verlauf und einen zusätzlichen Protokolllink erfassen; ursprüngliche Eintragsangaben und vorhandene Protokolle bleiben erhalten. Das Ergebnis kann im eingeschränkten Formular auch per manipuliertem Request nicht geändert werden.

## [1.0.33] – 2026-10-14

Enthält alle geprüften Korrekturen F1–F10, D1, B1 und B2 seit 1.0.32.

- **Daten erhalten:** Archivkonto frühzeitig gegen Löschung schützen; eigene Logbuch-Spaces erhalten. Bei aktivierter Archivierung Beschlüsse, Protokolle, Verlauf, Reviews, Anhänge und Dateiversionen auf das Archivkonto übertragen, auch bei nativer Selbstlöschung.
- **Digest ergänzen:** Neue lesbare Logbucheinträge mit Titel und Link in die E-Mail-Zusammenfassung aufnehmen. Aktuelle Leserechte und persönliche Space-Einstellungen gelten auch dort.
- **Formulare korrigieren:** Initialisierung und Rückkehr nach dem Speichern verbessern, doppelte Protokoll-Listener verhindern und das bestehende Organ bei berechtigter Bearbeitung erhalten.
- **Benachrichtigungen korrigieren:** Titel, Ereignis und Link in HTML-/Textmails anzeigen; Sonderzeichen und wörtliche HTML-Zeichen sicher ausgeben.
- **Löschabläufe korrigieren:** Native Eintragslöschung verwenden; Lesecontainer bei endgültiger Löschung kontrolliert entfernen, bei Papierkorb/Wiederherstellung erhalten. Keine automatische Bestandsbereinigung.
- **Betrieb und Altupdates absichern:** Cron-Fallback vollständig über HumHub starten. Vor gefährdeten historischen Migrationen stoppen und einen gesicherten Updatepfad mit Rohdatenarchiv anbieten.
- **Kompatibilität geprüft:** HumHub 1.18.6 und offizielle 1.19.0, PHP 8.3.30, MySQL 8.0.44; je 92 Tests/6.070 Assertions sowie Browser- und lokale SMTP-/Digest-Abnahme bestanden.

**Update-Hinweis:** Zwei zusätzliche Migrationen, 23 bereits veröffentlichte Migrationen unverändert. Datenbank/Dateien sichern; für noch ausstehende historische Schritte den geschützten Updater verwenden. Bereits früher verlorene Werte benötigen eine frühere Sicherung. Tabellenpräfixe bleiben nicht unterstützt.

Verständliche Übersicht: [Änderungen](AENDERUNGEN-1.0.33.md). Vorgehen: [Update-Anleitung](UPDATE-1.0.33.md).

Die folgenden technischen Notizen dokumentieren die einzelnen Korrekturschritte vor der Versionsanhebung. Ihre damaligen Angaben „Version unverändert“ beziehen sich auf den geprüften Arbeitsstand 1.0.32. Historische Prüfberichte bleiben erhalten.

### Technische Details – Logbucheinträge im Digest (B2)

- Neue lesbare Logbucheinträge mit Titel und Link in die native tägliche/wöchentliche Zusammenfassung aufnehmen; vorhandene Aktivitäten bleiben enthalten.
- Leserrechte für den tatsächlichen Empfänger bei Auswahl und Ausgabe prüfen, einschließlich ausgewählter Benutzer/Gruppen und Rechteentzug.
- Persönliche Space-Auswahl gegen den ursprünglichen Space auswerten, HTML sicher escapen und native Zeit-/Anzahlgrenzen erhalten.
- Anbindung auch beim ersten Request und im Queueworker registrieren; keine historischen Aktivitäten nacherzeugen oder bestehenden Daten verändern.
- Geprüft unter HumHub 1.18.6 und offizieller 1.19.0; Details: `docs/ai/B2-DIGEST-2026-10-14.txt`. Keine Veröffentlichung oder Versionsanhebung.

### Technische Details – Löschschutz bei noch nicht geladenem Modul (B1)

- Aktiviertes Modul in den Löschschutzprüfungen laden, statt native Formulare und frische Worker ohne Schutz passieren zu lassen.
- Ablehnung eines Archivkontos ohne eigene Spaces an einem sichtbaren Formularfeld anzeigen.
- Regressionen für nicht geladenes Modul, native Formularvalidierung, Statuswechsel, serialisierte Jobs und Archivübertragung bei Selbstlöschung ergänzen.
- Keine Bestandsbereinigung, Coreänderung oder Migration; Version unverändert. Nachweise und Anleitung: `docs/ai/B1-LOESCHSCHUTZ-2026-10-13.txt`.

### Technische Details – Mailinhalt und Manager-Bearbeitung (F9/F10)

- Native HTML- und Textmails enthalten wieder Titel, passenden Erstell-/Änderungshinweis und Link; Sonderzeichen und wörtliche HTML-Zeichen bleiben sicher erhalten.
- Berechtigte Bearbeiter können das bisherige Organ behalten, auch wenn ihnen dort das Erstellrecht fehlt. Erstellen und Organwechsel bleiben nach den bisherigen Regeln beschränkt.
- Keine Datenmigration und keine Änderung an bestehenden Berechtigungsauswahlen. Prüfung: `docs/ai/MAIL-ORGAN-2026-10-13.txt`.

### Technische Details – Geschützter Altupdatepfad (F6/F7)

- Neue vorgeschaltete Schutzmigration und Aktivierungs-/Updateprüfung stoppen historische Datenverluste vor den unveränderten veröffentlichten Schritten.
- Expliziter CLI-Updater mit Vorprüfung, sicherer Umwandlung vorhandener Berechtigungsauswahlen, Rohdatenarchiv und erhaltener Bereichsspalte; Organzuordnung bewusst manuell.
- Notwendige Fortsetzung für unzugeordnete Organ-Spaces gegen NULL-Namen und wiederholtes Hinzufügen des Flags absichern, ohne veröffentlichte Migrationen zu ändern.
- Zwei neue Migrationen; die ursprünglichen 23 Dateien bleiben bytegleich. Bereits früher verlorene Werte benötigen eine passende Sicherung.
- Tests und Anleitung: `docs/ai/SICHERER-ALTUPDATE-2026-10-13.txt`; keine Versionsanhebung oder Veröffentlichung.

### Technische Details – Native Eintragslöschung (D1)

- Controller verwendet die native Entry-Softdelete-API einschließlich Vorabprüfung und Lifecycle-Ereignissen; Papierkorb und Wiederherstellung bleiben erhalten.
- Veraltete zusätzliche Content-Löschung im Stream-Nachlaufcallback entfernen; native ContentActiveRecord-Bereinigung kontrollieren, keine rekursive zweite Löschung oder Bestandsbereinigung.
- Fünf Regressionen für Controllerrechte, abgebrochene Löschung, Wiederherstellung und ungefährlichen Löschcallback ergänzt. Prüfbericht: `docs/ai/CONTENT-LOESCHABLAUF-2026-10-13.txt`.

### Technische Details – Formular und Benachrichtigungen

- Formularinitialisierung mit nicht vorhandenen Modal-APIs durch native HumHub-Initialisierung ersetzen; Rückkehr nach erfolgreichem Logbuch-Modalabschluss auf die serverseitig erzeugte Karten-/Tabellenroute begrenzen.
- Formularvalidierung erhalten; Protokoll-Listener bei PJAX nicht mehrfach installieren und auf das betroffene Formular begrenzen.
- Titel und Modulname in beiden Webbenachrichtigungen genau einmal sicher HTML-kodieren; Mailbetreffe, Empfänger und Leserechte unverändert.
- Je 71 Tests / 5.833 Assertions unter HumHub 1.18.6 und offizieller 1.19.0 mit PHP 8.3.30; zusätzlich je elf isolierte Browserfälle mit versionsbezogenen Kernskripten bestanden. Grenzen und Anleitung: `docs/ai/FORMULAR-BENACHRICHTIGUNGEN-2026-10-13.txt`.

### Technische Details – Cron-Fallback und Lesecontainer

- Cron-Fallback über den nativen HumHub-Konsolenstart mit vollständiger Konfiguration, `.env` und Modulregistrierung ausführen.
- Beim endgültigen Löschen eines Eintrags den zugehörigen unbenutzten Lesecontainer nativ entfernen; bei Papierkorb und Wiederherstellung erhalten.
- Fremdreferenzen oder widersprüchliche Containerzuordnungen erhalten und protokollieren. Historische verwaiste Container ausschließlich mit `sociolog/read-scopes` auflisten; keine automatische Bestandsbereinigung.
- Keine neue Migration oder Änderung der Modulversion.

### Technische Details – Löschschutz

- Archivkonto vor Bestätigung, Statusänderung und Ausführung nativer Benutzerlöschaufträge schützen, auch ohne vorhandene Einträge.
- Bei aktiviertem Erhalt die Löschung betroffener Logbuch-Spaces abweisen; die vorhandene HumHub-Übergabe an den löschenden Administrator bleibt möglich.
- Einträge, Protokolle, Verlauf, Reviews sowie direkt zugeordnete Anhänge und Datei-Versionen vor dem nativen Löschauftrag auf das aktive Archivkonto übertragen.
- Bereits vorhandene Löschaufträge beim Ausführen erneut prüfen; veröffentlichte Migrationen und Modulversion unverändert.
- Zwölf Integrationstests für Formulare, serialisierte Warteschlangenaufträge, Daten-/Dateierhalt und den ausgeschalteten Erhalt ergänzt. Grenzen und Ergebnisse: `docs/ai/LOESCHSCHUTZ-2026-10-13.txt`.

## [1.0.32] – 2026-10-12

- Einwandsfrist samt Altdaten-Hinweis nach Ablauf in Karten und Detailansicht automatisch ausblenden.
- Bei erfasster Uhrzeit endet die Anzeige exakt nach 240 Stunden. Ohne historische Uhrzeit bleibt sie bis zum Ende des angezeigten letzten Tages sichtbar.
- Keine Änderung an Veröffentlichungsdaten, Fristberechnung, Inkrafttreten oder Berechtigungen; Anzeige aktualisiert sich beim nächsten Seitenaufruf.

- Je 49 Tests / 5.414 Assertions auf HumHub 1.18.6 und 1.19.0-beta.3 mit PHP 8.3.30 bestanden. Keine neue Migration beim Update von 1.0.31.

## [1.0.31] – 2026-10-12

### Leserechte und Übersetzungen

- Lesen für alle angemeldeten Mitglieder, Mitglieder des ursprünglichen Spaces oder ausgewählte Benutzer/Gruppen einstellen.
- Private native HumHub-Container schützen eingeschränkte Inhalte einschließlich Direktlinks; serverseitige Filter für Logbuchsuche, Widget und CSV.
- Additive Migration für den Lesecontainer; Veröffentlichung und Einwandsfrist unverändert.
- Bei eingeschränkten Leserechten sind native Space-Streams und automatische Space-Kalendererinnerungen begrenzt; Details in `docs/ai/LESERECHTE.txt`.
- Deutsche Ausgangssprache korrekt registrieren; Englisch, Deutsch und regionale Sprachvarianten prüfen.
- Standardbeschriftungen sprachunabhängig speichern; individuell gesetzte Beschriftungen erhalten.

### Einwandsfrist

- Optional „Schwerwiegender Einwand möglich bis“ in Karten und Detailansicht anzeigen.
- Zehn volle Tage ab Veröffentlichungszeitpunkt; unabhängig vom Zusatztag beim Inkrafttreten.
- Veröffentlichungszeitpunkt serverseitig festhalten; Bearbeiten verlängert die Frist nicht.
- Additive Migration für den Zeitpunkt; bei Alteinträgen bleibt die unbekannte Uhrzeit leer.
- Sechs neue Regressionstests; je Zielversion 35 Tests / 5.278 Assertions bestanden.

### Fehlerkorrekturen

- Aktive Space-Mitgliedschaft bei ausgewählten Schreibberechtigten prüfen.
- Datumswerte und Suchparameter validieren; versteckte Entscheidungstypen im Modell abweisen.
- Inkrafttreten aus Veröffentlichung berechnen und Statusregeln für Suche, Speichern und Cron vereinheitlichen.
- Organ-Zeitstempel, historischen Content-Import und Archivierung von Review-Autorbezügen korrigieren.
- Protokollkennungen und Verknüpfungen mit dokumentierten Überprüfungen beim Bearbeiten erhalten.
- Konsolenläufe ohne Oberflächen-Assets; Ressourcen über den Modulalias auflösen; Mail-Modultitel escapen.
- Nicht unterstützte Tabellenpräfixe vor Modulaktivierung und Modul-Migrationslauf abweisen. Die geprüften HumHub-Kernversionen unterstützen Präfixe nicht durchgängig; veröffentlichte Migrationen bleiben unverändert. Details in `docs/ai/PRAEFIXPRUEFUNG.txt`.
- 13 Regressionstests ergänzen. Quellen und Prüfgrenzen: `docs/ai/PROJEKT.md`, `docs/ai/REVIEW.txt`.

### Update und Prüfung

- Vor dem Update Modul und Datenbank vollständig sichern. Beide neuen Migrationen ausführen und Leseeinstellungen einmal speichern.
- Je 47 Tests / 5.402 Assertions bestanden auf HumHub 1.18.6 und 1.19.0-beta.3 mit PHP 8.3.30; ergänzende native Tests mit MySQL 8.0.44.
- Kalenderintegration und echte E-Mail-Zustellung bleiben ungeprüft. Einzelheiten und Testanleitung: `docs/ai/AKTUELLER_TESTSTAND.txt`.
- Für eine Rückkehr zum vorherigen Modulstand die passende vollständige Sicherung wiederherstellen; alleiniger Code-Downgrade reicht nicht.

## [1.0.30] – 2026-10-03

### Organ-Hierarchie und Space-Zuordnung
- Zugehörigkeit eines Spaces zu einem Organ und zuständiger Organ-Space sind wieder eindeutig getrennt
- Mehrere Spaces und Bereichskreise dürfen demselben übergeordneten Organ zugeordnet sein
- Der zuständige Organ-Space wird beim Bearbeiten des Organs ausdrücklich ausgewählt und in der gemeinsamen Übersicht angezeigt
- Die Weiterleitung verwendet vorrangig diese eindeutige Organ-Space-Zuordnung
- Das Speichern einzelner Space-Zeilen überschreibt bestehende Organ-Space-Zuordnungen nicht mehr
- Kontraste der Organ-Zeilen, Tabellenköpfe und hellen Administrationshinweise in der Dunkelansicht verbessert

## [1.0.29] – 2026-10-03

### Space-Verwaltung
- Beim Speichern werden nur noch tatsächlich geänderte Space-Zeilen übertragen
- Vollständigkeitsmarkierungen verhindern, dass serverseitig abgeschnittene Formulareinstellungen bestehende Konfigurationen überschreiben
- Zuordnung, Sichtbarkeit und Organ-Space-Einstellung funktionieren dadurch auch in Installationen mit sehr vielen Spaces zuverlässig

## [1.0.28] – 2026-10-03

### Informationsseite
- Reduzierten HumHub-Texteditor für Einleitung und alle acht Informationsbereiche ergänzt
- Formatierte Informationsinhalte über den sicheren HumHub-Rich-Text-Renderer ausgegeben
- Datei-Uploads, Erwähnungen, eingebettete Inhalte und Emojis auch auf der Informationsseite deaktiviert

## [1.0.27] – 2026-10-03

### Sicherheit
- Beschluss und Begründung auf jeweils 15.000 Zeichen begrenzt, damit übergrosse utf8mb4-Eingaben vor dem Datenbankschreiben kontrolliert abgewiesen werden
- Automatisierte Prüfung gegen ausführbare Script-, Bild- und `javascript:`-Inhalte im Rich-Text ergänzt

### Belastungstests
- Rich-Text-Kurzansichten mit 5.000 formatierten Texten geprüft
- Stapelverarbeitung der Statuspflege mit 2.000 Einträgen geprüft

## [1.0.26] – 2026-10-03

### Bedienung
- Reduzierten HumHub-Texteditor für Beschluss, Begründung und Begründung der Überprüfung ergänzt
- Datei-Uploads, Erwähnungen, eingebettete Inhalte und Emojis in den formellen Logbuchfeldern deaktiviert
- Formatierte Texte in Detail-, Druck- und Streamansicht sicher dargestellt
- Karten, Tabelle, Dashboard-Widget und CSV-Export in sauberen unformatierten Text umgewandelt

### Tests
- Automatisierte Prüfung für die Entfernung von Formatierungszeichen in Kurzansichten ergänzt

## [1.0.25] – 2026-10-03

### Administration
- Selten benötigten Abschnitt „Archiv“ ans Ende der Moduleinstellungen unmittelbar vor Speichern und Wartung verschoben

## [1.0.24] – 2026-10-03

### Navigation
- Systemadministrator:innen kehren von der Verwaltung der zuständigen Personen direkt zu den Moduleinstellungen zurück
- Mitglieder des Betriebs Logbuch ohne Systemadministrationsrecht kehren weiterhin sicher zur Logbuchübersicht zurück

## [1.0.23] – 2026-10-03

### Bedienung der Berechtigungen
- Direkten Zugang zur Verwaltung der zuständigen Personen im Abschnitt „Berechtigungen“ ergänzt
- Personenauswahl wird nur im Modus „Nur ausgewählte zuständige Personen“ angezeigt
- Leere Berechtigungsübersicht erklärt, dass zuerst ein sichtbarer Logbuch-Space eingerichtet werden muss, und verlinkt für Administrator:innen direkt zur Space-Zuordnung

## [1.0.22] – 2026-10-03

### Rollen und Schreibberechtigungen
- Rolle „Logbuch-Verantwortliche“ fachlich in „Betrieb des Logbuches“ überführt
- Der Betrieb des Logbuches kann die zuständigen Personen der Kreise ohne allgemeine HumHub-Administratorrechte verwalten
- Je Space kann zwischen allen Space-Administrator:innen und ausdrücklich ausgewählten zuständigen Personen gewählt werden
- Bestehende Installationen verwenden nach dem Update weiterhin den bisherigen Modus mit Space-Administrator:innen
- Ausgewählte Personen müssen Mitglied des betreffenden Spaces sein; nicht ausgewählte Space-Administrator:innen erhalten im Auswahlmodus kein automatisches Schreibrecht

### Dokumentierte Überprüfungen
- Überprüfungen erhalten ein Ergebnis „bleibt bestehen“ oder „wird ausser Kraft gesetzt“ sowie eine eigene Begründung
- Bei bestätigten Entscheiden wird ein neues Überprüfungsdatum verlangt, bei aufgehobenen Entscheiden der Status „Nicht mehr gültig“ gesetzt
- Überprüfungsbegründung, Ergebnis, Datum, handelnde Person und optionales Protokoll bleiben als Historie erhalten
- Der ursprüngliche Beschlusstext und die ursprüngliche Begründung werden bei einer Überprüfung nicht verändert

## [1.0.21] – 2026-10-03

### Administration
- Verwaltung der Logbuch-Organe und der Space-Zuordnungen zu einer gemeinsamen hierarchischen Liste zusammengeführt
- Organe können direkt in dieser Übersicht erstellt, bearbeitet und gelöscht werden; zugehörige Spaces stehen eingerückt darunter
- Nicht zugeordnete Spaces werden in einem eigenen Abschnitt angezeigt
- Alte Links zur separaten Organverwaltung bleiben als Weiterleitung funktionsfähig
- Doppelte Auswahl des Organ-Spaces aus dem Organformular entfernt; die eindeutige Zuordnung erfolgt zentral in der Übersicht

### Übersetzungen und Tests
- Deutsche und englische Texte der gemeinsamen Verwaltung ergänzt
- Gemeinsame Oberfläche mit HumHub 1.18 und 1.19 unter MAMP geprüft

## [1.0.20] – 2026-10-03

### Sicherheit und Datenintegrität
- Verwaltung der Logbuch-Organe zusätzlich auf Systemadministrator:innen beschränkt
- Kreisförmige Organ-Hierarchien werden bei der Validierung verhindert
- Pro Eintrag werden höchstens 50 Protokoll-Links verarbeitet; ungültige Link-Protokolle bleiben gesperrt
- Historischer CSV-Import prüft auch die maximale Titellänge und validiert Einträge vor dem Speichern erneut
- Verwendete Entscheidungstypen melden beim Löschversuch nun korrekt, weshalb sie erhalten bleiben
- Echte historische Einträge ohne HumHub-Content-Zeile werden wieder berücksichtigt, weich gelöschte Inhalte weiterhin ausgeschlossen

### Leistung und Tests
- Tägliche Statusprüfung liest grosse Logbücher speicherschonend in Blöcken von 500 Einträgen
- Laufzeittests für Eingabevalidierung, sichere Protokoll-URLs und Organ-Hierarchien ergänzt
- Wiederholbarer Belastungstest mit standardmässig 750 und lokal erfolgreich mit 5’000 Einträgen ergänzt
- Lokale Testkonfiguration für getrennte MAMP-Datenbanken portabel gemacht

## [1.0.19] – 2026-08-19

### Kalender
- Automatisch erzeugte Überprüfungstermine als reine Information ohne Zu-/Absage und Teilnehmerliste gekennzeichnet
- Vorhandene Teilnehmerzuordnungen älterer automatisch erzeugter Überprüfungstermine werden beim nächsten Abgleich entfernt

## [1.0.18] – 2026-08-18

### Systemanforderungen
- PHP-Mindestversion passend zu HumHub 1.18 von 8.1 auf 8.2 angehoben
- Statische GitHub-Prüfmatrix auf PHP 8.2 und 8.3 aktualisiert

## [1.0.17] – 2026-08-18

### Automatisierte Laufzeittests
- Offizielle HumHub-Codeception-Teststruktur ergänzt
- Datenbank-Laufzeittests für Erstellen, Bearbeiten und den HumHub-Soft-Delete-Lifecycle ergänzt
- Rechteprüfung für ausdrücklich konfigurierte Schreib- und Löschberechtigte ergänzt
- Vollständige Ausführung der Modul-Migrationskette und Prüfung des resultierenden Datenbankschemas ergänzt
- GitHub Actions testet das Modul gegen HumHub 1.18.4 und 1.19.0-beta.1 mit PHP 8.2 und 8.3

## [1.0.16] – 2026-08-17

### Workflow
- Weiterleitung und Übernahme verwenden die eindeutige Ziel-Space-ID statt des veränderlichen Space-Namens
- Noch nicht übernommene Entscheide können nicht erneut weitergeleitet und damit nicht versehentlich an ein Organ vorbeigeführt werden
- Der in „Spaces und Logbuch-Bereiche“ gewählte Organ-Space ist nun die führende Zuordnung für BG → BK → LK
- Organ-Space-Zuordnungen werden validiert und mit dem historischen Organ-Feld synchronisiert

### Events und Benachrichtigungen
- Entry-Events einheitlich über `config.php` registriert
- Die jeweils handelnde Person erhält keine eigene Benachrichtigung
- Empfängerkreis ausdrücklich wählbar: keine Benachrichtigungen, ausgewählte Gruppen, aktuell zuständiger Space oder alle aktiven Benutzer
- Bei ausgewählten Gruppen muss mindestens eine Gruppe hinterlegt sein; ein unbeabsichtigter Versand an alle findet nicht mehr statt
- Empfänger aus mehreren Benachrichtigungsgruppen werden dedupliziert und blockweise verarbeitet
- Duplikatschutz gilt pro Eintrag und Request auch bei mehreren Modellinstanzen

### Tests
- Modulprüfung um Workflow-, Event- und Benachrichtigungsregeln erweitert

## [1.0.15] – 2026-08-13

### Archiv und Datenintegrität
- Optionale Übertragung der Sociolog-Urheberschaft auf ein frei wählbares Archiv-Benutzerkonto vor einer vollständigen Benutzerlöschung ergänzt
- Logbucheinträge, Protokoll-Verknüpfungen, Entscheidungsverlauf und Organ-Zuordnungen bleiben nachvollziehbar erhalten
- Deutlicher Hinweis ergänzt, dass hochgeladene HumHub-Dokumente vor der Benutzerlöschung separat mit „Move content and users“ übertragen werden müssen

### Berechtigungen
- Automatische Inhaltsrechte für Systemadministratoren entfernt; der Zugang zu den Moduleinstellungen bleibt erhalten
- Globale HumHub-Gruppenberechtigungen für Erstellen, Bearbeiten und Löschen werden nun tatsächlich ausgewertet
- Benutzer-, Gruppen- und Space-Rechte bleiben kombinierbar
- Erläuterungen der Schreib-, Verwaltungs- und Löschrechte präzisiert

### Darstellung und Übersetzungen
- Fehlendes Uhr-Symbol bei „Status und Fristen“ mit HumHub-kompatibler Font-Awesome-Klasse korrigiert
- Deutsche und englische Texte für Archivfunktion und Berechtigungen ergänzt

## [1.0.14] – 2026-08-13

### Übersetzungen
- Fehlende englische Übersetzung für den Status „Überprüfung fällig“ ergänzt
- Gespeicherte Standardbezeichnungen werden nun entsprechend der aktuell gewählten Sprache übersetzt
- Frei konfigurierte eigene Bezeichnungen bleiben unverändert erhalten

## [1.0.13] – 2026-08-04

### Administration und Navigation
- Administrationsbereich in klar getrennte Themenblöcke gegliedert
- Verwaltung der Logbuch-Organe sowie der Spaces und Logbuch-Bereiche zusammengeführt und erläutert
- Position des Logbuchs im HumHub-Hauptmenü konfigurierbar gemacht

### Darstellung
- Statusfarben unabhängig vom verwendeten HumHub-Theme vereinheitlicht und kontrastreicher gestaltet
- Statusdarstellung in Übersicht, Dashboard-Widget und Stream konsistent gemacht

### Übersetzungen
- Deutsche und englische Texte für die neuen Einstellungen und Verwaltungsbereiche ergänzt

## [1.0.12] – 2026-08-03

### Metadaten
- Lizenzfeld in `module.json` entsprechend dem HumHub-Standard von `license` auf `licence` korrigiert; das Composer-Metadatenfeld bleibt standardkonform `license`

### Tests
- GitHub-Actions-Prüfung für PHP 8.1 und 8.3 ergänzt
- Automatische PHP- und JavaScript-Syntaxprüfung hinzugefügt
- Eigenständige Modulprüfung für Versionen, Lizenzfelder, HumHub-Mindestversion, Berechtigungsregistrierung und externe Ressourcen ergänzt

### Sicherheit
- Alternatives `run.php`-Fallback ausdrücklich auf PHP-CLI-Aufrufe beschränkt
- Temporäre Importvorschauen werden nach 24 Stunden und beim Erstellen einer neuen Vorschau automatisch bereinigt

### Darstellung
- Eigenständiges HumHub-Modulbild unter `resources/module_image.png` ergänzt
- SVG-Symbol als klar erkennbares Logbuch mit bestätigtem Entscheid überarbeitet

## [1.0.11] – 2026-08-03

### Ressourcen
- Externe DataTables-Dateien und die extern geladene Sprachdatei entfernt; Filterung und Seitennavigation erfolgen weiterhin serverseitig über HumHub/Yii
- Externes Font Awesome 6 entfernt und alle Symbole auf das von HumHub 1.18 und 1.19 mitgelieferte Font-Awesome-Bundle umgestellt
- Versehentlich in `icon.css` enthaltenen ungültigen PHP-Text entfernt
- Tabellen-Druckschaltfläche über PJAX-sichere Ereignisdelegation stabilisiert

## [1.0.10] – 2026-08-03

### Berechtigungen
- Die ungenutzte Space-Berechtigung `ViewEntry` wurde entfernt, damit das globale Lesekonzept in Code und Dokumentation eindeutig abgebildet ist
- Das globale Lese- und Exportrecht für alle angemeldeten Benutzer bleibt unverändert erhalten

### Ressourcen
- Die Entscheidungsarten-Sortierung benötigt kein extern von jsDelivr geladenes SortableJS mehr und verwendet stattdessen lokales Modul-JavaScript

## [1.0.9] – 2026-08-02

### HumHub-Content-Lifecycle
- Logbuch-Einträge mit zugehörigem HumHub-Content-Datensatz werden nun über `Content::softDelete()` gelöscht
- Historische Einträge ohne Content-Datensatz bleiben über einen klar begrenzten Kompatibilitäts-Fallback löschbar
- Weich gelöschte Einträge werden unmittelbar aus Übersicht, Detailansicht, Suche, Dashboard-Widget, CSV-Export und Statusprüfung ausgeblendet
- Kalenderverknüpfungen werden nach erfolgreichem Löschen weiterhin bereinigt
- Sociolog-Einträge sind explizit dem Modul `sociolog` zugeordnet

### Berechtigungen
- Die globale Lesbarkeit des Vereinslogbuchs bleibt erhalten: Alle angemeldeten Benutzer sehen die Einträge unabhängig von der Mitgliedschaft im zugehörigen Space
- Erstellen, Bearbeiten, Weiterleiten und Löschen bleiben weiterhin auf die zuständigen Space-Administratoren und Logbuch-Administratoren beschränkt

## [1.0.8] – 2026-07-24

### Neu
- Optionale Informationsseite „So funktioniert das Logbuch“ mit frei konfigurierbaren Textkarten, Dokumentlink und sicherem E-Mail-Link
- Optionale feste Entscheidungsart für neue Einträge sowie ein- und ausblendbare Entscheidungstypen
- Frei konfigurierbare Feld- und Statusbezeichnungen
- Optional automatisch gesetztes Veröffentlichungsdatum
- Optional verpflichtendes Überprüfungsdatum für neue Einträge
- Optionale eingeschränkte Pflege nach erreichter Überprüfung: zuständige Space-Administratoren dürfen nur das nächste Überprüfungsdatum setzen und ein zusätzliches Protokoll verlinken
- Historischer CSV-Import mit zweistufiger Vorschau, Validierung, Duplikatschutz und herunterladbarer Vorlage
- Seitennavigation für Logbuchübersichten mit mehr als 50 Einträgen
- Optionale zusätzliche manuelle Status „Schwerwiegender Einwand“ und „Ersetzt“
- Eigene Logbuch-Manager-Benutzer und -Gruppen

### Sicherheit und Datenintegrität
- Historische Importe sind nur für Systemadministratoren verfügbar und werden vollständig in einer Datenbanktransaktion ausgeführt
- Importdateien werden serverseitig auf Spalten, Ziel-Spaces, Entscheidungsarten, Datumswerte und Duplikate geprüft
- Importierte historische Einträge erzeugen keine nachträglichen Benachrichtigungen, Kalendertermine oder Stream-Aktivitäten
- Veröffentlichte Einträge können optional für reguläre Bearbeitungen gesperrt werden
- Sichere Behandlung von Informations- und Protokoll-Links

### Verbessert
- Entscheidungstyp-Kopf kann in Karten, im Dashboard-Widget und in der Detailansicht ausgeblendet werden
- Ausgeblendete Entscheidungstypen verschwinden bei neuen Einträgen und in Filtern; bestehende Einträge bleiben erhalten
- Fokusdarstellung, Tabellenbedienung und Formulare weiter für Tastatur und Screenreader optimiert
- Historischer Import und manuelle Statusprüfung im Administrationsbereich unter „Wartung“ zusammengefasst
- Kompatibilität erfolgreich mit HumHub 1.18 und 1.19 getestet

## [1.0.7] – 2026-07-21

### Kompatibilität
- TopMenu-Registrierung an die HumHub-1.19-API angepasst
- Veralteten Aufruf `TopMenu::getItems()` entfernt
- Duplikatprüfung über das in HumHub 1.18 und 1.19 verfügbare `getEntryById()` umgesetzt
- Menüeintrag weiterhin als `MenuLink` über `addEntry()` mit der ID `topmenu-sociolog` registriert
- Aktive Menüerkennung verwendet den PHP-Nullsafe-Operator
- Admin-Formulare auf die unter HumHub 1.18 und 1.19 verfügbare Yii-`ActiveForm`-Klasse umgestellt

## [1.0.6] – 2026-07-19

### Barrierefreiheit
- Sichtbare Fokusmarkierungen für Tastaturbedienung ergänzt und Mausfokus ausgeblendet
- Karten auf native Links umgestellt; Bearbeiten und Löschen separat per Tastatur erreichbar
- Tabellen, Überschriften, Icon-Aktionen und dynamische Protokollfelder semantisch beschriftet
- Space- und Organverwaltung für Tastatur und Screenreader überarbeitet
- Tabellen bei starkem Zoom und kleinen Bildschirmen horizontal scrollbar gemacht
- WCAG-basierte Kontrastberechnung für frei wählbare Entscheidungstyp-Farben ergänzt
- Fehlerzusammenfassung und feldnahe Protokollfehler mit automatischem Fokus ergänzt
- Links auf neue Fenster werden angekündigt
- Systemeinstellung „Bewegung reduzieren“ wird berücksichtigt

### Verbessert
- Verständliche und übersetzbare Feldnamen in der Organverwaltung
- README um Hinweise zur Barrierefreiheit und zu manuellen Tests erweitert
- Lizenzangaben in Modul- und Composer-Metadaten auf AGPL-3.0 vereinheitlicht

## [1.0.5] – 2026-07-12

### Sicherheit
- Serverseitige Space-Berechtigungen für Erstellen und Organwechsel
- Workflow- und Admin-Aktionen auf POST/CSRF-geschützte Aufrufe begrenzt
- CSV-Formelinjektion und unsichere Protokoll-URLs verhindert
- Bearbeitungsrecht folgt nach Weiterleitung dem aktuellen Entscheidungsorgan

### Verbessert
- HumHub-Container-Permissions und Controller-Zugriffsregeln integriert
- Transaktionen für Einträge, Protokolle und Workflow-Schritte ergänzt
- Protokoll-Links in zentraler Tabelle vereinheitlicht
- Optionale Kalenderintegration und Cron-Fallback robuster gemacht
- HumHub-ActiveRecord, AssetBundle und Tabellenpräfixe verwendet
- Fehlerbehandlung, Validierung und Übersetzungen erweitert

## [1.0.4] – 2026-04-01

### Neu
- Automatische Statusläufe über HumHub EVENT_ON_DAILY_RUN integriert
- run.php bleibt als optionaler Fallback-Cron für Shared Hosting verfügbar

### Verbessert
- Cron-Architektur vereinfacht (kein zusätzlicher Modul-Cron notwendig)
- README vollständig überarbeitet und strukturiert
- Installationsanleitung präzisiert (HumHub-Cron statt Modul-Cron empfohlen)
- Dokumentation der Statuslogik erweitert
- Shared-Hosting-Kompatibilität klar dokumentiert

## [1.0.3] – 2025-12-14

### Neu
- Installationsanleitung für Cyon in README ergänzt  
- README überarbeitet (klare Modulübersicht, Struktur, Cronjob-Beispiel)  
- Versionsnummer auf 1.0.3 aktualisiert  
- Anzeige im **Kalender** ein- und ausschaltbar (Modul-Einstellungen)  
- Anzeige im **Stream** ein- und ausschaltbar (Modul-Einstellungen)  

### Verbessert
- Module.php überarbeitet (Konsistenz, Logging, Struktur, Bootstrap-Kompatibilität)  
- Module.json ergänzt um Autor, Homepage, License und Keywords

---

## [1.0.2] – 2025-12-09

### Neu
- Vollständige englische Übersetzung (UK) ergänzt  
- Überarbeitete Admin-Ansicht mit Benutzer- und Gruppenoptionen  
- Erweiterte Einstellungen für Organe, Farben und Links  
- Neues Layout für Filter und Suchmaske mit Icons und Buttons  
- Anzeige-Optimierungen für Mobile (Bootstrap 5 Responsive Grid)  

### Verbessert
- Überarbeitete Statuslogik (Auto-Wechsel „Nicht in Kraft“ → „Gültig“)  
- Stabilere Cron-Verarbeitung (run.php verbessert)  
- Einheitliche Übersetzungen in allen Formularen und Labels  
- Überarbeitete Notification-Templates (Web + Mail)  
- CSS-Anpassungen für DataTables und Icons  

### Behoben
- Fehler beim Laden der Sidebar (Dashboard-Events)  
- Unvollständige Übersetzungen in Filter-Labels  
- Doppelanzeige von Benachrichtigungen auf manchen Instanzen  
- Kleinere HTML- und CSS-Validierungsprobleme behoben  

---

## [1.0.1] – 2025-12-03

### Neu
- Einführung der automatischen Statusläufe  
- Neues Cron-Script `run.php`  
- Admin-Einstellungen für Entscheidungsarten, Organe und Farben  
- Mehrsprachigkeit (Deutsch / Englisch Grundversion)

### Behoben
- Diverse kleinere Layoutfehler in der Index-Ansicht  
- Probleme beim Export (UTF-8 Fix)

---

## [1.0.0] – 2025-11-20

### Initiale Veröffentlichung
- Basismodul Sociolog mit CRUD-Funktionalität  
- Entscheidungstypen: Grundsatz, Prozess, Richtlinie  
- Dashboard-Integration  
- Notification-System für neue und geänderte Einträge
