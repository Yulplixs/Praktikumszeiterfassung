<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ClassRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /** @return list<array{id: int, code: string, name: string}> */
    public function findAll(): array
    {
        return $this->pdo->query('SELECT id, code, name FROM classes ORDER BY code')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exists(int $id): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM classes WHERE id = ?');
        $statement->execute([$id]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * Alle Klassen mit ihren Werten im Schuljahr (null ohne class_rules-Zeile),
     * Anzahl Einträge und Stunden je Halbjahr.
     */
    public function findWithRulesAndHours(int $schoolYearId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT classes.id, classes.code, classes.name,
                    class_rules.supervision_factor, class_rules.weeks_total,
                    class_rules.weeks_half_year_1, class_rules.weeks_half_year_2,
                    COUNT(supervision_entries.id) AS entry_count,
                    COALESCE(SUM(IF(supervision_entries.half_year = 1, supervision_entries.hours_half_year, 0)), 0) AS hours_half_year_1,
                    COALESCE(SUM(IF(supervision_entries.half_year = 2, supervision_entries.hours_half_year, 0)), 0) AS hours_half_year_2
             FROM classes
             LEFT JOIN class_rules
                 ON class_rules.class_id = classes.id AND class_rules.school_year_id = :jahr1
             LEFT JOIN supervision_entries
                 ON supervision_entries.class_id = classes.id AND supervision_entries.school_year_id = :jahr2
             GROUP BY classes.id
             ORDER BY classes.code'
        );
        $statement->execute(['jahr1' => $schoolYearId, 'jahr2' => $schoolYearId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
