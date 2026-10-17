# Logbuch 1.0.35 – Beschlüsse bei der Überprüfung aufheben oder ersetzen

Release vom 17.10.2026 auf Basis von 1.0.34.

## Drei Ergebnisse einer Überprüfung

Auch bei eingeschränkter Pflege können zuständige Personen ab dem Überprüfungsdatum wählen:

- **Entscheid bleibt bestehen:** ein zukünftiges Überprüfungsdatum festlegen.
- **Nicht mehr gültig (ausser Kraft gesetzt):** der Beschluss wird sofort als nicht mehr gültig gekennzeichnet; kein nächstes Überprüfungsdatum.
- **Ersetzt durch einen neuen Beschluss:** einen HTTP-/HTTPS-Link zum neuen Beschluss erfassen; kein nächstes Überprüfungsdatum. Der Nachfolgelink erscheint im Überprüfungsverlauf.

Eine neue Begründung ist bei jedem Ergebnis Pflicht. Die ursprüngliche Begründung, Beschlusstext, weitere Eintragsangaben und vorhandene Protokolle bleiben unverändert. Ein zusätzliches Protokoll kann verlinkt werden. Die Statusautomatik reaktiviert aufgehobene oder ersetzte Beschlüsse nicht.

Die vorhandenen Zuständigkeits-, Lese- und Fälligkeitsprüfungen gelten weiter. Bei ausgeschalteter Pflegeoption sind volle Bearbeitungsrechte erforderlich. Die Ergebniswahl ist unabhängig vom Entscheidungsworkflow und der Einstellung für erweiterte Statuswerte verfügbar.

## Update und Prüfung

**Eine neue additive Migration ergänzt `replacement_url` in der Überprüfungstabelle. Vor der Nutzung die Modulaktualisierung vollständig ausführen.** Bestehende Überprüfungen bleiben erhalten; veröffentlichte Migrationen wurden nicht verändert.

- HumHub 1.18.6 und reguläre 1.19.0: je 106 Tests / 6.313 Assertions erfolgreich.
- Nach der letzten Formularanpassung je 14 gezielte Tests / 241 Assertions erfolgreich.
- MAMP-Webformulare per authentifiziertem HTTP geprüft: Aufhebung, Ersatz, Pflichtlink und erhaltene Originalangaben. Web-PHP 8.3.14, MySQL 8.0.40. Keine erneute visuelle Browser-/Richtext-Abnahme.
- Neue Migration mit bestehenden Daten und auf leerer Tabelle geprüft; vollständige Neuinstallation mit 26 Migrationen unter beiden Zielständen erfolgreich. Die historische Installationsfolge benötigt einen SQL-Modus ohne `NO_ZERO_DATE` wegen eines alten Null-Datums-Vergleichs; die neue additive Migration funktioniert separat auch mit dem Standard-SQL-Modus.
- PHP-Syntax unter 8.3.14 und 8.5.2 sowie Modulmetadaten geprüft.

Die bestehenden Grenzen bei Tabellenpräfixen, nativer Suche/Streams und eingeschränkten Leserechten gelten weiter. Keine neue Mail-/Kalenderabnahme oder umfassende CSRF-Angriffsprüfung. Entwicklerversionen von HumHub sind keine nachgewiesenen Zielversionen.

[Update-Anleitung](UPDATE-1.0.35.md) · [Detaillierter Prüfbericht](docs/ai/UEBERPRUEFUNG-ERGEBNISSE-2026-10-17.txt).
