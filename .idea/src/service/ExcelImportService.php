<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\InternshipEntryRepository;
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
        private InternshipEntryRepository $entryRepository
    ) {
    }

    /**
     * Importiert eine XLSX-Datei für ein bestimmtes Schuljahr.
     *
     * @param string $filePath Pfad zur hochgeladenen XLSX-Datei
     * @param int $schoolYearId ID des ausgewählten Schuljahres
     * @param int $userId Interne Datenbank-ID des angemeldeten Nutzers
     *
     * @return array{imported: int}
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

            foreach (self::SHEETS as $sheetName => $halfYear) {
                $sheet = $spreadsheet->getSheetByName($sheetName);

                if ($sheet === null) {
                    throw new DomainException(
                        'Missing worksheet: ' . $sheetName . '.'
                    );
                }

                $this->validateHeaders($sheet);

                $rows = array_merge(
                    $rows,
                    $this->readRows($sheet, $halfYear)
                );
            }

            /*
             * Alle Einträge werden innerhalb einer Transaktion gespeichert.
             * Falls ein Eintrag fehlerhaft ist, wird nichts übernommen.
             */
            $this->entryRepository->beginTransaction();

            try {
                foreach ($rows as $row) {
                    $this->importRow(
                        $row,
                        $schoolYearId,
                        $userId
                    );
                }

                $this->entryRepository->commit();
            } catch (Throwable $exception) {
                $this->entryRepository->rollBack();

                throw $exception;
            }

            return [
                'imported' => count($rows),
            ];
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
                        'Invalid header in worksheet "%s", cell %s1. '
                        . 'Expected "%s".',
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
     * Faktor, Wochen und Stunden werden nicht aus der Datei übernommen,
     * sondern später durch den Calculator neu ermittelt.
     *
     * @return array<int, array{
     *     sheet: string,
     *     row: int,
     *     half_year: int,
     *     class_code: string,
     *     teacher_code: string,
     *     student_count: int
     * }>
     */
    private function readRows(Worksheet $sheet, int $halfYear): array
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

            $classValue = $classCell->getValue();
            $teacherValue = $teacherCell->getValue();
            $studentValue = $studentCell->getValue();

            if (
                $this->isEmpty($classValue)
                && $this->isEmpty($teacherValue)
                && $this->isEmpty($studentValue)
            ) {
                continue;
            }

            foreach ([$classCell, $teacherCell, $studentCell] as $cell) {
                $this->rejectFormula(
                    $cell,
                    $sheet->getTitle(),
                    $rowNumber
                );
            }

            $classCode = trim((string) $classValue);
            $teacherCode = trim((string) $teacherValue);

            if ($classCode === '' || $teacherCode === '') {
                throw $this->rowError(
                    $sheet->getTitle(),
                    $rowNumber,
                    'Klasse und Lehrkraft-Kürzel sind Pflichtfelder.'
                );
            }

            if (
                !is_numeric($studentValue)
                || (float) $studentValue < 1
                || floor((float) $studentValue) !== (float) $studentValue
                || (float) $studentValue > PHP_INT_MAX
            ) {
                throw $this->rowError(
                    $sheet->getTitle(),
                    $rowNumber,
                    'Die Schülerzahl muss eine positive ganze Zahl sein.'
                );
            }

            $rows[] = [
                'sheet' => $sheet->getTitle(),
                'row' => $rowNumber,
                'half_year' => $halfYear,
                'class_code' => $classCode,
                'teacher_code' => $teacherCode,
                'student_count' => (int) $studentValue,
            ];
        }

        return $rows;
    }

    /**
     * Berechnet und speichert einen einzelnen Eintrag.
     *
     * @param array{
     *     sheet: string,
     *     row: int,
     *     half_year: int,
     *     class_code: string,
     *     teacher_code: string,
     *     student_count: int
     * } $row
     */
    private function importRow(
        array $row,
        int $schoolYearId,
        int $userId
    ): void {
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

            $entry['teacher_id'] = $teacherId;
            $entry['created_by'] = $userId;

            /*
             * Das Repository speichert die passenden Felder in
             * supervision_entries und setzt created_at.
             */
            $this->entryRepository->insert($entry);
        } catch (InvalidArgumentException | DomainException $exception) {
            throw $this->rowError(
                $row['sheet'],
                $row['row'],
                $exception->getMessage(),
                $exception
            );
        }
    }

    /**
     * Verhindert Formeln in den drei importierten Eingabespalten.
     */
    private function rejectFormula(
        Cell $cell,
        string $sheetName,
        int $rowNumber
    ): void {
        if ($cell->getDataType() === DataType::TYPE_FORMULA) {
            throw $this->rowError(
                $sheetName,
                $rowNumber,
                'Formeln sind in den Eingabefeldern nicht erlaubt.'
            );
        }
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null
            || (is_string($value) && trim($value) === '');
    }

    private function rowError(
        string $sheet,
        int $row,
        string $message,
        ?Throwable $previous = null
    ): DomainException {
        return new DomainException(
            sprintf(
                'Tabellenblatt "%s", Zeile %d: %s',
                $sheet,
                $row,
                $message
            ),
            0,
            $previous
        );
    }
}