<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class TeacherRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /** Für Auswahllisten: Kürzel und Anzeigename. */
    public function findForSelection(): array
    {
        return $this->pdo->query(
            "SELECT id, code, CONCAT_WS(' ', NULLIF(title, ''), firstname, surname) AS name
             FROM teachers ORDER BY code"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Alle Lehrkräfte mit Einträgen und Stunden je Halbjahr im Schuljahr.
     * entry_count_all zählt alle Schuljahre (Löschen nur ohne Einträge).
     */
    public function findWithHours(int $schoolYearId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT teachers.id, teachers.code, teachers.title, teachers.firstname, teachers.surname,
                    (SELECT COUNT(*) FROM supervision_entries alle WHERE alle.teacher_id = teachers.id) AS entry_count_all,
                    COUNT(supervision_entries.id) AS entry_count,
                    COALESCE(SUM(IF(supervision_entries.half_year = 1, supervision_entries.hours_half_year, 0)), 0) AS hours_half_year_1,
                    COALESCE(SUM(IF(supervision_entries.half_year = 2, supervision_entries.hours_half_year, 0)), 0) AS hours_half_year_2
             FROM teachers
             LEFT JOIN supervision_entries
                 ON supervision_entries.teacher_id = teachers.id AND supervision_entries.school_year_id = ?
             GROUP BY teachers.id
             ORDER BY teachers.code'
        );
        $statement->execute([$schoolYearId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findCodeById(int $id): ?string
    {
        $statement = $this->pdo->prepare('SELECT code FROM teachers WHERE id = ?');
        $statement->execute([$id]);
        $code = $statement->fetchColumn();

        return $code === false ? null : (string) $code;
    }

    /** Vergleich nach DB-Collation, d. h. auch "GOB" und "GÖB" gelten als gleich. */
    public function isCodeTaken(string $code, int $exceptId = 0): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM teachers WHERE code = ? AND id <> ?');
        $statement->execute([$code, $exceptId]);

        return $statement->fetchColumn() !== false;
    }

    public function countEntries(int $id): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM supervision_entries WHERE teacher_id = ?');
        $statement->execute([$id]);

        return (int) $statement->fetchColumn();
    }

    /** @param array{code: string, title: string, firstname: string, surname: string} $teacher */
    public function create(array $teacher, int $userId): int
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                'INSERT INTO teachers (code, title, firstname, surname) VALUES (:code, :title, :firstname, :surname)'
            )->execute($teacher);
            $id = (int) $this->pdo->lastInsertId();
            (new AuditLogRepository($this->pdo))->log($userId, 'create', 'teacher', $id);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $id;
    }

    /** @param array{code: string, title: string, firstname: string, surname: string} $teacher */
    public function update(int $id, array $teacher, int $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                'UPDATE teachers SET code = :code, title = :title, firstname = :firstname, surname = :surname WHERE id = :id'
            )->execute($teacher + ['id' => $id]);
            (new AuditLogRepository($this->pdo))->log($userId, 'update', 'teacher', $id);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Scheitert am Fremdschlüssel, wenn noch Einträge existieren; vorher countEntries() prüfen. */
    public function delete(int $id, int $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('DELETE FROM teachers WHERE id = ?')->execute([$id]);
            (new AuditLogRepository($this->pdo))->log($userId, 'delete', 'teacher', $id);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
