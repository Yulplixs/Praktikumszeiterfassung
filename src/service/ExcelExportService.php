<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ExcelExportService
{
    public function __construct(
        private InternshipHoursCalculator $calculator
    ) {
    }

    /**
     * Erstellt einen Excel-Export für genau ein Schuljahr.
     *
     * Erwartete Felder je Eintrag:
     * school_year_id, half_year, class_code, teacher_id,
     * teacher_code, student_count, factor_snapshot,
     * weeks_snapshot, hours_per_week und hours_half_year.
     *
     * @param array<int, array<string, mixed>> $entries
     * @param string $destination Dateipfad oder beschreibbarer PHP-Stream.
     */
    public function export(
        int $schoolYearId,
        array $entries,
        string $destination
    ): void {
        if ($schoolYearId < 1) {
            throw new InvalidArgumentException(
                'School year ID must be positive.'
            );
        }

        if ($destination === '') {
            throw new InvalidArgumentException(
                'Export destination must not be empty.'
            );
        }

        foreach ($entries as $entry) {
            $this->validateEntry($entry, $schoolYearId);
        }

        // Verwendet die bereits vorhandene Berechnung der Zusammenfassung.
        $summary = $this->calculator->summarizeByTeacher($entries);

        $spreadsheet = new Spreadsheet();

        try {
            $halfYear1 = $spreadsheet->getActiveSheet();
            $halfYear1->setTitle('Praktikumsbetreuung I');

            $halfYear2 = $spreadsheet->createSheet();
            $halfYear2->setTitle('Praktikumsbetreuung II');

            $summarySheet = $spreadsheet->createSheet();
            $summarySheet->setTitle('Zusammenfassung je Lehrkraft');

            $this->writeEntries($halfYear1, $entries, 1);
            $this->writeEntries($halfYear2, $entries, 2);
            $this->writeSummary($summarySheet, $summary);

            $spreadsheet->setActiveSheetIndex(0);

            $writer = new Xlsx($spreadsheet);
            $writer->save($destination);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private function writeEntries(
        Worksheet $sheet,
        array $entries,
        int $halfYear
    ): void {
        $this->writeHeader($sheet, [
            'A' => 'Klasse',
            'B' => 'Betreuungsfaktor',
            'C' => 'Praktikumswochen',
            'D' => 'Lehrkraft-Kürzel',
            'E' => 'Schülerzahl',
            'F' => 'Stunden je Woche',
            'G' => 'Stunden je Halbjahr',
        ]);

        $row = 2;

        foreach ($entries as $entry) {
            if ((int) $entry['half_year'] !== $halfYear) {
                continue;
            }

            $this->writeText($sheet, 'A' . $row, (string) $entry['class_code']);
            $sheet->setCellValue('B' . $row, (float) $entry['factor_snapshot']);
            $sheet->setCellValue('C' . $row, (float) $entry['weeks_snapshot']);
            $this->writeText($sheet, 'D' . $row, (string) $entry['teacher_code']);
            $sheet->setCellValue('E' . $row, (int) $entry['student_count']);
            $sheet->setCellValue('F' . $row, (float) $entry['hours_per_week']);
            $sheet->setCellValue('G' . $row, (float) $entry['hours_half_year']);

            $row++;
        }

        if ($row > 2) {
            $lastRow = $row - 1;

            $sheet->getStyle('B2:C' . $lastRow)
                ->getNumberFormat()
                ->setFormatCode('0.####');

            $sheet->getStyle('F2:F' . $lastRow)
                ->getNumberFormat()
                ->setFormatCode('0.####');

            $sheet->getStyle('G2:G' . $lastRow)
                ->getNumberFormat()
                ->setFormatCode('0.000');
        }
    }

    /**
     * @param array{
     *     teachers: array<int, array<string, mixed>>,
     *     totals: array<string, mixed>
     * } $summary
     */
    private function writeSummary(Worksheet $sheet, array $summary): void
    {
        $this->writeHeader($sheet, [
            'A' => 'Lehrkraft-Kürzel',
            'B' => 'Stunden HJ1',
            'C' => 'Stunden HJ2',
            'D' => 'Gesamt',
        ]);

        $row = 2;

        foreach ($summary['teachers'] as $teacher) {
            $this->writeText(
                $sheet,
                'A' . $row,
                (string) $teacher['teacher_code']
            );

            $sheet->setCellValue(
                'B' . $row,
                (float) $teacher['hours_half_year_1']
            );

            $sheet->setCellValue(
                'C' . $row,
                (float) $teacher['hours_half_year_2']
            );

            $sheet->setCellValue(
                'D' . $row,
                (float) $teacher['hours_school_year']
            );

            $row++;
        }

        $totals = $summary['totals'];

        $this->writeText($sheet, 'A' . $row, 'Gesamtsumme');
        $sheet->setCellValue('B' . $row, (float) $totals['hours_half_year_1']);
        $sheet->setCellValue('C' . $row, (float) $totals['hours_half_year_2']);
        $sheet->setCellValue('D' . $row, (float) $totals['hours_school_year']);

        $sheet->getStyle('A' . $row . ':D' . $row)
            ->getFont()
            ->setBold(true);

        $sheet->getStyle('B2:D' . $row)
            ->getNumberFormat()
            ->setFormatCode('0.000');
    }

    /**
     * @param array<string, string> $columns
     */
    private function writeHeader(Worksheet $sheet, array $columns): void
    {
        foreach ($columns as $column => $label) {
            $sheet->setCellValue($column . '1', $label);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $lastColumn = array_key_last($columns);

        $sheet->getStyle('A1:' . $lastColumn . '1')
            ->getFont()
            ->setBold(true);

        $sheet->freezePane('A2');
    }

    private function writeText(
        Worksheet $sheet,
        string $cell,
        string $value
    ): void {
        $sheet->setCellValueExplicit(
            $cell,
            $value,
            DataType::TYPE_STRING
        );
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function validateEntry(array $entry, int $schoolYearId): void
    {
        $requiredFields = [
            'school_year_id',
            'half_year',
            'class_code',
            'teacher_id',
            'teacher_code',
            'student_count',
            'factor_snapshot',
            'weeks_snapshot',
            'hours_per_week',
            'hours_half_year',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $entry)) {
                throw new InvalidArgumentException(
                    'Export entry is missing ' . $field . '.'
                );
            }
        }

        if ((int) $entry['school_year_id'] !== $schoolYearId) {
            throw new InvalidArgumentException(
                'All export entries must belong to the selected school year.'
            );
        }

        if (!in_array((int) $entry['half_year'], [1, 2], true)) {
            throw new InvalidArgumentException(
                'Export entry half year must be 1 or 2.'
            );
        }

        foreach ([
            'student_count',
            'factor_snapshot',
            'weeks_snapshot',
            'hours_per_week',
            'hours_half_year',
        ] as $field) {
            if (!is_numeric($entry[$field]) || (float) $entry[$field] < 0) {
                throw new InvalidArgumentException(
                    'Export entry ' . $field . ' must be a non-negative number.'
                );
            }
        }
    }
}