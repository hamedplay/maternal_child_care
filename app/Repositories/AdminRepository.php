<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class AdminRepository
{
    public function isAdmin(int $userId): bool
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT 1 FROM admin_users WHERE user_id = :user_id LIMIT 1'
            );
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function grant(int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO admin_users (user_id) VALUES (:user_id)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)'
        );
        $stmt->execute(['user_id' => $userId]);
    }

    /** @return array<string,int> */
    public function dashboardCounts(): array
    {
        $counts = [
            'users' => 0,
            'doctor_new' => 0,
            'support_open' => 0,
            'reminders_pending' => 0,
            'ai_messages' => 0,
        ];

        $queries = [
            'users' => 'SELECT COUNT(*) FROM users',
            'doctor_new' => "SELECT COUNT(*) FROM doctor_messages WHERE status = 'new'",
            'support_open' => "SELECT COUNT(*) FROM support_messages WHERE status IN ('pending','in_progress')",
            'reminders_pending' => "SELECT COUNT(*) FROM reminders WHERE status = 'pending'",
            'ai_messages' => 'SELECT COUNT(*) FROM ai_messages',
        ];

        foreach ($queries as $key => $sql) {
            try {
                $counts[$key] = (int) Database::connection()->query($sql)->fetchColumn();
            } catch (PDOException $e) {
                $counts[$key] = 0;
            }
        }

        return $counts;
    }

    public function latestUsers(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        try {
            return Database::connection()->query(
                'SELECT u.id, u.phone, u.full_name, u.is_active, u.last_login_at, u.created_at,
                        p.stage, p.pregnancy_due_date, p.child_name, p.child_birth_date, p.child_gender
                 FROM users u
                 LEFT JOIN user_profiles p ON p.user_id = u.id
                 ORDER BY u.id DESC
                 LIMIT ' . $limit
            )->fetchAll();
        } catch (PDOException $e) {
            try {
                return Database::connection()->query(
                    'SELECT id, phone, full_name, is_active, last_login_at, created_at
                     FROM users ORDER BY id DESC LIMIT ' . $limit
                )->fetchAll();
            } catch (PDOException $inner) {
                return [];
            }
        }
    }

    public function doctorMessages(int $limit = 100): array
    {
        $limit = max(1, min($limit, 300));
        try {
            return Database::connection()->query(
                'SELECT d.id, d.user_id, d.subject, d.message, d.status, d.reply_text, d.replied_at,
                        d.created_at, u.phone, u.full_name
                 FROM doctor_messages d
                 INNER JOIN users u ON u.id = d.user_id
                 ORDER BY FIELD(d.status, \'new\', \'reviewed\', \'replied\'), d.id DESC
                 LIMIT ' . $limit
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function replyDoctorMessage(int $id, string $reply): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE doctor_messages
             SET reply_text = :reply, replied_at = NOW(), status = 'replied', updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute(['reply' => $reply, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function markDoctorReviewed(int $id): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE doctor_messages SET status = 'reviewed', updated_at = NOW()
             WHERE id = :id AND status = 'new'"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function supportMessages(int $limit = 100): array
    {
        $limit = max(1, min($limit, 300));
        try {
            return Database::connection()->query(
                "SELECT id, full_name, email, phone_number, message_type, message, status, created_at
                 FROM support_messages
                 ORDER BY FIELD(status, 'pending', 'in_progress', 'resolved'), id DESC
                 LIMIT {$limit}"
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function updateSupportStatus(int $id, string $status): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE support_messages SET status = :status WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function reminders(int $limit = 100): array
    {
        $limit = max(1, min($limit, 300));
        try {
            return Database::connection()->query(
                'SELECT r.id, r.user_id, r.reminder_type, r.title, r.event_at, r.remind_at,
                        r.status, r.attempts, r.sent_at, r.last_error, u.phone, u.full_name
                 FROM reminders r
                 INNER JOIN users u ON u.id = r.user_id
                 ORDER BY r.id DESC
                 LIMIT ' . $limit
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function aiMessages(int $limit = 100): array
    {
        $limit = max(1, min($limit, 300));
        try {
            return Database::connection()->query(
                'SELECT m.id, m.role, m.content, m.model, m.created_at,
                        c.id AS conversation_id, c.user_id, u.phone, u.full_name
                 FROM ai_messages m
                 INNER JOIN ai_conversations c ON c.id = m.conversation_id
                 LEFT JOIN users u ON u.id = c.user_id
                 ORDER BY m.id DESC
                 LIMIT ' . $limit
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
