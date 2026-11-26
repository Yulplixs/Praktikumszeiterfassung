# Praktikumszeiterfassung – Projektstand

Arbeitsdokument. Wird nach jedem Schritt aktualisiert. Den technischen Aufbau beschreibt [ARCHITEKTUR.md](ARCHITEKTUR.md).

## 1. Ziel

Erfassung der Praktikumsbetreuung je Klasse, Lehrkraft, Schülerzahl und Halbjahr. Die Betreuungsstunden werden nach
Spezifikation Kapitel 4.2 berechnet und je Lehrkraft summiert (ersetzt die bisherige Excel-Tabelle).

## 2. Berechnung (Spezifikation 4.2)

1. Faktor und Praktikumswochen (HJ1 bzw. HJ2) der Klasse aus `class_rules` nachschlagen.
2. Stunden je Woche = Faktor × Schüleranzahl
3. Stunden je Halbjahr = RUNDEN(Stunden je Woche × Wochen / 40; 3)
4. Summe je Lehrkraft für HJ1, HJ2 und Schuljahr, dazu die Gesamtsumme als Kontrollwert.

Rundung: einheitlich 3 Nachkommastellen (`InternshipHoursCalculator::ROUNDING_PRECISION`).

## 3. Aufbau

| Pfad | Inhalt |
|---|---|
| `index.html` | Weiterleitung auf `src/html/index.html` |
| `src/html/` | Seiten: `index.html` (Betreuungszeiten), `classes.html`, `Teacher.html` |
| `src/css/`, `src/img/` | Styles und Logo |
| `src/js/` | `gemeinsam.js` (Hilfsfunktionen aller Seiten), `zeitbuchungen.js`, `klassen.js`, `lehrkraefte.js` (je Seite, laden per `fetch` aus der API) |
| `src/api/` | JSON-API in reinem PHP: `bootstrap.php` (Autoloader, PDO, Fehler), `stammdaten.php`, `eintraege.php`, `klassen.php`, `lehrkraefte.php`, `export.php`, `import.php` |
| `src/model/`, `src/repository/`, `src/service/` | Fachlogik: `ClassRule`, Repositories (PDO, einziger Ort mit SQL), `InternshipHoursCalculator`, Excel-Import/-Export |
| `src/database/praktikumsbetreuung.sql` | MariaDB-Dump (Schema + Stammdaten + Bestandseinträge) |
| `src/database/class_rules_4_1.sql` | Klassenwerte aus Spezifikation 4.1 nachtragen |
| `src/database/nachtrag_excel_2026_11.sql` | 6 fehlende Lehrkraft-Kürzel und ihre 17 Einträge aus der Excel-Vorlage nachtragen, Wochen-Snapshots korrigieren |

### API

