<?php

declare(strict_types=1);

// GET    ?school_year_id=1                      -> Lehrkräfte mit Anzahl Einträge und Stunden je Halbjahr
// POST   {code, title, firstname, surname}      -> Lehrkraft anlegen
// PUT    ?id=5 {code, title, firstname, surname} -> Lehrkraft ändern
// DELETE ?id=5                                  -> Lehrkraft löschen, nur ohne Einträge (in allen Schuljahren)

require __DIR__ . '/bootstrap.php';

use App\Repository\TeacherRepository;

// shortcut: Anmeldung übernimmt der Admin, bis dahin ist immer User 1 (system-import) der Bearbeiter.
const BENUTZER_ID = 1;

$lehrkraefte = new TeacherRepository(db());
$methode = $_SERVER['REQUEST_METHOD'];

if ($methode === 'GET') {
    antworte($lehrkraefte->findWithHours((int) ($_GET['school_year_id'] ?? 0)));
}

if (!in_array($methode, ['POST', 'PUT', 'DELETE'], true)) {
    antworte(['fehler' => 'Methode nicht erlaubt.'], 405);
}

$id = 0;
if ($methode !== 'POST') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($lehrkraefte->findCodeById($id) === null) {
        antworte(['fehler' => 'Lehrkraft nicht gefunden.'], 404);
    }
}

if ($methode === 'DELETE') {
    // Mit Einträgen schlägt das DELETE am Fremdschlüssel fehl; vorher prüfen für eine klare Meldung.
    $anzahl = $lehrkraefte->countEntries($id);
    if ($anzahl > 0) {
        antworte(['fehler' => "Die Lehrkraft hat $anzahl Einträge und kann nicht gelöscht werden."], 409);
    }
    $lehrkraefte->delete($id, BENUTZER_ID);
    antworte(['id' => $id]);
}

$eingabe = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($eingabe)) {
    antworte(['fehler' => 'Ungültiger JSON-Body.'], 400);
}

// Längen wie in der Tabelle teachers
$werte = [];
foreach (['code' => 30, 'title' => 20, 'firstname' => 50, 'surname' => 50] as $feld => $max) {
    $werte[$feld] = trim((string) ($eingabe[$feld] ?? ''));
    if (mb_strlen($werte[$feld]) > $max) {
        antworte(['fehler' => "Das Feld $feld darf höchstens $max Zeichen haben."], 422);
    }
}
$werte['code'] = mb_strtoupper($werte['code']);
if ($werte['code'] === '') {
    antworte(['fehler' => 'Das Kürzel ist ein Pflichtfeld.'], 422);
}
if ($lehrkraefte->isCodeTaken($werte['code'], $id)) {
    antworte(['fehler' => "Das Kürzel {$werte['code']} ist schon vergeben."], 422);
}

if ($methode === 'POST') {
    $id = $lehrkraefte->create($werte, BENUTZER_ID);
} else {
    $lehrkraefte->update($id, $werte, BENUTZER_ID);
}

antworte(['id' => $id] + $werte, $methode === 'POST' ? 201 : 200);
