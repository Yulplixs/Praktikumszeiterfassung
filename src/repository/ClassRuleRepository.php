<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\ClassRule;
use PDO;

final class ClassRuleRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function findByClassCodeAndSchoolYear(
        string $classCode,
        int $schoolYearId
    ): ?ClassRule {
        $sql = <<<'SQL'
            SELECT
                classes.id AS class_id,
                classes.code AS class_code,
                class_rules.school_year_id,
                class_rules.supervision_factor,
                class_rules.weeks_half_year_1,
                class_rules.weeks_half_year_2
            FROM class_rules
            INNER JOIN classes
                ON classes.id = class_rules.class_id
            WHERE classes.code = :class_code
              AND class_rules.school_year_id = :school_year_id
            SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'class_code' => $classCode,
            'school_year_id' => $schoolYearId,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new ClassRule(
            (int) $row['class_id'],
            (string) $row['class_code'],
            (int) $row['school_year_id'],
            (float) $row['supervision_factor'],
            (float) $row['weeks_half_year_1'],
            (float) $row['weeks_half_year_2']
        );
    }

    /** Wochen je Halbjahr für alle Klassen und Schuljahre (Klassenauswahl im Formular). */
    public function findAllWeeks(): array
    {
        return $this->pdo->query(
            'SELECT class_rules.school_year_id, classes.code,
                    class_rules.weeks_half_year_1, class_rules.weeks_half_year_2
             FROM class_rules INNER JOIN classes ON classes.id = class_rules.class_id'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Setzt Faktor und Wochen einer Klasse im Schuljahr; legt die Zeile an, wenn sie fehlt.
     *
     * @param array{supervision_factor: float, weeks_total: float, weeks_half_year_1: float, weeks_half_year_2: float} $values
     */
    public function save(int $classId, int $schoolYearId, array $values, int $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                'INSERT INTO class_rules (school_year_id, class_id, supervision_factor, weeks_total, weeks_half_year_1, weeks_half_year_2)
                 VALUES (:jahr, :klasse, :supervision_factor, :weeks_total, :weeks_half_year_1, :weeks_half_year_2)
                 ON DUPLICATE KEY UPDATE supervision_factor = VALUES(supervision_factor), weeks_total = VALUES(weeks_total),
                     weeks_half_year_1 = VALUES(weeks_half_year_1), weeks_half_year_2 = VALUES(weeks_half_year_2)'
            )->execute(['jahr' => $schoolYearId, 'klasse' => $classId] + $values);
            (new AuditLogRepository($this->pdo))->log($userId, 'update_rule', 'class', $classId);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
