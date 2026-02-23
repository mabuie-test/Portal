<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AuditLogService
{
    public static function record(int $userId, string $action, string $targetType, int $targetId, string $justification, string $ip, array $metadata): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO audit_logs (user_id, action, target_type, target_id, justification, ip, timestamp, metadata) VALUES (:user_id,:action,:target_type,:target_id,:justification,:ip,NOW(),:metadata)');
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'justification' => $justification,
            'ip' => $ip,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }
}
