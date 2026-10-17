# Logbuch 1.0.34 – Überprüfungen mit eigener Begründung

Release vom 17.10.2026, enthält Pull Request #22.

## Jede Überprüfung bekommt eine neue Begründung

„Überprüfung dokumentieren“ öffnet immer ein eigenes Formular mit dem Pflichtfeld **Neue Begründung der Überprüfung** – auch wenn die eingeschränkte Pflege ausgeschaltet ist. Die ursprüngliche Begründung und frühere Überprüfungen bleiben unverändert. Neue Begründungen werden mit verantwortlicher Person, Zeitpunkt, nächstem Datum und optionalem Protokoll im Verlauf gespeichert.

## Eingeschränkte Pflege fälliger Einträge

Ist die Option eingeschaltet, dürfen zuständige Personen bei gesperrten veröffentlichten Einträgen ab dem Überprüfungsdatum ein zukünftiges Datum, eine neue Begründung und einen zusätzlichen Protokolllink erfassen. Andere Eintragsangaben und bestehende Protokolle bleiben erhalten. Eine Ergebnisänderung ist im eingeschränkten Formular auch per manipuliertem Request nicht möglich. Die Aktion steht auch bei ausgeschaltetem Entscheidungsworkflow bereit; bestehende weitergehende Rechte bleiben erhalten.

## Formular korrigiert

Die Browservalidierung prüft das tatsächliche Rich-Text-Eingabefeld und zeigt fehlende Begründungen an. Jede neue Überprüfung öffnet einen eigenen Editor, ohne einen früheren Editor-Entwurf zu übernehmen. Das reine Öffnen speichert nichts.

## Prüfung und Grenzen

- HumHub 1.18.6 und reguläre 1.19.0: je 100 Tests / 6.183 Assertions erfolgreich, PHP 8.3.14 und MySQL 8.0.40.
- Echte MAMP-Browserprüfung beider Versionen mit Web-PHP 8.3.14: Pflichtbegründung, Speichern mit und ohne Pflegeoption, erhaltene Originaltexte/Protokolle und unberechtigter Direktzugriff.
- PHP-Syntax zusätzlich unter 8.4.17 und 8.5.2 sowie Modulmetadaten geprüft.

Der GitHub-Stable-Lauf des Funktionsstands bestand unter PHP 8.2–8.5. Der Develop-Lauf scheiterte vor den Modultests am HTTP-404-Testserver; eine erfolgreiche Develop-Prüfung wird nicht behauptet. Keine erneute Mail-/Kalenderabnahme oder umfassende CSRF-Angriffsprüfung. Die bestehenden Grenzen bei Tabellenpräfixen und eingeschränkten nativen Streams/Suchfunktionen bleiben bestehen.

Keine neue Datenbankmigration gegenüber 1.0.33. [Update-Anleitung](UPDATE-1.0.34.md) · [Detaillierter Prüfbericht](docs/ai/UEBERPRUEFUNG-2026-10-17.txt).
