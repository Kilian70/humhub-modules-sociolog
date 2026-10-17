# Update auf Logbuch 1.0.34

Das Installationspaket heißt **sociolog-1.0.34.zip** und enthält den Ordner `sociolog`. Eine GitHub-Veröffentlichung aktualisiert deine HumHub-Installation nicht automatisch.

## Von Version 1.0.33

1. Datenbank, Dateien und bisherigen Modulordner sichern; lokale Anpassungen vergleichen. Das Update zuerst auf einer getrennten Testkopie prüfen.
2. Den bisherigen Sociolog-Modulordner durch den Inhalt des ZIP-Ordners `sociolog` ersetzen, gewöhnlich unter `protected/modules/sociolog`. Kein zusätzliches `sociolog/sociolog` anlegen. Das Modul nicht deinstallieren.
3. Die normale Modulaktualisierung durchführen und den HumHub-Cache über die Administration leeren. Die Modulversion muss anschließend **1.0.34** anzeigen. Gegenüber 1.0.33 ist keine neue Datenbankmigration erforderlich.
4. Bei einem bestehenden Eintrag „Überprüfung dokumentieren“ öffnen. Das neue Begründungsfeld muss leer beginnen und verpflichtend sein. Speichern muss einen neuen Verlaufseintrag ergänzen; ursprüngliche Begründung und vorhandene Protokolle bleiben erhalten.
5. Bei aktivierter eingeschränkter Pflege einen fälligen, gesperrten Eintrag als zuständige Person prüfen: neues zukünftiges Datum, neue Begründung und optionaler zusätzlicher Protokolllink erlaubt; andere Eintragsangaben bleiben gesperrt.
6. Die Pflegeoption ausschalten und als Person mit regulären Bearbeitungsrechten erneut prüfen: Das Formular mit neuer Pflichtbegründung steht weiterhin bereit. Als unzuständige Person den direkten Formularzugriff prüfen; er muss abgewiesen werden.

## Von älteren Versionen

Die bereits in 1.0.33 enthaltenen Migrationen und der geschützte historische Updatepfad gelten weiter. Zuerst [UPDATE-1.0.33.md](UPDATE-1.0.33.md) lesen. Keine alten Migrationen einzeln ausführen oder ihren Verlauf manuell ändern. Nach vollständiger Sicherung den dort beschriebenen Updater verwenden, falls HumHub darauf verweist.

## Prüfstand

HumHub 1.18.6 und reguläre 1.19.0, PHP 8.3.14, MySQL 8.0.40; zusätzlich MAMP-Web-PHP 8.3.14. Tabellenpräfixe bleiben nicht unterstützt. Die vorhandenen Einschränkungen bei privaten Lesecontainern, Fremdsuche, Streams und Kalendererinnerungen werden durch dieses Update nicht aufgehoben.

[Änderungen in 1.0.34](AENDERUNGEN-1.0.34.md) · [Prüfbericht](docs/ai/UEBERPRUEFUNG-2026-10-17.txt).
