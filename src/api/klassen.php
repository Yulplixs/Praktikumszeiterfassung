<?php

declare(strict_types=1);

// GET ?school_year_id=1                -> Klassen mit Faktor, Wochen und Stunden je Halbjahr
// PUT ?class_id=3&school_year_id=1 {supervision_factor, weeks_total, weeks_half_year_1, weeks_half_year_2}
//                                      -> Klassenwerte setzen. Bestehende Einträge behalten ihren Snapshot.

require __DIR__ . '/bootstrap.php';

use App\Repository\ClassRepository;
use App\Repository\ClassRuleRepository;
use App\Repository\SchoolYearRepository;

// shortcut: Anmeldung übernimmt der Admin, bis dahin ist immer User 1 (system-import) der Bearbeiter.
const BENUTZER_ID = 1;

$pdo = db();
$klassen = new ClassRepository($pdo);
$schuljahr = (int) ($_GET['school_year_id'] ?? 0);
$methode = $_SERVER['REQUEST_METHOD'];

if ($methode === 'GET') {
    antworte($klassen->findWithRulesAndHours($schuljahr));
}

if ($methode !== 'PUT') {
    antworte(['fehler' => 'Methode nicht erlaubt.'], 405);
}

$klasse = (int) ($_GET['class_id'] ?? 0);
if (!$klassen->exists($klasse) || (new SchoolYearRepository($pdo))->findNameById($schuljahr) === null) {
    antworte(['fehler' => 'Klasse oder Schuljahr nicht gefunden.'], 404);
}

$eingabe = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($eingabe)) {
    antworte(['fehler' => 'Ungültiger JSON-Body.'], 400);
}

$werte = [];
foreach (['supervision_factor', 'weeks_total', 'weeks_half_year_1', 'weeks_half_year_2'] as $feld) {
    $wert = $eingabe[$feld] ?? null;
    // decimal(8,4) in class_rules
    if (!is_numeric($wert) || (float) $wert < 0 || (float) $wert > 9999) {
        antworte(['fehler' => "Ungültiger Wert für $feld (Zahl von 0 bis 9999)."], 422);
    }
    $werte[$feld] = round((float) $wert, 4);
}

(new ClassRuleRepository($pdo))->save($klasse, $schuljahr, $werte, BENUTZER_ID);

antworte(['class_id' => $klasse, 'school_year_id' => $schuljahr] + $werte);
