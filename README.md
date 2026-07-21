## TL;DR

- **Sociolog** ist ein Logbuch-Modul für HumHub zur Dokumentation von Beschlüssen, Richtlinien und Entscheiden
- Einträge verfügen über **Statuslogik**, **Überprüfungsdaten** sowie **Stream- und Kalender-Integration**
- Automatische Status- und Kalenderläufe erfolgen über den täglichen HumHub-Cron (`php protected/yii cron/run`)
- Ein zusätzlicher Modul-Cronjob ist **nicht erforderlich**; `run.php` steht bei Bedarf als Fallback zur Verfügung
- Das Modul nutzt HumHub-Events (`EVENT_ON_DAILY_RUN`) für automatische Statuswechsel
- Kalendertermine werden automatisch im **Space des zuständigen Organs** erstellt
- Der Cron-Mechanismus ist **Shared-Hosting-kompatibel** (z. B. Cyon) und benötigt nur die regulären HumHub-Cronjobs
 
# Sociolog – Logbuch-Modul für HumHub

**Version:** 1.0.7
**Author & Maintainer:** Kilian Schmid 
**Kompatibel mit:** HumHub 1.18+   
**Lizenz:** GNU Affero General Public License v3.0 (AGPL-3.0)  

## Beschreibung

Das **Sociolog-Modul** („Logbuch“) dokumentiert **Grundsatzentscheide**, **Prozessentscheide** und **Richtlinien** und unterstützt die strukturierte Erfassung von Entscheidungen im Rahmen soziokratischer Zusammenarbeit.  
Es bietet eine zentrale, transparente Übersicht aller Beschlüsse inklusive Statusverlauf, Überprüfungsdaten, Benachrichtigungen sowie Dashboard-Darstellung.

Das Modul wurde speziell für gemeinschaftliche Wohn- und Organisationsprojekte entwickelt, um Entscheidungsprozesse nachvollziehbar, transparent und verbindlich abzubilden.

## Hauptfunktionen

- **Eintragsverwaltung:** Titel, Beschluss, Datum, Status, zuständiges Organ, Themenhüter:in  
- **Entscheidungsarten:** Grundsatzentscheid, Prozessentscheid, Richtlinie, Delegierter oder Weitergeleiteter Entscheid  
- **Statuslogik:** Automatische Übergänge (z. B. von *Nicht in Kraft* → *Gültig* nach definierten Tagen)  
- **Überprüfungsdaten:** Erinnerung und Benachrichtigung bei anstehenden Prüfungen  
- **Benachrichtigungen:** Glocken- und E-Mail-Benachrichtigungen bei neuen oder geänderten Einträgen  
- **Filter & Suche:** Jahr, Organ, Entscheidungsart, Status und Volltextsuche  
- **Darstellung:** Karten- und Tabellenansicht, Druck-Ansicht, CSV-Export  
- **Verwaltung:** Eigene Admin-Seite zur Konfiguration von Organen, Farben, Links und Standardwerten  
- **Mehrsprachigkeit:** Deutsch und Englisch (UK)  
- **Kalender-Integration** (Überprüfungstermine sichtbar)  
- **Stream-Integration**

## Barrierefreiheit

Das Modul unterstützt die Bedienung mit Tastatur und Screenreader und berücksichtigt
die Systemeinstellung **Bewegung reduzieren**. Zu den umgesetzten Massnahmen gehören:

- deutlich sichtbare Fokusmarkierungen nur bei Tastaturbedienung
- native Links und vollständig per Tastatur erreichbare Aktionen
- zugängliche Namen für Icon-Schaltflächen und dynamische Protokollfelder
- semantische Überschriften, Tabellenbeschriftungen und Spaltenzuordnungen
- kontrastabhängige schwarze oder weisse Schrift auf frei gewählten Typfarben
- zugängliche Fehlerzusammenfassungen und feldnahe Fehlermeldungen
- scrollbarere Tabellen bei kleinen Bildschirmen und starker Vergrösserung
- Ankündigung von Links, die ein neues Fenster öffnen

Die Reihenfolge der Entscheidungstypen kann per Maus durch Ziehen verändert werden.
Als Tastaturalternative kann im Bearbeitungsformular die **Sortierreihenfolge** als Zahl
eingetragen werden.

Eine formelle WCAG-Zertifizierung ist damit nicht verbunden. Vor einer öffentlichen
Freigabe werden zusätzliche Praxistests mit Browser-Zoom, Tastatur und Screenreader
empfohlen.

