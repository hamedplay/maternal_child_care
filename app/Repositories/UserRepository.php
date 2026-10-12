<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class UserRepository
{
    public function findByPhone(string $phone): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM users WHERE phone = :phone LIMIT 1'
            );
            $stmt->execute(['phone' => $phone]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM users WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * کاربر جدید با همین شماره می‌سازه (اولین ورود = ثبت‌نام خودکار).
     */
    public function create(string $phone): array
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (phone) VALUES (:phone)'
        );
        $stmt->execute(['phone' => $phone]);

        $id = (int) Database::connection()->lastInsertId();

        return $this->findById($id) ?? ['id' => $id, 'phone' => $phone];
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET last_login_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function updateName(int $id, string $name): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET full_name = :name WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'name' => $name]);
    }
}