| Aufruf | Zweck |
|---|---|
| `GET api/stammdaten.php` | Schuljahre, Klassen, Lehrkräfte, Wochen je Klasse und Schuljahr (`klassenwerte`) |
| `GET api/klassen.php?school_year_id=1` | Klassen mit Faktor, Wochen (gesamt, HJ1, HJ2), Anzahl Einträge und Stunden je Halbjahr |
| `PUT api/klassen.php?class_id=3&school_year_id=1` (JSON: `supervision_factor`, `weeks_total`, `weeks_half_year_1`, `weeks_half_year_2`, je 0 bis 9999) | Klassenwerte setzen (legt fehlende `class_rules`-Zeile an), Zeile in `audit_log` |
| `GET api/lehrkraefte.php?school_year_id=1` | Lehrkräfte mit Anzahl Einträge und Stunden je Halbjahr |
| `POST api/lehrkraefte.php` / `PUT ...?id=5` (JSON: `code` Pflicht, `title`, `firstname`, `surname`) | Lehrkraft anlegen bzw. ändern. Kürzel wird groß geschrieben und muss eindeutig sein |
| `DELETE api/lehrkraefte.php?id=5` | Lehrkraft löschen, nur ohne Einträge (sonst 409) |
| `GET api/eintraege.php?school_year_id=1` | Einträge eines Schuljahres und Summen je Lehrkraft |
| `POST api/eintraege.php` (JSON: `school_year_id`, `half_year`, `class_code`, `teacher_id`, `student_count`) | Eintrag berechnen und speichern |
| `PUT api/eintraege.php?id=5` (JSON wie POST, ohne `school_year_id`) | Eintrag mit aktuellen Klassenwerten neu berechnen und überschreiben, Zeile in `audit_log` |
| `DELETE api/eintraege.php?id=5` | Eintrag endgültig löschen, Zeile in `audit_log` |
| `GET api/export.php?school_year_id=1[&teacher_id=5]` | XLSX-Download (Blätter: Praktikumsbetreuung I, II, Zusammenfassung je Lehrkraft). Ohne `teacher_id` Gesamtexport, mit `teacher_id` nur diese Lehrkraft (Dateiname mit Kürzel, Umlaute als AE/OE/UE) |
| `POST api/import.php` (multipart: `datei` = .xlsx im Exportformat, max. 5 MB; `school_year_id`) | Ersetzt alle Einträge des Schuljahres durch die Datei. 422 mit `details[]` (alle fehlerhaften Zeilen), wenn die Datei Fehler hat; dann wird nichts geändert. Jeder Versuch steht in `import_batches` (`imported` / `rejected` / `failed`) |

Fehler kommen als `{"fehler": "..."}` zurück: 404 bei unbekanntem Eintrag oder Schuljahr, 409 beim Löschen einer
Lehrkraft mit Einträgen, 422 bei ungültiger Eingabe (auch: Klasse ohne Praktikumswochen im gewählten Halbjahr),
500 bei Serverfehler.

## 4. Lokale Einrichtung

1. XAMPP (PHP ≥ 8.2, MariaDB) installieren und Apache und MySQL starten.
2. Datenbank `praktikumsbetreuung` anlegen, dann `src/database/praktikumsbetreuung.sql` und anschließend
   `src/database/class_rules_4_1.sql` und `src/database/nachtrag_excel_2026_11.sql` importieren.
3. Im Repo-Root `composer install` ausführen. Das installiert PhpSpreadsheet nach `vendor/` und braucht die
   PHP-Extensions `gd` und `zip`. Lokal: `C:\xampp\php\php.exe C:\xampp\php\composer.phar install`.
   In der XAMPP-`php.ini` sind dafür `extension=gd` und `extension=zip` aktiviert (zip ist ab PHP 8.2 nicht mehr eingebaut).
4. Projekt im Webroot bereitstellen, z. B. `C:\xampp\htdocs\praktikum` als Verknüpfung auf das Repo.
5. Abweichende DB-Zugangsdaten in `src/config.php` eintragen (steht nicht im Git):
   ```php
   <?php return ['dsn' => 'mysql:host=127.0.0.1;dbname=praktikumsbetreuung;charset=utf8mb4', 'user' => 'root', 'password' => ''];
   ```

Schnellstart ohne Apache (vom Repo-Root aus):
```
C:\xampp\php\php.exe -c C:\xampp\php\php.ini -S 127.0.0.1:8080 -t .
```
Danach `http://127.0.0.1:8080/` öffnen.

Getestet am 2026-11-19 mit XAMPP 8.1.6 (MariaDB 10.4.24): Stammdaten, Einträge lesen, Summen je Lehrkraft
(Kontrollwert 74,595 h bei 172 Bestandseinträgen), Eintrag anlegen (P1, HJ1, 10 Schüler → 1,956 h) und die
Fehlerfälle (Halbjahr, Klasse, Lehrkraft, JSON).

Getestet am 2026-11-26 mit PHP 8.2.36 (lokal in `C:\xampp\php`, MariaDB 10.4.24): Lint aller Dateien, `composer check-platform-reqs`,
alle API-Aufrufe inkl. Fehlerfälle, Export (gesamt und je Lehrkraft) und Import der eigenen Exportdatei. Keine Warnungen
oder Deprecations bei `error_reporting = -1`.