## Benutzer löschen (WICHTIG)
❌ Nicht verwenden

Beim Löschen eines Benutzers darf die folgende Option NICHT aktiviert werden:

**„Alle Beiträge des Benutzers löschen“**

Diese Option löscht alle Inhalte unwiderruflich –
inklusive Sociolog-Einträgen (Systemverhalten von HumHub).   

## Technische Details

| Bereich | Beschreibung |
|----------|---------------|
| **Modul-ID** | `sociolog` |
| **Namespace** | `humhub\modules\sociolog` |
| **Verwendete Frameworks** | Yii2 (HumHub Core 1.18+), Bootstrap 5 |
| **Datenbanktabellen** | `sociolog_entry`, `sociolog_decision_type` |
| **Benachrichtigungen** | `EntryCreated`, `EntryUpdated` |
| **Views** | Formulare, Index-Ansicht, Detail-View, Admin-Seite, Notification-Views |
| **Assets** | DataTables, FontAwesome 6, eigene CSS-/JS-Bundles |

## Installation

1. **Ordner kopieren** nach protected/modules/sociolog  

2. Stelle sicher, dass dein Server folgende Anforderungen erfüllt:
   - PHP ≥ 8.1  
   - MySQL/MariaDB  
   - Aktivierte HumHub-Cronjobs (`php protected/yii cron/run`) 
   - Schreibrechte im HumHub-Modulverzeichnis 

3. Im **Adminbereich → Module → Modul hinzufügen** aktivieren  

4. **Nach Aktivierung:** Menüpunkt Logbuch erscheint in der oberen Navigation

5. Mindestens ein **Organ** muss angegeben werden. Die definierten Organe erscheinen später in den entsprechenden Auswahlfeldern für Einträge.

6. **Benachrichtigungen testen:** Erstelle oder ändere einen Eintrag, um Glocken- und Mail-Benachrichtigungen zu prüfen.

**Dashboard-Widget:** Neueste Beschlüsse sind automatisch sichtbar

## Automatische Statusläufe & Cronjobs

Das Modul unterstützt automatische Statuswechsel und Erinnerungen über einen **Cron-Mechanismus.**
Damit können Einträge nach definierten Zeiträumen automatisch von „nicht in Kraft“ auf „gültig“ wechseln
oder nach Ablauf einer Frist in den Status „zur Überprüfung“ gesetzt werden.

## Cronjobs

Das Sociolog-Modul nutzt den täglichen HumHub-Cron:

`php protected/yii cron/run`

Über diesen Cron werden automatisch ausgeführt:

- Statuswechsel von Einträgen
- Überprüfungslogik
- Kalender-Synchronisation

Ein zusätzlicher Modul-Cronjob ist normalerweise nicht erforderlich.

## Alternative: run.php (Fallback für Shared Hosting)

Falls der HumHub-Cron nicht verfügbar ist, kann optional ein eigener Cronjob verwendet werden:

`php protected/modules/sociolog/run.php`

Diese Variante eignet sich für Hosting-Umgebungen:

- ohne stabilen Zugriff auf `protected/yii`
- mit mehreren parallelen HumHub-Instanzen
- mit instanzbezogener Cronsteuerung im Hosting-Panel


Beispiel (Cyon Shared Hosting):


```bash
0 0 * * * /opt/alt/php83/usr/bin/php /home/IHRBENUTZER/public_html/ORDNERNAME/protected/modules/sociolog/run.php >/home/IHRBENUTZER/sociolog_cron.log 2>&1
```

Dieser startet den Statuslauf einmal täglich um Mitternacht.

`run.php` ermittelt den HumHub-Ordner automatisch relativ zum Modulpfad.
Im Skript selbst muss deshalb kein installationsabhängiger Serverpfad
eingetragen werden.


## Statuslogik (kurz & einfach)

Wichtig dabei:
Die Automatik dient der inhaltlichen Überprüfung, nicht der technischen Spielerei.

## Die Status im Überblick

**Nicht in Kraft (pending)**

- Der Entscheid ist erfasst, aber noch nicht wirksam

- z. B. wegen Rekursfrist oder zukünftigem Inkrafttreten

Automatik:

Wird automatisch „Gültig“, sobald das Inkrafttretedatum erreicht ist

**Gültig (valid)**

- Der Entscheid ist aktuell wirksam

- kann manuell oder automatisch erreicht werden

Wichtig:
Es gibt keinen Unterschied zwischen manuell und automatisch gültig.

