<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class AuditLogRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function log(int $userId, string $action, string $entityType, int $entityId): void
    {
        $this->pdo->prepare(
            'INSERT INTO audit_log (user_id, action, entity_type, entity_id, created_at)
             VALUES (:user_id, :action, :entity_type, :entity_id, UTC_TIMESTAMP())'
        )->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }
}
