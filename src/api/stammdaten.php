<?php

declare(strict_types=1);

// GET: Schuljahre, Klassen und Lehrkräfte für Auswahllisten.

require __DIR__ . '/bootstrap.php';

use App\Repository\ClassRepository;
use App\Repository\ClassRuleRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\TeacherRepository;

$pdo = db();

antworte([
    'schuljahre' => (new SchoolYearRepository($pdo))->findAll(),
    'klassen' => (new ClassRepository($pdo))->findAll(),
    // Wochen je Klasse und Schuljahr: das Formular zeigt nur Klassen mit Wochen im gewählten Halbjahr.
    'klassenwerte' => (new ClassRuleRepository($pdo))->findAllWeeks(),
    'lehrkraefte' => (new TeacherRepository($pdo))->findForSelection(),
]);
