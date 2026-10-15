<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class BaleChannelRepository
{
    public function findByUserId(int $userId): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT user_id, chat_id, is_active, connected_at, updated_at
                 FROM bale_user_channels WHERE user_id = :user_id LIMIT 1'
            );
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function createLinkToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO bale_link_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, :expires_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public function consumeLinkToken(string $tokenHash): ?int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'SELECT id, user_id FROM bale_link_tokens
                 WHERE token_hash = :token_hash
                   AND used_at IS NULL
                   AND expires_at > NOW()
                 ORDER BY id DESC
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmt->execute(['token_hash' => $tokenHash]);
            $row = $stmt->fetch();

            if (!$row) {
                $pdo->rollBack();
                return null;
            }

            $update = $pdo->prepare('UPDATE bale_link_tokens SET used_at = NOW() WHERE id = :id');
            $update->execute(['id' => $row['id']]);
            $pdo->commit();

            return (int) $row['user_id'];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function connect(int $userId, string $chatId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO bale_user_channels (user_id, chat_id, is_active)
             VALUES (:user_id, :chat_id, 1)
             ON DUPLICATE KEY UPDATE
                chat_id = VALUES(chat_id),
                is_active = 1,
                connected_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute(['user_id' => $userId, 'chat_id' => $chatId]);
    }
}