Automatik:

Nach 2 Jahren (oder einem gesetzten Überprüfungsdatum)

Wechsel zu „Überprüfung“

**Überprüfung (review)**

- Der Entscheid soll bewusst überprüft werden

- Signal: Handlungsbedarf

Regeln:

- bleibt so lange bestehen, bis jemand handelt

- keine automatische Rückkehr zu „Gültig“

**Nicht mehr gültig (expired)**

- Der Entscheid ist ausser Kraft gesetzt

Besonderheit:

- wird nie automatisch gesetzt

- wird nie automatisch verändert

- Kann nur manuell geändert werden

**Status-Ablauf (vereinfacht)**

Nicht in Kraft
   ↓
Gültig
   ↓
Überprüfung
   ↓ (manuell)
Gültig  oder  Nicht mehr gültig


Nicht mehr gültig
→ Endzustand

**Automatische Prüfung (Cron)**

- Die Statusprüfung läuft automatisch im Hintergrund

- Sie arbeitet nur mit Datenbankwerten

- Sie ändert:

	- pending → valid

	- valid → review

- Sie ändert nie:

	- review

	- expired

Das sorgt für:

- stabile Abläufe

- nachvollziehbare Statuswechsel

- keine unerwarteten Automatismen


## Modul-Aufbau

```text
sociolog/
├── assets/                             	# Registrierung von Modul-CSS und -JS
│   └── SociologAsset.php                	# Bindet Styles und Skripte des Moduls ein
│
├── controllers/							# Webcontroller (HTTP)
│   ├── AdminController.php					# Admin-Einstellungen des Moduls
│   ├── DecisionTypeController.php			# Verwaltung der Entscheidungsarten
│   └── EntryController.php					# CRUD-Logik für Logbuch-Einträge
│
├── messages/								# Übersetzungen (DE/EN)
│   ├── de/
│   └── en/
│
├── migrations/								# Datenbankmigrationen
│
├── models/									# ActiveRecord-Modelle und Formularlogik
│   ├── Entry.php							# Hauptmodell eines Logbuch-Eintrags
│	├── EntryBase.php						# Basisklasse mit gemeinsamer Logik (abstrakt)
│   ├── EntrySearch.php						# Such- und Filterlogik für Listen / Tabellen
│   ├── DecisionType.php					# Entscheidungstypen
│   ├── SettingsForm.php					# Formularmodell für Modul-Einstellungen
│	└── LocalCreatorBehavior.php			# Behavior zur automatischen Ermittlung des Erstellers
│
├── notifications/							# Benachrichtigungen (Glocke / Mail)
│   ├── EntryCreated.php					# Notification bei neuem Eintrag
│   ├── EntryUpdated.php					# Notification bei Änderungen
│	└── SociologNotificationCategory.php	# Eigene Kategorie für Sociolog-Benachrichtigungen
│
├── permissions/							# Rechte und Berechtigungen
│   ├── CreateEntry.php						# Recht: Eintrag erstellen
│	├── DeleteEntry.php						# Recht: Eintrag löschen
│   ├── UpdateEntry.php						# Recht: Eintrag bearbeiten
│	└── ViewEntry.php						# Recht: Eintrag ansehen
│
├── resources/								# Statische Ressourcen
│   └── css/js								# Modul-Styles und JavaScript-Dateien
│
├──services/								# Fachlogik / Integrationen
│	├── SociologCalendarService.php			# Kalender-Synchronisation
│	├──	SociologStatusService.php			# Automatische Statuspflege der Einträge (Cron)
│	└── SociologStreamService.php			# Stream-Integration
│
├── views/									# View-Templates
│   ├── entry/								# Ansichten für Logbuch-Einträge
│   │ 	├── _card.php						# Kartenansicht
│   │ 	├── _form.php						# Formular (Create / Update)
│   │ 	├── _search.php						# Such- und Filtermaske
│   │ 	├── _table.php						# Tabellenansicht
│   │ 	├── create.php						# Neuer Eintrag
│   │ 	├── index.php						# Übersicht / Liste
│   │ 	├── print.php						# Druckansicht
│   │ 	├── update.php						# Bearbeiten
│   │ 	└──	view.php 						# Detailansicht
│   │
│	├── admin/								# Admin-Views
│   │ 	└──	index.php						# Modul-Einstellungen
│	│
│	├── decision-type/						# Verwaltung der Entscheidungstypen
│   │ 	├── _form.php
│   │ 	├── create.php
│   │ 	├── index.php
│   │ 	└──	update.php
│	│
│   ├── notifications/						# Verwaltung von Notification-Einstellungen
│   │ 	├── _form.php
│   │ 	├── create.php
│   │ 	├── index.php
│   │ 	└──	update.php
│
├── widgets/								# Widgets für Dashboard, Stream und Navigation
│   ├── LatestEntries.php					# Widget: Neueste Einträge
│   ├── WallEntry.php						# Stream-Darstellung eines Eintrags
│ 	└── views/
│ 		└── latestEntries.php				# View für LatestEntries-Widget
│ 		└── wallEntry.php					# View für Stream-Eintrag
│
├── CHANGELOG.md							# Versions- und Änderungsverlauf
├── Events.php								# Event-Registrierungen (z. B. afterSave)
├── Module.php								# Zentrale Modul-Klasse
├── module.json								# Metadaten (Name, Version, HumHub-Kompatibilität)
├── run.php                                 # Optionales Fallback-Cron-Skript für Shared Hosting
│                                           # Führt Status- und Kalenderlogik ohne yii cron/run aus
│                                           # Standardbetrieb erfolgt über HumHub EVENT_ON_DAILY_RUN
│
└── README.md								# Dokumentation des Moduls
```


