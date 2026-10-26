# Logbuch 1.0.36 – Benachrichtigungen für Stable und Develop

Release vom 26.10.2026 auf Basis von 1.0.35.

## Änderung

HumHub Develop hat die Benachrichtigungsschnittstelle umgebaut: statische Kategorien, finale Kategorienwerte, datensatzgebundene Nachrichten und ein neuer Versandaufruf. Die bisherige Sociolog-Klasse führte deshalb beim Laden zu einem PHP-Abbruch. Beide Benachrichtigungsklassen wählen jetzt anhand der tatsächlich vorhandenen Schnittstelle die passende Anbindung.

- HumHub Stable verwendet die bisherigen Web-/Mailvorlagen und Instanzmethoden.
- Der geprüfte Develop-Stand verwendet seine native Kategorie, Nachrichtenparameter und statische Versandfunktion.
- Öffentliche Klassennamen, bisher gespeicherte Benachrichtigungen, Empfängerauswahl und Leserechte bleiben erhalten. Bei der neuen Anbindung wird das Leserecht zusätzlich am nativen Empfangshook geprüft.
- Die bisherige Kategorienklasse wird auf Develop nicht mehr von einer finalen Kernklasse abgeleitet.

Ein Upgrade von 1.18.6 auf die reguläre 1.19.0 ist nicht die Ursache des gemeldeten Fehlers; betroffen war die geänderte Develop-Schnittstelle.

## Prüfung und Update

Beide gemeinsamen MAMP-Instanzen (1.18.6 und 1.19.0) bestehen je 44 gezielte Prüfungen für Klassenregistrierung, Kategorien und Web-/Maildarstellung. Zusätzlich wurden die neuen Schnittstellen gegen echte Develop-Kernklassen geprüft. Der vollständige GitHub-Lauf und der geprüfte Kernstand werden im Prüfbericht beziehungsweise in den Release-Hinweisen festgehalten. Keine zusätzliche lokale HumHub-Installation und keine Fixture-Resets der gemeinsamen Datenbanken.

**Keine neue Datenbankmigration gegenüber 1.0.35.** Funktionen und Migrationen früherer Versionen sind enthalten. Vor einem Update Datenbank und Dateien sichern; das Modul vollständig ersetzen, damit auch die neuen Unterordner übernommen werden. Nicht deinstallieren.

Develop ist ein beweglicher Entwicklungsstand. Die Korrektur ist keine Zusage für ungeprüfte künftige Core-Änderungen. Kein erneuter vollständiger Browser- oder E-Mail-Zustelltest; die vorhandenen Modulgrenzen bleiben bestehen.

[Update-Anleitung](UPDATE-1.0.36.md) · [Prüfbericht](docs/ai/BENACHRICHTIGUNGEN-DEVELOP-2026-10-26.txt).
