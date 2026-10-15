<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class UserProfileRepository
{
    public function findByUserId(int $userId): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT user_id, stage, birth_date, pregnancy_due_date,
                        child_name, child_birth_date, child_gender, created_at, updated_at
                 FROM user_profiles
                 WHERE user_id = :user_id
                 LIMIT 1'
            );
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function upsert(int $userId, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO user_profiles
                (user_id, stage, birth_date, pregnancy_due_date, child_name, child_birth_date, child_gender)
             VALUES
                (:user_id, :stage, :birth_date, :pregnancy_due_date, :child_name, :child_birth_date, :child_gender)
             ON DUPLICATE KEY UPDATE
                stage = VALUES(stage),
                birth_date = VALUES(birth_date),
                pregnancy_due_date = VALUES(pregnancy_due_date),
                child_name = VALUES(child_name),
                child_birth_date = VALUES(child_birth_date),
                child_gender = VALUES(child_gender),
                updated_at = CURRENT_TIMESTAMP'
        );

        $stmt->execute([
            'user_id' => $userId,
            'stage' => $data['stage'],
            'birth_date' => $data['birth_date'],
            'pregnancy_due_date' => $data['pregnancy_due_date'],
            'child_name' => $data['child_name'],
            'child_birth_date' => $data['child_birth_date'],
            'child_gender' => $data['child_gender'],
        ]);
    }
}
