<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\SupervisionEntryRepository;
use DomainException;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

final class ExcelImportService
{
    /**
     * Die Blattnamen legen fest, zu welchem Halbjahr
     * die enthaltenen Einträge gehören.
     */
    private const SHEETS = [
        'Praktikumsbetreuung I' => 1,
        'Praktikumsbetreuung II' => 2,
    ];

    /**
     * Erwartete Überschriften des ExcelExportService.
     */
    private const HEADERS = [
        'A' => 'Klasse',
        'B' => 'Betreuungsfaktor',
        'C' => 'Praktikumswochen',
        'D' => 'Lehrkraft-Kürzel',
        'E' => 'Schülerzahl',
        'F' => 'Stunden je Woche',
        'G' => 'Stunden je Halbjahr',
    ];

    public function __construct(
        private InternshipHoursCalculator $calculator,
        private SupervisionEntryRepository $entryRepository
    ) {
    }

    /**
     * Importiert eine XLSX-Datei für ein bestimmtes Schuljahr und ersetzt dabei
     * alle bisherigen Einträge dieses Schuljahres.
     *
     * Erst wird die ganze Datei geprüft und alle Zeilenfehler werden gesammelt.
     * Gespeichert wird nur eine fehlerfreie Datei, in einer Transaktion.
     * Fehlende Blätter oder falsche Überschriften brechen sofort mit DomainException ab.
     *
     * @param string $filePath Pfad zur hochgeladenen XLSX-Datei
     * @param int $schoolYearId ID des ausgewählten Schuljahres
     * @param int $userId Interne Datenbank-ID des angemeldeten Nutzers
     *
     * @return array{imported: int, deleted: int, rows: int, errors: list<string>}
     */
    public function import(
        string $filePath,
        int $schoolYearId,
        int $userId
    ): array {
        if ($schoolYearId < 1) {
            throw new InvalidArgumentException(
                'School year ID must be positive.'
            );
        }

        if ($userId < 1) {
            throw new InvalidArgumentException(
                'User ID must be positive.'
            );
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new InvalidArgumentException(
                'The import file does not exist or cannot be read.'
            );
        }

        // Nur XLSX-Dateien einlesen.
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);

