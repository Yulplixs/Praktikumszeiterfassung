# Praktikumszeiterfassung

Schulprojekt der BBS Einbeck: Erfassung der Praktikumsbetreuung je Klasse, Lehrkraft und Halbjahr. Die Stunden werden
nach Spezifikation 4.2 berechnet. PHP-JSON-API mit MariaDB, Frontend aus statischen Seiten.

- Projektstand, API und Entscheidungen: [PROJEKT.md](PROJEKT.md)
- Schichten und Datenmodell: [ARCHITEKTUR.md](ARCHITEKTUR.md)

## Projektstruktur

| Ordner | Inhalt |
|---|---|
| `index.html` | Einstieg, leitet auf `src/html/index.html` weiter |
| `src/html`, `src/css`, `src/js`, `src/img` | Seiten Zeitbuchungen, Lehrkräfte, Klassen |
| `src/api` | JSON-API (ein Skript je Ressource) |
| `src/service`, `src/repository`, `src/model` | Fachlogik, Datenbankzugriff (einziger Ort mit SQL), Wertobjekte |
| `src/database` | Datenbank-Dump und Nachtrags-Skripte |

## Lokal starten (XAMPP)

1. XAMPP mit **PHP 8.2 oder neuer** installieren, Apache und MySQL starten.
   Wer schon XAMPP mit PHP 8.1 hat: `C:\xampp\php` nach `C:\xampp\php81` umbenennen, das offizielle
   `php-8.2.x-Win32-vs16-x64.zip` (Thread Safe, von windows.php.net) nach `C:\xampp\php` entpacken, die alte `php.ini`
   hineinkopieren und darin unter `extension=gd` die Zeile `extension=zip` ergänzen (in 8.2 nicht mehr eingebaut).
2. Repo nach `C:\xampp\htdocs\praktikum` klonen (oder dort verknüpfen).
3. In phpMyAdmin eine Datenbank **`praktikumsbetreuung`** anlegen (Kollation `utf8mb4_unicode_ci`), sie links
   auswählen und dann **in dieser Reihenfolge** importieren:
   1. `src/database/praktikumsbetreuung.sql`
   2. `src/database/class_rules_4_1.sql`
   3. `src/database/nachtrag_excel_2026_11.sql`
4. Nur wenn MySQL nicht `root` ohne Passwort nutzt: `src/config.php` anlegen (steht nicht im Git):
   ```php
   <?php return ['dsn' => 'mysql:host=127.0.0.1;dbname=praktikumsbetreuung;charset=utf8mb4', 'user' => 'root', 'password' => 'geheim'];
   ```
5. Für Excel-Export und -Import im Repo-Root `composer install` ausführen (braucht die PHP-Extensions `gd` und `zip`).
6. Im Browser **`http://localhost/praktikum/`** öffnen.

## Seiten zeigen keine Daten?

- **Adresse prüfen:** immer über `http://localhost/...` öffnen. Nicht per Doppelklick (`file://`) und nicht über die
  PhpStorm-Vorschau (Port 63342), sonst läuft kein PHP.
- **API direkt aufrufen:** `http://localhost/praktikum/src/api/stammdaten.php`. Dort steht JSON oder die Fehlermeldung.
  Mehr steht im Browser unter F12 → Netzwerk und in `C:\xampp\apache\logs\error.log`.
- **Datenbankname** muss genau `praktikumsbetreuung` sein. Der Dump legt die Datenbank nicht selbst an.
- **PHP-Version** unter 8.2: `composer install` bricht ab (`composer.json` verlangt `php >=8.2`).
- **Klassenauswahl leer oder alle Stunden 0:** Schritt 3.2 und 3.3 fehlen. Ohne Praktikumswochen ist keine Klasse
  wählbar.
- **Export/Import mit Fehler 500:** `composer install` fehlt (Ordner `vendor/`).
