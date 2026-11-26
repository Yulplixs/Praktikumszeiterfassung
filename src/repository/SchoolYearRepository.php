<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class SchoolYearRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /** @return list<array{id: int, name: string}> neuestes Schuljahr zuerst */
    public function findAll(): array
    {
        return $this->pdo->query('SELECT id, name FROM school_years ORDER BY start_date DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findNameById(int $id): ?string
    {
        $statement = $this->pdo->prepare('SELECT name FROM school_years WHERE id = ?');
        $statement->execute([$id]);
        $name = $statement->fetchColumn();

        return $name === false ? null : (string) $name;
    }
}