        try {
            $rows = [];
            $errors = [];

            foreach (self::SHEETS as $sheetName => $halfYear) {
                $sheet = $spreadsheet->getSheetByName($sheetName);

                if ($sheet === null) {
                    throw new DomainException(
                        'Tabellenblatt "' . $sheetName . '" fehlt.'
                    );
                }

                $this->validateHeaders($sheet);

                $rows = array_merge(
                    $rows,
                    $this->readRows($sheet, $halfYear, $errors)
                );
            }

            // Jeder Lesefehler betrifft genau eine nicht-leere Zeile.
            $rowCount = count($rows) + count($errors);

            /*
             * Unveränderte Zeilen behalten die exakt gespeicherten Stunden ihres bisherigen Eintrags.
             * Altdaten aus der Excel wurden teils mit ungerundeten Wochen berechnet (z. B. 7,8225 statt
             * Snapshot 7,82) und ließen sich aus Faktor und Wochen nicht exakt nachrechnen.
             */
            $existingHours = [];
            foreach ($this->entryRepository->findBySchoolYear($schoolYearId) as $existing) {
                $existingHours[$this->snapshotKey($existing)] ??= [
                    'hours_per_week' => $existing['hours_per_week'],
                    'hours_half_year' => $existing['hours_half_year'],
                ];
            }

            // Erst alle Zeilen berechnen, dann speichern: so werden alle Fehler auf einmal gemeldet.
            $entries = [];
            foreach ($rows as $row) {
                $entry = $this->prepareRow($row, $schoolYearId, $userId, $errors);
                if ($entry !== null) {
                    $entries[] = ($existingHours[$this->snapshotKey($entry)] ?? []) + $entry;
                }
            }

            $result = [
                'imported' => 0,
                'deleted' => 0,
                'rows' => $rowCount,
                'errors' => $errors,
            ];

            if ($errors !== []) {
                // Lese- und Berechnungsfehler entstehen in zwei Durchläufen; nach Blatt und Zeile ordnen.
                sort($result['errors'], SORT_NATURAL);

                return $result;
            }

            /*
             * Ersetzen und Speichern laufen in einer Transaktion.
             * Bei einem Fehler bleibt der bisherige Stand vollständig erhalten.
             */
            $this->entryRepository->beginTransaction();

            try {
                $result['deleted'] = $this->entryRepository->deleteBySchoolYear(
                    $schoolYearId,
                    $userId
                );

                foreach ($entries as $entry) {
                    $this->entryRepository->insert($entry);
                }

                $this->entryRepository->commit();
            } catch (Throwable $exception) {
                $this->entryRepository->rollBack();

                throw $exception;
            }

            $result['imported'] = count($entries);

            return $result;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * Prüft, ob die Datei die erwarteten Spaltenüberschriften enthält.
     */
    private function validateHeaders(Worksheet $sheet): void
    {
        foreach (self::HEADERS as $column => $expected) {
            $actual = trim(
                (string) $sheet->getCell($column . '1')->getValue()
            );

            if ($actual !== $expected) {
                throw new DomainException(
                    sprintf(
                        'Tabellenblatt "%s", Zelle %s1: Überschrift "%s" erwartet.',
                        $sheet->getTitle(),
                        $column,
                        $expected
                    )
                );
            }
        }
    }

    /**
     * Liest die für den Import benötigten Eingabedaten.
     *
     * Faktor (B) und Wochen (C) aus der Datei bleiben als Snapshot erhalten, damit ein Re-Import
     * bestehende Stunden nicht verändert. Sind B und C leer (neue Zeile), gelten die aktuellen
     * Klassenwerte. Die Stunden (F, G) werden immer neu berechnet, nie übernommen.
     *
     * @return array<int, array{
     *     sheet: string,
     *     row: int,
     *     half_year: int,
     *     class_code: string,
     *     teacher_code: string,
     *     student_count: int,
     *     factor: ?float,
     *     weeks: ?float
     * }>
     *
     * @param list<string> $errors Fehlerhafte Zeilen werden hier gesammelt statt abzubrechen.
     */
    private function readRows(Worksheet $sheet, int $halfYear, array &$errors): array
    {
        $rows = [];

        $lastRow = max(
            $sheet->getHighestDataRow('A'),
            $sheet->getHighestDataRow('D'),
            $sheet->getHighestDataRow('E')
        );

        for ($rowNumber = 2; $rowNumber <= $lastRow; $rowNumber++) {
            $classCell = $sheet->getCell('A' . $rowNumber);
            $teacherCell = $sheet->getCell('D' . $rowNumber);
            $studentCell = $sheet->getCell('E' . $rowNumber);
            $factorCell = $sheet->getCell('B' . $rowNumber);
            $weeksCell = $sheet->getCell('C' . $rowNumber);

            $classValue = $classCell->getValue();
            $teacherValue = $teacherCell->getValue();
            $studentValue = $studentCell->getValue();
            $factorValue = $factorCell->getValue();
            $weeksValue = $weeksCell->getValue();

            if (
                $this->isEmpty($classValue)
                && $this->isEmpty($teacherValue)
                && $this->isEmpty($studentValue)
            ) {
                continue;
            }

            $classCode = trim((string) $classValue);
            $teacherCode = trim((string) $teacherValue);
            $message = null;

            if ($this->hasFormula($classCell, $teacherCell, $studentCell, $factorCell, $weeksCell)) {
                $message = 'Formeln sind in den Eingabefeldern nicht erlaubt.';
            } elseif ($this->isEmpty($factorValue) !== $this->isEmpty($weeksValue)) {
                $message = 'Betreuungsfaktor und Praktikumswochen nur gemeinsam angeben oder beide leer lassen.';
            } elseif (
                !$this->isEmpty($factorValue)
                && (!is_numeric($factorValue) || !is_numeric($weeksValue)
                    || (float) $factorValue < 0 || (float) $weeksValue < 0)
            ) {
                $message = 'Betreuungsfaktor und Praktikumswochen müssen Zahlen ab 0 sein.';
            } elseif ($classCode === '' || $teacherCode === '') {
                $message = 'Klasse und Lehrkraft-Kürzel sind Pflichtfelder.';
            } elseif (
                !is_numeric($studentValue)
                || (float) $studentValue < 1
                || floor((float) $studentValue) !== (float) $studentValue
                || (float) $studentValue > PHP_INT_MAX
            ) {
                $message = 'Die Schülerzahl muss eine positive ganze Zahl sein.';
            }

            if ($message !== null) {
                $errors[] = $this->rowMessage($sheet->getTitle(), $rowNumber, $message);
                continue;
            }

            $rows[] = [
                'sheet' => $sheet->getTitle(),
                'row' => $rowNumber,
                'half_year' => $halfYear,
                'class_code' => $classCode,
                'teacher_code' => $teacherCode,
                'student_count' => (int) $studentValue,
                'factor' => $this->isEmpty($factorValue) ? null : (float) $factorValue,
                'weeks' => $this->isEmpty($weeksValue) ? null : (float) $weeksValue,
            ];
        }

        return $rows;
    }

    /**
     * Berechnet einen einzelnen Eintrag. Bei unbekannter Lehrkraft oder Klasse
     * wird der Fehler gesammelt und null zurückgegeben.
     *
     * @param array{
     *     sheet: string,
     *     row: int,
     *     half_year: int,
     *     class_code: string,
     *     teacher_code: string,
     *     student_count: int
     * } $row
     * @param list<string> $errors
     */
    private function prepareRow(
        array $row,
        int $schoolYearId,
        int $userId,
        array &$errors
    ): ?array {
        try {
            $teacherId = $this->entryRepository->findTeacherIdByCode(
                $row['teacher_code']
            );

            if ($teacherId === null) {
                throw new DomainException(
                    'Unbekanntes Lehrkraft-Kürzel: '
                    . $row['teacher_code']
                );
            }

            $entry = $this->calculator->calculateEntry(
                $row['class_code'],
                $schoolYearId,
                $row['half_year'],
                $row['student_count']
            );

            // Snapshot aus der Datei hat Vorrang; calculateEntry prüft trotzdem Klasse und Schuljahr.
            if ($row['factor'] !== null) {
                $entry = $this->calculator->withSnapshot($entry, $row['factor'], $row['weeks']);
            }

            $entry['teacher_id'] = $teacherId;
            $entry['created_by'] = $userId;

            return $entry;
        } catch (InvalidArgumentException | DomainException $exception) {
            $errors[] = $this->rowMessage(
                $row['sheet'],
                $row['row'],
                $this->translate($exception->getMessage())
            );

            return null;
        }
    }

    /**
     * Identifiziert einen Eintrag über seine fachlichen Eingaben und den Snapshot.
     * Zahlen auf 4 Stellen normiert, da die DB DECIMAL-Strings und die Datei Floats liefert.
     */
    private function snapshotKey(array $entry): string
    {
        return implode('|', [
            (int) $entry['half_year'],
            (int) $entry['class_id'],
            (int) $entry['teacher_id'],
            (int) $entry['student_count'],
            number_format((float) $entry['factor_snapshot'], 4, '.', ''),
            number_format((float) $entry['weeks_snapshot'], 4, '.', ''),
        ]);
    }

    /**
     * Formeln in den Eingabespalten sind nicht erlaubt.
     */
    private function hasFormula(Cell ...$cells): bool
    {
        foreach ($cells as $cell) {
            if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                return true;
            }
        }

        return false;
    }

    /**
     * Die Fehlermeldungen des Calculators sind englisch; für die Anzeige im Import übersetzen.
     */
    private function translate(string $message): string
    {
        if (preg_match('/^No class rule found for class (.+) in school year \d+\.$/', $message, $match) === 1) {
            return 'Unbekannte Klasse oder keine Klassenwerte im Schuljahr: ' . $match[1];
        }

        return $message;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null
            || (is_string($value) && trim($value) === '');
    }

    private function rowMessage(
        string $sheet,
        int $row,
        string $message
    ): string {
        return sprintf(
            'Tabellenblatt "%s", Zeile %d: %s',
            $sheet,
            $row,
            $message
        );
    }
}