## 5. Entscheidungen

| Datum | Entscheidung |
|---|---|
| 2026-11-19 | Arbeit auf Basis des Branches `dev`, lokaler Branch `feature/db-anbindung` |
| 2026-11-19 | Backend: reines PHP + PDO (wie der vorhandene Code), kein Laravel |
| 2026-11-19 | Code aus `.idea/src/` (PhpStorm-Ordner) nach `src/` verschoben, `.idea/` ignoriert |
| 2026-11-19 | Das DB-Modell gilt (Halbjahr statt Datum). Datum und Zeitraumfilter sind aus dem Frontend entfernt |
| 2026-11-19 | Klassenwerte aus 4.1 gelten für die Gruppe und alle Einzelklassen `<Gruppe>.<x>` |
| 2026-11-19 | Anmeldung übernimmt der Admin, sie wird im Projekt nicht umgesetzt. Bis dahin ist User 1 der Bearbeiter |
| 2026-11-19 | Beim Bearbeiten wird mit den aktuellen Klassenwerten neu berechnet (neuer Snapshot, `updated_by`/`updated_at`) |
| 2026-11-19 | Löschen ist endgültig, mit Bestätigungsdialog. Änderungen und Löschungen werden in `audit_log` protokolliert |
| 2026-11-19 | Export als XLSX über den vorhandenen `ExcelExportService`. PhpSpreadsheet `^3.10` per Composer, `vendor/` ist nicht im Git |
| 2026-11-19 | Tabelle mit 25 Einträgen pro Seite. Ein Filterwechsel springt auf Seite 1 |
| 2026-11-19 | Seiten Klassen und Lehrkräfte werden später behandelt. Nur die Navigation und der Logo-Pfad sind repariert (umgesetzt am 2026-11-26) |
| 2026-11-19 | Der Export folgt dem Lehrkraft-Filter: „Alle Lehrkräfte“ = Gesamtexport, sonst eine Datei nur für diese Lehrkraft |
| 2026-11-19 | Lehrkraft-Dropdowns (Filter und Formular) mit integrierter Suche (Combobox): hineinklicken und tippen, Pfeiltasten, Enter, Esc. Sucht in Kürzel und Name, ohne Unterschied bei Groß-/Kleinschreibung und Umlauten. Das `<select>` bleibt versteckt als Wertspeicher |
| 2026-11-19 | Import nur im eigenen Exportformat. Er ersetzt nach Bestätigung alle Einträge des Schuljahres (eine Transaktion, `audit_log`-Eintrag `replace_by_import`) |
| 2026-11-19 | Import behält alte Werte: Faktor (B) und Wochen (C) aus der Datei bleiben Snapshot. Unveränderte Zeilen übernehmen die exakt gespeicherten Stunden des bisherigen Eintrags (Altdaten wurden teils mit ungerundeten Wochen gerechnet). Geänderte Zeilen werden mit B/C neu berechnet, neue Zeilen mit leerem B/C nach den aktuellen Klassenwerten. Stunden (F, G) aus der Datei werden nie übernommen. Bearbeiten in der Oberfläche rechnet weiterhin mit aktuellen Klassenwerten |
| 2026-11-19 | Import prüft erst die ganze Datei und meldet alle fehlerhaften Zeilen. Gespeichert wird nur eine fehlerfreie Datei. Jeder Versuch wird in `import_batches` protokolliert |
| 2026-11-26 | Klassen ohne Praktikumswochen im Halbjahr sind im Formular ausgeblendet. API und Import lehnen solche Einträge mit 422 ab (`InternshipHoursCalculator::calculateEntry`). Die 9 Klassen ohne Werte aus 4.1 bleiben bei 0: Die Excel-Vorlage bucht nur auf die Sammelklassen `FPT`, `SOT`, `11GS` |
| 2026-11-26 | Sechs Kürzel aus der Excel-Vorlage fehlten in der Lehrkräfteliste des Admins (`FRH`, `SRK`, `GÖB`, `KAP`, `ASL`, `HEK`). Sie sind nur mit Kürzel angelegt, ihre 17 Einträge nachgetragen. HJ1 ergibt damit 40,044 h wie in der Excel |
| 2026-11-26 | `weeks_snapshot` 7,82 / 2,6 / 10,39 auf 7,8225 / 2,5974 / 10,389 korrigiert. Die Stunden waren schon mit den exakten Werten berechnet und ändern sich nicht |
| 2026-11-26 | Seite Klassen: Liste je Schuljahr mit Faktor, Wochen, Einträgen und Stunden; Faktor und Wochen bearbeitbar. Seite Lehrkräfte: anlegen, bearbeiten, löschen nur ohne Einträge. Klassenleitung, Schüleranzahl je Klasse und E-Mail entfallen (nicht in der DB) |
| 2026-11-26 | `audit_log` speichert weiter nur Aktion und ID. Alte Werte werden nicht benötigt |
| 2026-11-26 | Duplikate aus dem Merge von `main` gelöscht: `src/models/` (ungenutztes Laravel-Model), `database/` (gleicher Dump wie `src/database/`), `src/index.html` (altes Mockup), `public/` (gleiches Logo wie `src/img/`) |
| 2026-11-26 | SQL nur noch in Repositories. Neu: `ClassRepository`, `TeacherRepository`, `SchoolYearRepository`, `AuditLogRepository` |
| 2026-11-26 | PHP-Dateien enden ohne `?>` (PSR-12), damit keine Leerzeichen nach dem Tag JSON oder XLSX beschädigen |
| 2026-11-26 | Mindestversion PHP 8.2 (Anforderung): `composer.json` verlangt `php >=8.2`, `config.platform.php` = 8.2.0 hält `composer.lock` auf 8.2-kompatiblen Paketen. Lokal ist XAMPP auf PHP 8.2.36 umgestellt, das alte PHP 8.1.6 liegt in `C:\xampp\php81` |

