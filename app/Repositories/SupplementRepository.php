<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

/**
 * ریپازیتوری جدول pregnancy_supplements.
 * مسئول واکشی مکمل‌های موردنیاز دوران بارداری، به تفکیک سه‌ماهه یا هفته.
 */
class SupplementRepository
{
    /**
     * لیبل فارسی سه‌ماهه‌ها برای نمایش در صفحه.
     */
    public const TRIMESTER_LABELS = [
        'all'    => 'کل دوران بارداری',
        'first'  => 'سه‌ماهه اول',
        'second' => 'سه‌ماهه دوم',
        'third'  => 'سه‌ماهه سوم',
    ];

    /**
     * همه‌ی مکمل‌ها، مرتب‌شده به‌صورت ضروری‌ها اول و بعد بر اساس هفته‌ی شروع.
     */
    public function all(): array
    {
        try {
            $stmt = Database::connection()->query(
                'SELECT * FROM pregnancy_supplements
                 ORDER BY is_essential DESC, week_start ASC, id ASC'
            );

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * مکمل‌های مرتبط با یک سه‌ماهه‌ی خاص.
     * مقدار 'all' یعنی مکمل‌هایی که در کل دوران بارداری مصرف می‌شوند (trimester = 'all' در دیتابیس)
     * ورودی نامعتبر → همه‌ی رکوردها برگردونده می‌شه.
     */
    public function findByTrimester(string $trimester): array
    {
        if (!array_key_exists($trimester, self::TRIMESTER_LABELS)) {
            return $this->all();
        }

        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_supplements
                 WHERE trimester = :trimester
                 ORDER BY is_essential DESC, week_start ASC, id ASC'
            );
            $stmt->execute(['trimester' => $trimester]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * مکمل‌های مرتبط با یک هفته‌ی مشخص از بارداری
     * (یعنی هفته بین week_start و week_end رکورد قرار داشته باشه).
     */
    public function findRelevantForWeek(int $week): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_supplements
                 WHERE (week_start IS NULL OR week_start <= :week)
                   AND (week_end IS NULL OR week_end >= :week)
                 ORDER BY is_essential DESC, week_start ASC, id ASC'
            );
            $stmt->execute(['week' => $week]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * یک مکمل با شناسه‌ی مشخص.
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_supplements WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
