<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class DoctorMessageRepository
{
    public function create(int $userId, string $subject, string $message): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO doctor_messages (user_id, subject, message)
             VALUES (:user_id, :subject, :message)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'subject' => $subject,
            'message' => $message,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function recentForUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));

        try {
            $stmt = Database::connection()->prepare(
                'SELECT id, subject, message, status, created_at, updated_at
                 FROM doctor_messages
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
