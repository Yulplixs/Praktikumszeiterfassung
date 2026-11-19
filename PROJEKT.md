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
| `src/js/zeitbuchungen.js` | Frontend-Logik der Seite Betreuungszeiten (lädt per `fetch` aus der API) |
| `src/api/` | JSON-API in reinem PHP: `bootstrap.php` (Autoloader, PDO, Fehler), `stammdaten.php`, `eintraege.php` |
| `src/model/`, `src/repository/`, `src/service/` | Fachlogik: `ClassRule`, Repositories (PDO), `InternshipHoursCalculator`, Excel-Import/-Export |
| `src/database/praktikumsbetreuung.sql` | MariaDB-Dump (Schema + Stammdaten + Bestandseinträge) |
| `src/database/class_rules_4_1.sql` | Klassenwerte aus Spezifikation 4.1 nachtragen |

### API

| Aufruf | Zweck |
|---|---|
| `GET api/stammdaten.php` | Schuljahre, Klassen, Lehrkräfte |
| `GET api/eintraege.php?school_year_id=1` | Einträge eines Schuljahres und Summen je Lehrkraft |
| `POST api/eintraege.php` (JSON: `school_year_id`, `half_year`, `class_code`, `teacher_id`, `student_count`) | Eintrag berechnen und speichern |
| `PUT api/eintraege.php?id=5` (JSON wie POST, ohne `school_year_id`) | Eintrag mit aktuellen Klassenwerten neu berechnen und überschreiben, Zeile in `audit_log` |
| `DELETE api/eintraege.php?id=5` | Eintrag endgültig löschen, Zeile in `audit_log` |
| `GET api/export.php?school_year_id=1[&teacher_id=5]` | XLSX-Download (Blätter: Praktikumsbetreuung I, II, Zusammenfassung je Lehrkraft). Ohne `teacher_id` Gesamtexport, mit `teacher_id` nur diese Lehrkraft (Dateiname mit Kürzel, Umlaute als AE/OE/UE) |

| `POST api/import.php` (multipart: `datei` = .xlsx im Exportformat, max. 5 MB; `school_year_id`) | Ersetzt alle Einträge des Schuljahres durch die Datei. 422 mit `details[]` (alle fehlerhaften Zeilen), wenn die Datei Fehler hat; dann wird nichts geändert. Jeder Versuch steht in `import_batches` (`imported` / `rejected` / `failed`) |

Fehler kommen als `{"fehler": "..."}` zurück: 404 bei unbekanntem Eintrag oder Schuljahr, 422 bei ungültiger Eingabe,
500 bei Serverfehler.

## 4. Lokale Einrichtung

1. XAMPP (PHP ≥ 8.1, MariaDB) installieren und Apache und MySQL starten.
2. Datenbank `praktikumsbetreuung` anlegen, dann `src/database/praktikumsbetreuung.sql` und anschließend
   `src/database/class_rules_4_1.sql` importieren.
3. Im Repo-Root `composer install` ausführen. Das installiert PhpSpreadsheet nach `vendor/` und braucht die
   PHP-Extensions `gd` und `zip`. Lokal: `C:\xampp\php\php.exe C:\xampp\php\composer.phar install`.
   In der XAMPP-`php.ini` ist dafür `extension=gd` aktiviert.
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
| 2026-11-19 | Seiten Klassen und Lehrkräfte werden später behandelt. Nur die Navigation und der Logo-Pfad sind repariert |
| 2026-11-19 | Der Export folgt dem Lehrkraft-Filter: „Alle Lehrkräfte“ = Gesamtexport, sonst eine Datei nur für diese Lehrkraft |
| 2026-11-19 | Lehrkraft-Dropdowns (Filter und Formular) mit integrierter Suche (Combobox): hineinklicken und tippen, Pfeiltasten, Enter, Esc. Sucht in Kürzel und Name, ohne Unterschied bei Groß-/Kleinschreibung und Umlauten. Das `<select>` bleibt versteckt als Wertspeicher |
| 2026-11-19 | Import nur im eigenen Exportformat. Er ersetzt nach Bestätigung alle Einträge des Schuljahres (eine Transaktion, `audit_log`-Eintrag `replace_by_import`) |
| 2026-11-19 | Import behält alte Werte: Faktor (B) und Wochen (C) aus der Datei bleiben Snapshot. Unveränderte Zeilen übernehmen die exakt gespeicherten Stunden des bisherigen Eintrags (Altdaten wurden teils mit ungerundeten Wochen gerechnet). Geänderte Zeilen werden mit B/C neu berechnet, neue Zeilen mit leerem B/C nach den aktuellen Klassenwerten. Stunden (F, G) aus der Datei werden nie übernommen. Bearbeiten in der Oberfläche rechnet weiterhin mit aktuellen Klassenwerten |
| 2026-11-19 | Import prüft erst die ganze Datei und meldet alle fehlerhaften Zeilen. Gespeichert wird nur eine fehlerfreie Datei. Jeder Versuch wird in `import_batches` protokolliert |

## 6. Offene Punkte

- **Klassen ohne Werte aus 4.1** (bleiben 0): BETZ, BG11GS, BG12GS, FOG11, FOG12, FP1T, FP3T, SO2T, SO3T. Zuordnung klären
  (z. B. BG11GS → 11GS? FP1T/FP3T → FPT? SO2T/SO3T → SOT?).
- **Bestandsdaten:** Einige Snapshots weichen von 4.1 ab (P1/P2/P3 HJ2: 7,82 statt 7,8225; FPT HJ2: 2,6 statt 2,5974).
  Wurden bewusst nicht geändert.
- **Login:** übernimmt der Admin. Bis dahin sind `created_by`, `updated_by` und `audit_log.user_id` fest User 1
  (`system-import`).
- **Einträge mit 0 Stunden** werden gespeichert, z. B. für eine Klasse ohne Praktikumswochen im gewählten Halbjahr
  (Beispiel: IDs 217, 218 und 220, alle 11GS/HJ1, angelegt am 2026-11-19). Ursache ist wohl die Vorauswahl im Formular:
  11GS und Halbjahr 1 sind voreingestellt, und 11GS hat im HJ1 null Wochen. Klären, ob das gesperrt oder als Warnung gezeigt werden soll.
- **audit_log** speichert nur Aktion und ID, keine alten Werte. Gelöschte Einträge lassen sich daraus nicht wiederherstellen.
- **Seiten Klassen und Lehrkräfte:** noch statisch. Felder passen nicht zur DB (Klassenleitung, Schüleranzahl je Klasse,
  E-Mail, Kürzel max. 3 Zeichen). Stattdessen gehören Faktor und Wochen je Klasse in die Klassenseite.
- **Import-Grenzen:** max. 5 MB (`MAX_GROESSE` in `api/import.php`), dazu die PHP-Limits `upload_max_filesize` und
  `post_max_size` (XAMPP: 40M).
