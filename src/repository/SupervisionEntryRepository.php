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

    // Transaktionssteuerung für den Excel-Import (alles oder nichts).
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function findTeacherIdByCode(string $teacherCode): ?int
    {
        $statement = $this->pdo->prepare('SELECT id FROM teachers WHERE code = :code');
        $statement->execute(['code' => $teacherCode]);

        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * Speichert einen berechneten Eintrag, der teacher_id und created_by bereits enthält (Excel-Import).
     */
    public function insert(array $entry): int
    {
        return $this->create($entry, (int) $entry['teacher_id'], (int) $entry['created_by']);
    }

    /**
     * Löscht alle Einträge eines Schuljahres (Import "Schuljahr ersetzen").
     * Läuft innerhalb der Import-Transaktion, daher ohne eigene Transaktion.
     */
    public function deleteBySchoolYear(int $schoolYearId, int $deletedBy): int
    {
        $statement = $this->pdo->prepare('DELETE FROM supervision_entries WHERE school_year_id = :id');
        $statement->execute(['id' => $schoolYearId]);

        $this->pdo->prepare(
            "INSERT INTO audit_log (user_id, action, entity_type, entity_id, created_at)
             VALUES (:user_id, 'replace_by_import', 'school_year', :entity_id, UTC_TIMESTAMP())"
        )->execute(['user_id' => $deletedBy, 'entity_id' => $schoolYearId]);

        return $statement->rowCount();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, school_year_id, half_year, class_id, teacher_id, student_count
             FROM supervision_entries WHERE id = :id'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Überschreibt Klasse, Halbjahr, Lehrkraft, Schülerzahl und den Berechnungs-Snapshot.
     * Das Schuljahr bleibt unverändert. Schreibt einen Eintrag in audit_log.
     */
    public function update(
        int $id,
        array $entry,
        int $teacherId,
        int $updatedBy
    ): void {
        $this->validateEntry($entry);

        if ($teacherId < 1 || $updatedBy < 1) {
            throw new InvalidArgumentException('Teacher and updater IDs must be positive.');
        }

        $sql = <<<'SQL'
            UPDATE supervision_entries SET
                half_year = :half_year,
                class_id = :class_id,
                teacher_id = :teacher_id,
                student_count = :student_count,
                factor_snapshot = :factor_snapshot,
                weeks_snapshot = :weeks_snapshot,
                hours_per_week = :hours_per_week,
                hours_half_year = :hours_half_year,
                updated_by = :updated_by,
                updated_at = UTC_TIMESTAMP()
            WHERE id = :id
            SQL;

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare($sql)->execute([
                'id' => $id,
                'half_year' => $entry['half_year'],
                'class_id' => $entry['class_id'],
                'teacher_id' => $teacherId,
                'student_count' => $entry['student_count'],
                'factor_snapshot' => $entry['factor_snapshot'],
                'weeks_snapshot' => $entry['weeks_snapshot'],
                'hours_per_week' => $entry['hours_per_week'],
                'hours_half_year' => $entry['hours_half_year'],
                'updated_by' => $updatedBy,
            ]);
            $this->audit('update', $id, $updatedBy);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Löscht den Eintrag endgültig und schreibt einen Eintrag in audit_log.
     */
    public function delete(int $id, int $deletedBy): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('DELETE FROM supervision_entries WHERE id = :id')
                ->execute(['id' => $id]);
            $this->audit('delete', $id, $deletedBy);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function audit(string $action, int $entityId, int $userId): void
    {
        $this->pdo->prepare(
            "INSERT INTO audit_log (user_id, action, entity_type, entity_id, created_at)
             VALUES (:user_id, :action, 'supervision_entry', :entity_id, UTC_TIMESTAMP())"
        )->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_id' => $entityId,
        ]);
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
                CONCAT_WS(' ', NULLIF(teachers.title, ''), teachers.firstname, teachers.surname) AS teacher_name,
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
