<?php

declare(strict_types=1);

// GET: Schuljahre, Klassen und Lehrkräfte für Auswahllisten.

require __DIR__ . '/bootstrap.php';

$pdo = db();

antworte([
    'schuljahre' => $pdo->query(
        'SELECT id, name FROM school_years ORDER BY start_date DESC'
    )->fetchAll(),
    'klassen' => $pdo->query(
        'SELECT id, code, name FROM classes ORDER BY code'
    )->fetchAll(),
    'lehrkraefte' => $pdo->query(
        "SELECT id, code, CONCAT_WS(' ', NULLIF(title, ''), firstname, surname) AS name
         FROM teachers ORDER BY code"
    )->fetchAll(),
]);
