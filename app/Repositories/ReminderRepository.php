<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class ReminderRepository
{
    public function create(int $userId, array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO reminders (user_id, reminder_type, title, notes, event_at, remind_at)
             VALUES (:user_id, :reminder_type, :title, :notes, :event_at, :remind_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'reminder_type' => $data['reminder_type'],
            'title' => $data['title'],
            'notes' => $data['notes'],
            'event_at' => $data['event_at'],
            'remind_at' => $data['remind_at'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function recentForUser(int $userId, int $limit = 50): array
    {
        $limit = max(1, min($limit, 100));
        try {
            $stmt = Database::connection()->prepare(
                'SELECT id, reminder_type, title, notes, event_at, remind_at, status, sent_at, last_error, created_at
                 FROM reminders
                 WHERE user_id = :user_id
                 ORDER BY event_at DESC, id DESC
                 LIMIT ' . $limit
            );
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function findDue(int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        try {
            $stmt = Database::connection()->query(
                "SELECT r.id, r.user_id, r.reminder_type, r.title, r.notes, r.event_at, r.remind_at,
                        b.chat_id
                 FROM reminders r
                 INNER JOIN bale_user_channels b ON b.user_id = r.user_id AND b.is_active = 1
                 WHERE r.status = 'pending' AND r.remind_at <= NOW()
                 ORDER BY r.remind_at ASC, r.id ASC
                 LIMIT {$limit}"
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function markSent(int $id): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE reminders
             SET status = 'sent', sent_at = NOW(), attempts = attempts + 1, last_error = NULL
             WHERE id = :id AND status = 'pending'"
        );
        $stmt->execute(['id' => $id]);
    }

    public function markFailedAttempt(int $id, string $error, int $maxAttempts = 3): void
    {
        $maxAttempts = max(1, $maxAttempts);
        $stmt = Database::connection()->prepare(
            "UPDATE reminders
             SET attempts = attempts + 1,
                 last_error = :last_error,
                 status = CASE WHEN attempts + 1 >= :max_attempts THEN 'failed' ELSE 'pending' END
             WHERE id = :id AND status = 'pending'"
        );
        $stmt->execute([
            'last_error' => mb_substr($error, 0, 500),
            'max_attempts' => $maxAttempts,
            'id' => $id,
        ]);
    }

    public function cancelForUser(int $id, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE reminders SET status = 'cancelled'
             WHERE id = :id AND user_id = :user_id AND status = 'pending'"
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
