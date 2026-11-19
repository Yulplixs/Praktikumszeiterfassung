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
}
