# Changelog

## 2026-11-26

**Neu**
- Seite Klassen: Faktor und Praktikumswochen je Klasse und Schuljahr anzeigen und bearbeiten, Stunden je Halbjahr
  (`src/api/klassen.php`, `src/js/klassen.js`).
- Seite Lehrkräfte: anlegen, bearbeiten, löschen (nur ohne Einträge), Stunden je Halbjahr
  (`src/api/lehrkraefte.php`, `src/js/lehrkraefte.js`).
- `src/database/nachtrag_excel_2026_11.sql`: 6 fehlende Lehrkraft-Kürzel und ihre 17 Einträge aus der Excel-Vorlage,
  Korrektur der Wochen-Snapshots 7,82 / 2,6 / 10,39.
- `KONZEPT-ANMELDUNG.md`: Konzept und Aufwandsschätzung für die Anmeldung.

**Geändert**
- Klassen ohne Praktikumswochen im Halbjahr sind im Formular nicht wählbar. API und Import lehnen sie mit 422 ab.
- SQL nur noch in Repositories: neu `ClassRepository`, `TeacherRepository`, `SchoolYearRepository`, `AuditLogRepository`.
- Gemeinsame Frontend-Hilfsfunktionen in `src/js/gemeinsam.js`.
- Mindestversion PHP 8.2 (`composer.json`, `config.platform.php`).
- README mit Einrichtung und Fehlersuche.

**Entfernt**
- Duplikate aus dem Merge von `main`: `src/models/` (ungenutztes Laravel-Model), `database/` (gleicher Dump wie
  `src/database/`), `src/index.html` (altes Mockup), `public/` (gleiches Logo wie `src/img/`).

## 2026-11-19

- Frontend und Services an die Datenbank angebunden (JSON-API in reinem PHP): Einträge erfassen, bearbeiten, löschen,
  Summen je Lehrkraft, Excel-Export und -Import.
