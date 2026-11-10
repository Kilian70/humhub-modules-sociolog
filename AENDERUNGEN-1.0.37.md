# Logbuch 1.0.37 – Infotafel oder Kacheln

Release vom 10.11.2026 auf Basis von 1.0.36.

## Informationsseite frei gestalten

Unter **Moduleinstellungen → Informationsseite → Darstellung der Informationsseite** stehen zwei Ansichten zur Auswahl:

- **Kacheln:** die bisherige Informationsseite mit den vorhandenen Textfeldern.
- **Infotafel:** ein eigenes Feld für formatierten Text, Bilder und Links. Bilder lassen sich über das Einfügen-Menü des Editors hochladen.

Kacheln bleiben die Voreinstellung. Beim Wechsel werden die Inhalte der anderen Ansicht und hochgeladene Bilder erhalten. Einleitung, Seitentitel und optionaler Dokumentlink stehen in beiden Ansichten zur Verfügung. Bestehende Logbucheinträge werden nicht verändert.

Bilder werden über HumHubs Dateiverwaltung dauerhaft an die Infotafel gebunden. Aktive angemeldete Mitglieder können sie bei aktivierter Informationsseite lesen. Bearbeiten und Löschen ist der Administration vorbehalten; Gäste haben keinen Zugriff. Bei deaktivierter Seite bleibt der Entwurf für die Administration erhalten.

## Weitere enthaltene Korrektur

Der Leserechts-Test verwendet in HumHub Develop den neuen Kommentar-Controller und unter Stable weiterhin die bisherige Schnittstelle. Die erwartete Zugriffsverweigerung wird in beiden Fällen geprüft. Die Benachrichtigungsanbindung aus 1.0.36 bleibt enthalten.

## Prüfung und Update

- MAMP: HumHub 1.18.7 und 1.19.3, PHP 8.3.30, MySQL 8.0.44.
- Je 115 Tests / 6.421 Assertions ohne Fehler; je ein vorhandener Test wird versionsbedingt ausgelassen.
- Je ein echter Browsertest / 19 Assertions für Bild-Upload, Speichern und den Erhalt beider Ansichten beim Umschalten.
- Die GitHub-Stable-/Develop-Ergebnisse des Release-Commits stehen auf der Veröffentlichungsseite. Künftige Develop-Änderungen müssen erneut geprüft werden.

**Keine neue Datenbankmigration gegenüber 1.0.36.** Vor dem Update Datenbank und Dateien sichern. Modul vollständig ersetzen und nicht deinstallieren. [Update-Anleitung](UPDATE-1.0.37.md) · [Prüfbericht](docs/ai/INFOTAFEL-2026-11-10.txt).
