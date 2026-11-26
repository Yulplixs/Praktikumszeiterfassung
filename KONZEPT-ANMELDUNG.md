# Konzept: Anmeldung und Benutzer

Stand 2026-11-26. Noch nicht umgesetzt. Annahmen zu IServ sind mit dem Schul-Admin zu prüfen.

## Ausgangslage im Code

- Tabelle `users` existiert (`iserv_uuid`, `email`, `display_name`, `role`, `status`, `last_login_at`). Einziger Eintrag:
  User 1 `system-import`.
- `user_class_assignments` existiert, wird nicht genutzt. `teachers` ist nicht mit `users` verknüpft.
- `BENUTZER_ID = 1` steht fest in `src/api/eintraege.php`, `import.php`, `klassen.php`, `lehrkraefte.php`.
- Repositories und `ExcelImportService` nehmen die User-ID schon als Parameter. Sie müssen nicht geändert werden.
- Es gibt keine Session, keine Login-Seite und keine Zugriffssperre.

## 1. Anmeldeverfahren

| Option | Pro | Contra |
|---|---|---|
| **A: IServ-SSO (OAuth2/OpenID Connect)** – empfohlen | Kein eigenes Passwort. Ein deaktiviertes IServ-Konto sperrt automatisch. `iserv_uuid` ist vorgesehen | Admin muss den Client in IServ einmal anlegen. Braucht HTTPS und eine feste Redirect-URL |
| B: Eigene Passwörter | Unabhängig von IServ | Ein weiteres Passwort für ca. 110 Lehrkräfte. Passwortspeicherung, Reset und Brute-Force-Schutz liegen bei uns (+8 bis 10 h) |
| C: Webserver-Auth (`mod_auth_openidc`) | Fast kein PHP-Code | Braucht Root-Zugriff auf Apache. Lokal in XAMPP kaum testbar |

Beim Admin zu klären: OIDC-Modul in IServ aktiv, Endpunkte und Scopes, ob `sub` stabil ist, ob der IServ-Benutzername dem
Lehrerkürzel entspricht, ob es einen Gruppen-/Rollen-Claim gibt.

## 2. Rollen und Rechte

| Aktion | admin (Verwaltung) | abteilungsleitung (nur Vollvariante) | lehrkraft |
|---|---|---|---|
| `GET stammdaten.php`, `klassen.php`, `lehrkraefte.php` | ja | ja | ja |
| `GET eintraege.php` | alle | Klassen aus `user_class_assignments` | nur eigene |
| `POST/PUT/DELETE eintraege.php` | ja | eigene Klassen | nein (MVP) |
| `PUT klassen.php`, `POST/PUT/DELETE lehrkraefte.php` | ja | nein | nein |
| `GET export.php` | gesamt oder je Lehrkraft | eigene Klassen | nur eigene |
| `POST import.php` | ja | nein | nein |

Neuer Login: Rolle `lehrkraft`, `status = 'pending'`, bis ein Admin `teacher_id` zuordnet.
Die Matrix muss die Schulleitung fachlich bestätigen.

## 3. Änderungen

- **DB:** Migration `users.teacher_id` (FK auf `teachers`, eindeutig) und `users.username`. Dump nachziehen.
- **`src/api/bootstrap.php`:** Session (`httponly`, `secure`, `samesite=Lax`, Timeout), `benutzer()` (401),
  `erlaube(...$rollen)` (403), CSRF-Prüfung bei POST/PUT/DELETE über Header `X-CSRF-Token`.
- **Neue Endpunkte:** `login.php` (state + PKCE, Weiterleitung zu IServ), `callback.php` (Token, `userinfo`, Upsert in
  `users`, `session_regenerate_id`), `logout.php`, `ich.php` (Name, Rolle, CSRF-Token). Client-Daten in `src/config.php`.
- **Bestehende API:** `BENUTZER_ID` durch `benutzer()['id']` ersetzen, Rechte nach Matrix prüfen, für `lehrkraft`
  Lesen und Export auf die eigene `teacher_id` begrenzen.
- **Frontend:** `api()` in `src/js/gemeinsam.js` leitet bei 401 zum Login und sendet den CSRF-Header (auch der Import-`fetch`).
  Benutzername statt „Schulverwaltung“, Logout, Aktionen je Rolle ausblenden (nur Komfort, Schutz liegt im Server).
- **Benutzerverwaltung:** MVP per phpMyAdmin. Vollvariante: Seite `benutzer.html` + `api/benutzer.php`.
- **Sicherheit:** HTTPS Pflicht. Webroot sperren: SQL-Dumps, `vendor/` und `.git/` sind heute per HTTP abrufbar.
  Für lokale Entwicklung ein Schalter in `src/config.php`, der einen festen Benutzer simuliert (nie in Produktion).

## 4. Aufwand (Stunden, eine erfahrene Person; Schülerteam Faktor 1,5 bis 2)

| Paket | MVP | Voll |
|---|---|---|
| DB-Migration und Dump | 1 | 2 |
| `bootstrap.php`: Session, Rechte, CSRF | 3 | 3 |
| OIDC-Endpunkte | 6 | 7 |
| Rechte in den API-Dateien | 3 | 6 |
| Frontend | 4 | 5 |
| Benutzerverwaltung | 0 | 10 |
| IServ-Client mit Admin einrichten, HTTPS, Test | 3 | 3 |
| Webroot absichern | 1 | 1 |
| Test und Doku | 3 | 5 |
| **Summe** | **24 h** | **42 h** |

- **MVP:** IServ-SSO, Rollen `admin` und `lehrkraft`. Lehrkräfte lesen und exportieren nur eigene Einträge.
  Zuordnung zur Lehrkraft per SQL. Echte User-IDs in allen `*_by`-Spalten und im `audit_log`.
- **Vollvariante:** zusätzlich Abteilungsleitung über `user_class_assignments`, Benutzerverwaltung in der Oberfläche,
  optional Rolle/Zuordnung automatisch aus IServ-Claims.

**Risiken:** IServ-Endpunkte und Claims unbekannt. Kein SSO-Test in XAMPP möglich. Zuordnung Benutzer ↔ Lehrkraft
manuell, solange `teachers` keine E-Mail hat.
