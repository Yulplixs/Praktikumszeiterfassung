<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ClassRuleRepository;
use DomainException;
use InvalidArgumentException;

final class InternshipHoursCalculator
{
    private const HOURS_PER_YEAR = 40;
    private const ROUNDING_PRECISION = 3;

    public function __construct(
        private ClassRuleRepository $classRuleRepository
    ) {
    }

    public function calculateEntry(
        string $classCode,
        int $schoolYearId,
        int $halfYear,
        int $studentCount
    ): array {
        if ($schoolYearId < 1) {
            throw new InvalidArgumentException('School year ID must be positive.');
        }

        if ($studentCount < 1) {
            throw new InvalidArgumentException('Student count must be at least 1.');
        }

        if ($halfYear !== 1 && $halfYear !== 2) {
            throw new InvalidArgumentException('Half year must be 1 or 2.');
        }

        $rule = $this->classRuleRepository->findByClassCodeAndSchoolYear(
            $classCode,
            $schoolYearId
        );

        if ($rule === null) {
            throw new DomainException(
                'No class rule found for class '
                . $classCode
                . ' in school year '
                . $schoolYearId
                . '.'
            );
        }

        $factor = $rule->supervisionFactor();
        $weeks = $rule->weeksFor($halfYear);
        $hoursPerWeek = $factor * $studentCount;
        $hoursHalfYear = round(
            $hoursPerWeek * $weeks / self::HOURS_PER_YEAR,
            self::ROUNDING_PRECISION,
            PHP_ROUND_HALF_UP
        );

        return [
            'class_id' => $rule->classId(),
            'class_code' => $rule->classCode(),
            'school_year_id' => $rule->schoolYearId(),
            'half_year' => $halfYear,
            'student_count' => $studentCount,
            'factor_snapshot' => $factor,
            'weeks_snapshot' => $weeks,
            'hours_per_week' => $hoursPerWeek,
            'hours_half_year' => $hoursHalfYear,
        ];
    }

    public function summarizeByTeacher(array $entries): array
    {
        $teachers = [];

        foreach ($entries as $entry) {
            $this->validateSummaryEntry($entry);

            $teacherId = (int) $entry['teacher_id'];
            $halfYear = (int) $entry['half_year'];
            $hours = (float) $entry['hours_half_year'];

            if (!isset($teachers[$teacherId])) {
                $teachers[$teacherId] = [
                    'teacher_id' => $teacherId,
                    'teacher_code' => (string) $entry['teacher_code'],
                    'hours_half_year_1' => 0.0,
                    'hours_half_year_2' => 0.0,
                ];
            }

            $field = $halfYear === 1
                ? 'hours_half_year_1'
                : 'hours_half_year_2';

            $teachers[$teacherId][$field] += $hours;
        }

        $teacherSummaries = [];
        $totals = [
            'hours_half_year_1' => 0.0,
            'hours_half_year_2' => 0.0,
            'hours_school_year' => 0.0,
        ];

        foreach ($teachers as $teacher) {
            $hoursHalfYear1 = $this->roundHours(
                $teacher['hours_half_year_1']
            );
            $hoursHalfYear2 = $this->roundHours(
                $teacher['hours_half_year_2']
            );
            $hoursSchoolYear = $this->roundHours(
                $hoursHalfYear1 + $hoursHalfYear2
            );

            $teacherSummaries[] = [
                'teacher_id' => $teacher['teacher_id'],
                'teacher_code' => $teacher['teacher_code'],
                'hours_half_year_1' => $hoursHalfYear1,
                'hours_half_year_2' => $hoursHalfYear2,
                'hours_school_year' => $hoursSchoolYear,
            ];

            $totals['hours_half_year_1'] += $hoursHalfYear1;
            $totals['hours_half_year_2'] += $hoursHalfYear2;
            $totals['hours_school_year'] += $hoursSchoolYear;
        }

        usort(
            $teacherSummaries,
            static fn (array $left, array $right): int =>
                strcmp($left['teacher_code'], $right['teacher_code'])
        );

        foreach ($totals as $field => $hours) {
            $totals[$field] = $this->roundHours($hours);
        }

        return [
            'teachers' => $teacherSummaries,
            'totals' => $totals,
        ];
    }

    private function validateSummaryEntry(array $entry): void
    {
        foreach (
            ['teacher_id', 'teacher_code', 'half_year', 'hours_half_year']
            as $field
        ) {
            if (!array_key_exists($field, $entry)) {
                throw new InvalidArgumentException(
                    'Summary entry is missing ' . $field . '.'
                );
            }
        }

        if (
            (int) $entry['teacher_id'] < 1
            || (string) $entry['teacher_code'] === ''
        ) {
            throw new InvalidArgumentException(
                'Summary entry has an invalid teacher.'
            );
        }

        if (!in_array((int) $entry['half_year'], [1, 2], true)) {
            throw new InvalidArgumentException(
                'Summary entry half year must be 1 or 2.'
            );
        }

        if (
            !is_numeric($entry['hours_half_year'])
            || (float) $entry['hours_half_year'] < 0
        ) {
            throw new InvalidArgumentException(
                'Summary entry hours must be a non-negative number.'
            );
        }
    }

    private function roundHours(float $hours): float
    {
        return round(
            $hours,
            self::ROUNDING_PRECISION,
            PHP_ROUND_HALF_UP
        );
    }
}