<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ImportBatchRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Protokolliert einen Importversuch. Wird außerhalb der Import-Transaktion aufgerufen,
     * damit auch abgelehnte Importe erhalten bleiben.
     */
    public function log(
        int $schoolYearId,
        int $importedBy,
        string $fileChecksum,
        string $status,
        int $rowCount,
        int $errorCount
    ): int {
        $this->pdo->prepare(
            'INSERT INTO import_batches
                (school_year_id, imported_by, file_checksum, status, row_count, error_count, created_at)
             VALUES (:school_year_id, :imported_by, :file_checksum, :status, :row_count, :error_count, UTC_TIMESTAMP())'
        )->execute([
            'school_year_id' => $schoolYearId,
            'imported_by' => $importedBy,
            'file_checksum' => $fileChecksum,
            'status' => $status,
            'row_count' => $rowCount,
            'error_count' => $errorCount,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
