<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class OtpRepository
{
    public function create(string $phone, string $codeHash, string $expiresAt, ?string $ip): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO otp_verifications (phone, code_hash, expires_at, ip_address)
             VALUES (:phone, :code_hash, :expires_at, :ip)'
        );
        $stmt->execute([
            'phone'      => $phone,
            'code_hash'  => $codeHash,
            'expires_at' => $expiresAt,
            'ip'         => $ip,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * آخرین کدِ هنوز معتبرِ (تایید‌نشده، invalidate‌نشده، منقضی‌نشده) یک شماره.
     */
    public function findLatestActiveForPhone(string $phone): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM otp_verifications
                 WHERE phone = :phone AND is_verified = 0 AND is_invalidated = 0 AND expires_at > NOW()
                 ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute(['phone' => $phone]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function incrementAttempts(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE otp_verifications SET attempts = attempts + 1 WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function markVerified(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE otp_verifications SET is_verified = 1, verified_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * کدهای فعال قبلیِ یک شماره رو باطل می‌کنه تا وقتی کد جدید ساخته می‌شه،
     * کدهای قبلی دیگه قابل استفاده نباشن.
     */
    public function invalidateActiveForPhone(string $phone): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE otp_verifications SET is_invalidated = 1
             WHERE phone = :phone AND is_verified = 0 AND is_invalidated = 0'
        );
        $stmt->execute(['phone' => $phone]);
    }

    /**
     * تعداد درخواست‌های کد برای یک شماره توی بازه‌ی زمانی اخیر (برای محدودسازی سوءاستفاده).
     */
    public function countRecentForPhone(string $phone, int $windowSeconds): int
    {
        $sinceTime = date('Y-m-d H:i:s', time() - $windowSeconds);

        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS cnt FROM otp_verifications WHERE phone = :phone AND created_at > :since'
        );
        $stmt->execute(['phone' => $phone, 'since' => $sinceTime]);
        $row = $stmt->fetch();

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * زمان آخرین درخواست کد برای یک شماره (برای اعمال فاصله‌ی حداقلی بین دو درخواست).
     */
    public function findLastCreatedAtForPhone(string $phone): ?string
    {
        $stmt = Database::connection()->prepare(
            'SELECT created_at FROM otp_verifications WHERE phone = :phone ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['phone' => $phone]);
        $row = $stmt->fetch();

        return $row['created_at'] ?? null;
    }
}
