
# 📄 Changelog – Sociolog

Alle relevanten Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

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
