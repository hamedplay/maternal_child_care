<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

/**
 * Data Access مربوط به تاریخچه گفتگوهای AI.
 */
class AiConversationRepository
{
    public function getOrCreateConversation(string $sessionKey, ?int $userId): ?int
    {
        try {
            $conn = Database::connection();

            $stmt = $conn->prepare(
                'SELECT id, user_id FROM ai_conversations WHERE session_key = :session_key LIMIT 1'
            );
            $stmt->execute(['session_key' => $sessionKey]);
            $row = $stmt->fetch();

            if ($row) {
                if ($userId !== null && $row['user_id'] === null) {
                    $update = $conn->prepare(
                        'UPDATE ai_conversations SET user_id = :user_id WHERE id = :id'
                    );
                    $update->execute([
                        'user_id' => $userId,
                        'id' => (int) $row['id'],
                    ]);
                }

                return (int) $row['id'];
            }

            $insert = $conn->prepare(
                'INSERT INTO ai_conversations (user_id, session_key)
                 VALUES (:user_id, :session_key)'
            );
            $insert->execute([
                'user_id' => $userId,
                'session_key' => $sessionKey,
            ]);

            return (int) $conn->lastInsertId();
        } catch (PDOException $e) {
            error_log('AiConversationRepository::getOrCreateConversation خطا: ' . $e->getMessage());
            return null;
        }
    }

    public function addMessage(
        int $conversationId,
        string $role,
        string $content,
        ?string $model = null
    ): bool {
        if (!in_array($role, ['user', 'assistant'], true)) {
            return false;
        }

        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO ai_messages (conversation_id, role, content, model)
                 VALUES (:conversation_id, :role, :content, :model)'
            );
            $stmt->execute([
                'conversation_id' => $conversationId,
                'role' => $role,
                'content' => $content,
                'model' => $model,
            ]);

            return true;
        } catch (PDOException $e) {
            error_log('AiConversationRepository::addMessage خطا: ' . $e->getMessage());
            return false;
        }
    }
}