## 6. Offene Punkte

- **Namen der nachgetragenen Lehrkräfte** `FRH`, `SRK`, `GÖB`, `KAP`, `ASL`, `HEK` fehlen (Seite Lehrkräfte). Prüfen, ob
  `FRH` dieselbe Person wie `BRI` (Brinkmann-Freiherr) ist; dann Einträge umhängen und `FRH` löschen.
- **Excel-Vorlage HJ2:** Die Summenzelle zeigt 36,0102 h, die Zeilen ergeben 39,7326 h. Die Differenz ist genau der
  11GS-Block (3,7224 h): Die Summenformel schließt die 11GS-Zeilen nicht ein. Die DB hat 39,741 h (Rundung auf
  3 statt 4 Stellen je Zeile). Außerdem stehen in HJ2 unter `BEGS2` dieselben Lehrkräfte wie in HJ1 unter `BEST2`.
  Beides mit dem Admin klären.
- **Lokale Test-Einträge mit 0 Stunden:** IDs 396 und 397 (11GS/HJ1) in der lokalen DB, nicht im Dump. Solange sie
  existieren, lehnt der Import die eigene Exportdatei ab (0-Wochen-Sperre). Löschen.
- **Anmeldung:** Konzept mit Aufwandsschätzung in [KONZEPT-ANMELDUNG.md](KONZEPT-ANMELDUNG.md). Bis zur Umsetzung sind
  `created_by`, `updated_by`, `audit_log.user_id` und `import_batches.imported_by` fest User 1 (`system-import`).
- **Webroot:** Das Repo-Root ist Webroot. Damit sind die SQL-Dumps (`src/database/`), `vendor/` und `.git/` per HTTP
  abrufbar. Vor dem Produktivbetrieb sperren (DocumentRoot einschränken oder `.htaccess`).
- **Import-Grenzen:** max. 5 MB (`MAX_GROESSE` in `api/import.php`), dazu die PHP-Limits `upload_max_filesize` und
  `post_max_size` (XAMPP: 40M).
