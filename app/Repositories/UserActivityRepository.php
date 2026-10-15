<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class UserActivityRepository
{
    public function create(
        int $userId,
        string $actionType,
        ?string $requestMethod,
        ?string $requestPath,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $metadata = null
    ): void {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO user_activity_logs
                    (user_id, action_type, request_method, request_path, entity_type, entity_id, metadata_json)
                 VALUES
                    (:user_id, :action_type, :request_method, :request_path, :entity_type, :entity_id, :metadata_json)'
            );

            $metadataJson = $metadata === null
                ? null
                : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $stmt->execute([
                'user_id' => $userId,
                'action_type' => $actionType,
                'request_method' => $requestMethod,
                'request_path' => $requestPath,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metadata_json' => $metadataJson === false ? null : $metadataJson,
            ]);
        } catch (PDOException $e) {
            // Logging نباید Request اصلی کاربر را fail کند؛ مخصوصاً قبل از اجرای Migration.
        }
    }

    public function recentForUser(int $userId, int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));

        try {
            $stmt = Database::connection()->prepare(
                'SELECT action_type, request_method, request_path, entity_type, entity_id, metadata_json, created_at
                 FROM user_activity_logs
                 WHERE user_id = :user_id
                 ORDER BY id DESC
                 LIMIT ' . $limit
            );
            $stmt->execute(['user_id' => $userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
