<?php

declare(strict_types=1);

// GET    ?school_year_id=1  -> Einträge des Schuljahres + Summen je Lehrkraft (Kap. 4.2, Schritt 4)
// POST   {school_year_id, half_year, class_code, teacher_id, student_count} -> Eintrag berechnen und speichern
// PUT    ?id=5 {half_year, class_code, teacher_id, student_count}          -> neu berechnen und überschreiben
// DELETE ?id=5                                                             -> Eintrag löschen

require __DIR__ . '/bootstrap.php';

use App\Repository\ClassRuleRepository;
use App\Repository\SupervisionEntryRepository;
use App\Repository\TeacherRepository;
use App\Service\InternshipHoursCalculator;

// shortcut: Anmeldung übernimmt der Admin, bis dahin ist immer User 1 (system-import) der Bearbeiter.
const BENUTZER_ID = 1;

$pdo = db();
$eintraege = new SupervisionEntryRepository($pdo);
$rechner = new InternshipHoursCalculator(new ClassRuleRepository($pdo));
$methode = $_SERVER['REQUEST_METHOD'];

if ($methode === 'GET') {
    $liste = $eintraege->findBySchoolYear((int) ($_GET['school_year_id'] ?? 0));
    antworte([
        'eintraege' => $liste,
        'zusammenfassung' => $rechner->summarizeByTeacher($liste),
    ]);
}

if (!in_array($methode, ['POST', 'PUT', 'DELETE'], true)) {
    antworte(['fehler' => 'Methode nicht erlaubt.'], 405);
}

$bestehend = null;
if ($methode !== 'POST') {
    $bestehend = $eintraege->findById((int) ($_GET['id'] ?? 0));
    if ($bestehend === null) {
        antworte(['fehler' => 'Eintrag nicht gefunden.'], 404);
    }
}

if ($methode === 'DELETE') {
    $eintraege->delete((int) $bestehend['id'], BENUTZER_ID);
    antworte(['id' => (int) $bestehend['id']]);
}

$eingabe = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($eingabe)) {
    antworte(['fehler' => 'Ungültiger JSON-Body.'], 400);
}

$teacherId = (int) ($eingabe['teacher_id'] ?? 0);
if ((new TeacherRepository($pdo))->findCodeById($teacherId) === null) {
    antworte(['fehler' => 'Unbekannte Lehrkraft.'], 422);
}

$eintrag = $rechner->calculateEntry(
    (string) ($eingabe['class_code'] ?? ''),
    // Beim Bearbeiten bleibt das Schuljahr des Eintrags.
    (int) ($bestehend['school_year_id'] ?? $eingabe['school_year_id'] ?? 0),
    (int) ($eingabe['half_year'] ?? 0),
    (int) ($eingabe['student_count'] ?? 0)
);

if ($methode === 'PUT') {
    $eintraege->update((int) $bestehend['id'], $eintrag, $teacherId, BENUTZER_ID);
    antworte(['id' => (int) $bestehend['id']] + $eintrag);
}

$id = $eintraege->create($eintrag, $teacherId, BENUTZER_ID);

antworte(['id' => $id] + $eintrag, 201);
