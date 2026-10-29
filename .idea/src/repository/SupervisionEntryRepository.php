<?php

declare(strict_types=1);

namespace App\Repository;

use InvalidArgumentException;
use PDO;

final class SupervisionEntryRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function create(
        array $entry,
        int $teacherId,
        int $createdBy
    ): int {
        $this->validateEntry($entry);

        if ($teacherId < 1 || $createdBy < 1) {
            throw new InvalidArgumentException('Teacher and creator IDs must be positive.');
        }

        $sql = <<<'SQL'
            INSERT INTO supervision_entries (
                school_year_id,
                half_year,
                class_id,
                teacher_id,
                student_count,
                factor_snapshot,
                weeks_snapshot,
                hours_per_week,
                hours_half_year,
                created_by,
                created_at
            ) VALUES (
                :school_year_id,
                :half_year,
                :class_id,
                :teacher_id,
                :student_count,
                :factor_snapshot,
                :weeks_snapshot,
                :hours_per_week,
                :hours_half_year,
                :created_by,
                UTC_TIMESTAMP()
            )
            SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'school_year_id' => $entry['school_year_id'],
            'half_year' => $entry['half_year'],
            'class_id' => $entry['class_id'],
            'teacher_id' => $teacherId,
            'student_count' => $entry['student_count'],
            'factor_snapshot' => $entry['factor_snapshot'],
            'weeks_snapshot' => $entry['weeks_snapshot'],
            'hours_per_week' => $entry['hours_per_week'],
            'hours_half_year' => $entry['hours_half_year'],
            'created_by' => $createdBy,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findBySchoolYear(int $schoolYearId): array
    {
        return $this->fetchEntries($schoolYearId, null);
    }

    public function findBySchoolYearAndHalfYear(
        int $schoolYearId,
        int $halfYear
    ): array {
        if ($halfYear !== 1 && $halfYear !== 2) {
            throw new InvalidArgumentException('Half year must be 1 or 2.');
        }

        return $this->fetchEntries($schoolYearId, $halfYear);
    }

    private function fetchEntries(int $schoolYearId, ?int $halfYear): array
    {
        if ($schoolYearId < 1) {
            throw new InvalidArgumentException('School year ID must be positive.');
        }

        $sql = <<<'SQL'
            SELECT
                supervision_entries.id,
                supervision_entries.school_year_id,
                supervision_entries.half_year,
                supervision_entries.class_id,
                classes.code AS class_code,
                supervision_entries.teacher_id,
                teachers.code AS teacher_code,
                teachers.name AS teacher_name,
                supervision_entries.student_count,
                supervision_entries.factor_snapshot,
                supervision_entries.weeks_snapshot,
                supervision_entries.hours_per_week,
                supervision_entries.hours_half_year,
                supervision_entries.created_by,
                supervision_entries.updated_by,
                supervision_entries.created_at,
                supervision_entries.updated_at
            FROM supervision_entries
            INNER JOIN classes
                ON classes.id = supervision_entries.class_id
            INNER JOIN teachers
                ON teachers.id = supervision_entries.teacher_id
            WHERE supervision_entries.school_year_id = :school_year_id
            SQL;

        $parameters = ['school_year_id' => $schoolYearId];

        if ($halfYear !== null) {
            $sql .= ' AND supervision_entries.half_year = :half_year';
            $parameters['half_year'] = $halfYear;
        }

        $sql .= ' ORDER BY supervision_entries.half_year, classes.code, teachers.code, supervision_entries.id';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validateEntry(array $entry): void
    {
        $requiredFields = [
            'school_year_id',
            'half_year',
            'class_id',
            'student_count',
            'factor_snapshot',
            'weeks_snapshot',
            'hours_per_week',
            'hours_half_year',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $entry)) {
                throw new InvalidArgumentException('Entry is missing ' . $field . '.');
            }
        }

        foreach (['school_year_id', 'class_id'] as $field) {
            if (!is_numeric($entry[$field]) || (int) $entry[$field] < 1) {
                throw new InvalidArgumentException($field . ' must be a positive integer.');
            }
        }

        if (!in_array((int) $entry['half_year'], [1, 2], true)) {
            throw new InvalidArgumentException('Half year must be 1 or 2.');
        }

        if (!is_numeric($entry['student_count']) || (int) $entry['student_count'] < 1) {
            throw new InvalidArgumentException('Student count must be at least 1.');
        }

        foreach (
            ['factor_snapshot', 'weeks_snapshot', 'hours_per_week', 'hours_half_year']
            as $field
        ) {
            if (!is_numeric($entry[$field]) || (float) $entry[$field] < 0) {
                throw new InvalidArgumentException($field . ' must be a non-negative number.');
            }
        }
    }
}
