<?php

declare(strict_types=1);

// GET ?school_year_id=1[&teacher_id=5] -> XLSX-Download: Praktikumsbetreuung I, II und Zusammenfassung je Lehrkraft.
// Ohne teacher_id: Gesamtexport. Mit teacher_id: nur die Einträge dieser Lehrkraft.

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../../vendor/autoload.php';

use App\Repository\ClassRuleRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SupervisionEntryRepository;
use App\Repository\TeacherRepository;
use App\Service\ExcelExportService;
use App\Service\InternshipHoursCalculator;

$pdo = db();
$schoolYearId = (int) ($_GET['school_year_id'] ?? 0);

$name = (new SchoolYearRepository($pdo))->findNameById($schoolYearId);
if ($name === null) {
    antworte(['fehler' => 'Unbekanntes Schuljahr.'], 404);
}

$eintraege = (new SupervisionEntryRepository($pdo))->findBySchoolYear($schoolYearId);

$dateiname = 'Praktikumsbetreuung_' . str_replace('/', '-', $name);
$teacherId = (int) ($_GET['teacher_id'] ?? 0);
if ($teacherId > 0) {
    $kuerzel = (new TeacherRepository($pdo))->findCodeById($teacherId);
    if ($kuerzel === null) {
        antworte(['fehler' => 'Unbekannte Lehrkraft.'], 404);
    }
    $eintraege = array_values(array_filter(
        $eintraege,
        static fn (array $e): bool => (int) $e['teacher_id'] === $teacherId
    ));
    // Kürzel enthalten Umlaute (GÖK, BRÖ); im Dateinamen nur sichere Zeichen.
    $dateiname .= '_' . preg_replace('/[^A-Za-z0-9]/', '_', strtr($kuerzel, ['Ä' => 'AE', 'Ö' => 'OE', 'Ü' => 'UE']));
}
$service = new ExcelExportService(new InternshipHoursCalculator(new ClassRuleRepository($pdo)));

// Erst in eine Temp-Datei schreiben, damit ein Fehler keinen halben Download erzeugt.
$datei = tempnam(sys_get_temp_dir(), 'export');
try {
    $service->export($schoolYearId, $eintraege, $datei);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $dateiname . '.xlsx"');
    header('Content-Length: ' . filesize($datei));
    readfile($datei);
} finally {
    unlink($datei);
}
