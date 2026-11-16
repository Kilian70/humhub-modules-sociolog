# Prüfbericht


## Explizite HumHub-1.19.4-Prüfung – 16.11.2026

Auf Nutzerauftrag wird die vorhandene GitHub-Prüfung um HumHub 1.19.4 am offiziellen Release-Commit `b8881d5ea7ee27384a33adeaa23100c73472c4ce` erweitert (PHP 8.2, 8.3, 8.4). Bestehende Versionsprüfungen bleiben erhalten. Nur Workflow-Konfiguration, gegebenenfalls Prüfmetadaten und Dokumentation geändert; keine produktive Modul-PHP-, JavaScript-, Schema- oder Installationsänderung.

Quellen am 16.11.2026 geprüft: offizieller Tag v1.19.4 und Core-Konfiguration (Version 1.19.4), CLAUDE.md und docs/develop/module-migrate-1.19.md am obigen Commit. Keine neue HumHub-API eingeführt. Für Reservation bleibt Calendar 1.9.5 (`9d2a8b5da62e34a4ca2495edc6b1f6eb40e4a904`), für Messenger-Lesebestätigung das Originalmodul 3.4.6 (`f1f6af8e2fa19d40d21eace55091122269670552`) unverändert; beide Manifeste erlauben HumHub ab 1.19.

Commits, Pushes und PRs sind ausdrücklich freigegeben; Merge, Release und Deployment sind nicht Teil dieses Auftrags. Ausführung in kurzlebigen GitHub-Umgebungen mit synthetischen Daten. Keine produktive oder lokale MAMP-Installation angefasst. Die tatsächlich abgeschlossenen Tests und Grenzen werden in der PR-Beschreibung mit Run-Links dokumentiert; ein Syntaxcheck allein ist kein Kompatibilitätsnachweis.

Die bestehenden fachlichen Tests werden für die neue Zielversion unverändert ausgeführt. Keine Tests abgeschaltet und keine Modul-Mindestversion angehoben. Basiskontrollen der vorhandenen Workflows bleiben erhalten. Ergebnisse sind bis zum Abschluss der aktuellen PR-Checks ausdrücklich offen.
