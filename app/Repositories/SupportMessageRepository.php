<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class SupportMessageRepository
{
    /**
     * یک پیام پشتیبانی جدید ثبت می‌کنه.
     *
     * @param array{full_name:?string, email:?string, phone_number:?string,
     *              message_type:string, message:string} $data
     */
    public function create(array $data): bool
    {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO support_messages (full_name, email, phone_number, message_type, message)
                 VALUES (:full_name, :email, :phone_number, :message_type, :message)'
            );

            return $stmt->execute([
                'full_name'    => $data['full_name'] ?? null,
                'email'        => $data['email'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'message_type' => $data['message_type'],
                'message'      => $data['message'],
            ]);
        } catch (PDOException $e) {
            error_log('SupportMessageRepository::create خطا: ' . $e->getMessage());
            return false;
        }
    }
}
