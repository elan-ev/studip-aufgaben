# Das neue Aufgabentool für Stud.IP!

Ein einfaches Plugin für zeitgesteuerte Aufgaben mit Text- und Dateiabgabe in Stud.IP.

## Was kann es?

* Zeitgesteuert Aufgaben für Teilnehmende einer Veranstaltung stellen.
* Teilnehmende können Antworttexte eingeben und bequem Dateien einreichen.
* Es kann Feedback ebenfalls in Textform oder mittels Dateien erfolgen.
* Der spezielle Uploader erlaubt das Hochladen von beliebig vielen Dateien auf einen Knopfdruck!

Weitere Infos auf der HP unter http://tgloeggl.github.io/studip-aufgaben/

# Changelog
## 1.5.7
* Kompatibilität zu Stud.IP 4.0 & PHP 7
* Anbindung an den neuen Dateibereich
* Herunterladen aller abgegebenen Texte einer Aufgabe als PDF

### Updatehinweise

In Stud.IP 4.0 hat sich die Dateiablage grundlegend geändert. Sollten Sie bereits mit dem Aufgabenplugin in einer Version vor Stud.IP 4.0 (3.5 oder abwärts) gearbeitet haben, sollten Sie die Tabelle "dokumente", die bei der Migration nach Stud.IP 4.0 gelöscht, wieder mit den Originaldaten einspielen. Das Plugin wird dann die Dateien sauber in die neue Dateiablage migrieren.

Sollte es Ihnen nicht möglich sein diese Tabelle einzuspielen, so werden die Dateien trotzdem migriert und stehen bei den Aufgaben zur Verfügung, allerdings mit einigen Abstrichen:
* Die Original Dateinamen sind nicht mehr verfügbar, die Dateien sind stattdessen durchnummeriert nach dem Schema "dokument_x"
* Die Art der Datei (PDF, DOC, etc.) ist ebenfalls nicht hinterlegt. Die Dateien müssen dann nach dem Herunterladen entsprechend umbenannt werden, um sie verwenden zu können.

## 1.3.4
* Serverseitig fehlgeschlagener Dateiupload führte zu Inkonsistenzen in der Datenbank

## 1.3.3
* Es konnte nur eine einzige Berechtigung pro Aufgabe gespeichert werden, das ist nun behoben

# Kontakt

Bei Fragen wenden Sie sich gerne an gloeggler@elan-ev.de
