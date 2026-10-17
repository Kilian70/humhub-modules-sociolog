# Update auf Logbuch 1.0.35

Das Installationspaket **sociolog-1.0.35.zip** enthält den Modulordner `sociolog`. Die Veröffentlichung auf GitHub aktualisiert deine HumHub-Installation nicht automatisch.

## Von Version 1.0.34

1. Datenbank, Dateien und bisherigen Modulordner sichern; lokale Anpassungen vergleichen. Zuerst auf einer getrennten Testkopie aktualisieren.
2. Den bisherigen Modulordner durch den Inhalt des ZIP-Ordners `sociolog` ersetzen, gewöhnlich unter `protected/modules/sociolog`. Kein zusätzliches `sociolog/sociolog` anlegen und das Modul nicht deinstallieren.
3. **Die normale HumHub-Modulaktualisierung vollständig ausführen.** Die neue Migration `m261017_120000_add_review_replacement_url` muss angewendet werden. Sie ergänzt eine optionale Linkspalte; vorhandene Überprüfungen und Texte bleiben erhalten. Erst nach erfolgreicher Migration die neue Funktion benutzen.
4. Den HumHub-Cache über die Administration leeren. Die angezeigte Modulversion muss **1.0.35** sein.
5. Pflegeoption einschalten und einen fälligen, gesperrten Testbeschluss als zuständige Person öffnen. Drei Ergebnisse müssen auswählbar sein. Bei „bleibt bestehen“ ein zukünftiges Datum verlangen; bei „Ersetzt“ den Link zum neuen Beschluss; bei jedem Ergebnis eine neue Begründung.
6. Nach Aufhebung/Ersatz müssen Status, neuer Verlaufseintrag und gegebenenfalls Nachfolgelink erscheinen. Kein nächstes Überprüfungsdatum; ursprüngliche Begründung und bisherige Protokolle unverändert. Die Statusautomatik darf den Beschluss nicht reaktivieren.
7. Unzuständige Personen dürfen auch über die direkte Überprüfungsadresse nicht speichern. Bei ausgeschalteter Pflegeoption bleiben volle Bearbeitungsrechte erforderlich.

## Ältere Versionen und Neuinstallation

Die bisherigen Migrationen und der geschützte historische Updatepfad gelten weiter. Zuerst [UPDATE-1.0.33.md](UPDATE-1.0.33.md) lesen; keine historischen Migrationen einzeln ausführen oder deren Verlauf manuell ändern. Falls HumHub den geschützten Updater verlangt, dessen dokumentierte Schritte nach vollständiger Sicherung ausführen.

Für die vollständige historische Neuinstallationsfolge besteht eine ältere Einschränkung: ein Vergleich mit `0000-00-00` scheitert bei `NO_ZERO_DATE`. Die geprüfte Neuinstallation verwendet eine leere Testdatenbank und eine SQL-Sitzung ohne diesen Modus; keine globale Datenbankkonfiguration ungeprüft ändern. Ein Update von 1.0.34 führt diese alte Migration nicht erneut aus. Die neue Migration wurde separat mit dem Standard-SQL-Modus geprüft. Tabellenpräfixe bleiben nicht unterstützt.

## Rücknahme und Prüfstand

Vor einer Rücknahme erneut sichern. Nur älteren Modulcode einzuspielen entfernt die neue Spalte nicht. Eine Rücknahme der neuen Migration entfernt die gespeicherten Nachfolgelinks; ursprüngliche Überprüfungen werden dadurch nicht gelöscht. Für eine vollständige Wiederherstellung die gesicherte Datenbank und den passenden Modulstand gemeinsam wiederherstellen.

Geprüft: HumHub 1.18.6 und reguläre 1.19.0, PHP 8.3.14, MySQL 8.0.40; Syntax zusätzlich PHP 8.5.2. Bestehende Einschränkungen bei Lesecontainern, Fremdsuche, Streams und Kalendererinnerungen bleiben bestehen.

[Änderungen in 1.0.35](AENDERUNGEN-1.0.35.md) · [Prüfbericht](docs/ai/UEBERPRUEFUNG-ERGEBNISSE-2026-10-17.txt).
