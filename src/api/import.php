<?php

declare(strict_types=1);

// POST multipart: datei (XLSX im Exportformat), school_year_id
// -> ersetzt alle Einträge des Schuljahres durch den Inhalt der Datei.
// Antwort 200 {imported, deleted, rows}, 422 {fehler, details[]} bei fehlerhaften Zeilen.
// Jeder Versuch wird in import_batches protokolliert.

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../../vendor/autoload.php';

use App\Repository\ClassRuleRepository;
use App\Repository\ImportBatchRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SupervisionEntryRepository;
use App\Service\ExcelImportService;
use App\Service\InternshipHoursCalculator;

// shortcut: Anmeldung übernimmt der Admin, bis dahin ist immer User 1 (system-import) der Importierende.
const BENUTZER_ID = 1;
const MAX_GROESSE = 5 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    antworte(['fehler' => 'Methode nicht erlaubt.'], 405);
}

$datei = $_FILES['datei'] ?? null;
if (!is_array($datei) || !is_int($datei['error'] ?? null)) {
    antworte(['fehler' => 'Keine Datei hochgeladen.'], 400);
}
if ($datei['error'] === UPLOAD_ERR_INI_SIZE || $datei['error'] === UPLOAD_ERR_FORM_SIZE || $datei['size'] > MAX_GROESSE) {
    antworte(['fehler' => 'Die Datei ist größer als 5 MB.'], 422);
}
if ($datei['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($datei['tmp_name'])) {
    antworte(['fehler' => 'Upload fehlgeschlagen.'], 400);
}
if (strtolower(pathinfo((string) $datei['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
    antworte(['fehler' => 'Nur .xlsx-Dateien können importiert werden.'], 422);
}

$pdo = db();
$schoolYearId = (int) ($_POST['school_year_id'] ?? 0);
if ((new SchoolYearRepository($pdo))->findNameById($schoolYearId) === null) {
    antworte(['fehler' => 'Unbekanntes Schuljahr.'], 404);
}

$eintraege = new SupervisionEntryRepository($pdo);
$service = new ExcelImportService(
    new InternshipHoursCalculator(new ClassRuleRepository($pdo)),
    $eintraege
);
$protokoll = new ImportBatchRepository($pdo);
$pruefsumme = hash_file('sha256', $datei['tmp_name']);

try {
    $ergebnis = $service->import($datei['tmp_name'], $schoolYearId, BENUTZER_ID);
} catch (Throwable $e) {
    // Datei unlesbar, Blatt fehlt oder Überschrift falsch.
    $protokoll->log($schoolYearId, BENUTZER_ID, $pruefsumme, 'failed', 0, 1);
    if ($e instanceof DomainException) {
        antworte(['fehler' => 'Datei kann nicht importiert werden: ' . $e->getMessage(), 'details' => []], 422);
    }
    if ($e instanceof PhpOffice\PhpSpreadsheet\Reader\Exception) {
        // Originalmeldung enthält Serverpfade, daher nur ins Log.
        error_log((string) $e);
        antworte(['fehler' => 'Die Datei ist keine gültige XLSX-Datei.', 'details' => []], 422);
    }
    throw $e;
}

if ($ergebnis['errors'] !== []) {
    $protokoll->log($schoolYearId, BENUTZER_ID, $pruefsumme, 'rejected', $ergebnis['rows'], count($ergebnis['errors']));
    antworte([
        'fehler' => count($ergebnis['errors']) . ' fehlerhafte Zeile(n). Es wurde nichts geändert.',
        'details' => $ergebnis['errors'],
    ], 422);
}

$protokoll->log($schoolYearId, BENUTZER_ID, $pruefsumme, 'imported', $ergebnis['rows'], 0);

antworte([
    'imported' => $ergebnis['imported'],
    'deleted' => $ergebnis['deleted'],
    'rows' => $ergebnis['rows'],
]);