## Berechtigungen

| Berechtigung | Beschreibung              | Standard |
|--------------|---------------------------|----------|
| ViewEntry    | Einträge ansehen          | Alle angemeldeten Nutzer |
| CreateEntry  | Neue Einträge erstellen   | Ersteller / Admin |
| UpdateEntry  | Einträge bearbeiten       | Ersteller / Admin |
| DeleteEntry  | Einträge löschen          | Admin |

Berechtigungen können über **Admin → Rechte → Sociolog** angepasst werden.


## Datenbankstruktur

### Tabellen

| Tabelle | Beschreibung |
|--------|--------------|
| `sociolog_entry` | Haupttabelle für alle Beschlüsse |
| `sociolog_decision_type` | Definition der Entscheidungsarten |
| `sociolog_organ` | (optional) Organ-Farben und Bezeichnungen |


### Empfohlene Indexe

```sql
CREATE INDEX idx_entry_organ  ON sociolog_entry (organ);
CREATE INDEX idx_entry_year   ON sociolog_entry (year);
CREATE INDEX idx_entry_type   ON sociolog_entry (decision_type_id);
CREATE INDEX idx_entry_status ON sociolog_entry (status);
CREATE INDEX idx_entry_date   ON sociolog_entry (decision_date); 
```

Hinweise zu den Indexen: 
- Sinnvoll für Filter nach Organ und Jahr 
- Verbessert Performance bei Tabellen- und Kartenansichten 
- Optimiert Status- und Entscheidungsart-Auswahl 
- Auch auf Shared Hosting unkritisch 
- Keine Redundanz, keine unnötigen Indexe


## Ereignisse

| Ereignis | Beschreibung |
|--------|--------------|
| EntryCreated | Neuer Logbucheintrag wurde erstellt |
| EntryUpdated | Bestehender Eintrag wurde geändert |
| EVENT_ON_DAILY_RUN | Führt automatische Statusänderungen aus |

### Kalenderdarstellung

Überprüfungstermine werden als **Kalendereinträge im Space des zuständigen Organs**
angelegt.

- sichtbar im Space-Kalender
- nicht im Activity-Stream
- Zugriff gemäss Space-Mitgliedschaft
- Klick auf den Termin öffnet den zugehörigen Logbuch-Eintrag

Die Kalender-Integration ist optional und kann in den
Modul-Einstellungen jederzeit deaktiviert werden.

## Support & Weiterentwicklung

Dieses Modul wird aktiv im Kontext von
gemeinschaftlichen Wohn- und Organisationsprojekten eingesetzt.

Anpassungen, Erweiterungen oder strukturelle Änderungen
sollten immer im Einklang mit der bestehenden Cron- und Event-Architektur erfolgen.

🧠 Tipps für Administrator:innen

Prüfe gelegentlich die Logdatei: - runtime/sociolog-cron.log
								 - runtime/logs/app.log

Führe php protected/yii migrate/up --includeModuleMigrations=sociolog aus nach Updates

Im Adminbereich: Sociolog → Einstellungen für Modulnamen, Widget-Reihenfolge etc.

© 2026 EinViertel Winterthur – Modul „Sociolog“ (Logbuch)
