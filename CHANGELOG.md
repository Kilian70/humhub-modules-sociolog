
# 📄 Changelog – Sociolog

Alle relevanten Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

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
