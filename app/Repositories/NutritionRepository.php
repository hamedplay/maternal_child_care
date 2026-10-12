<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * ریپازیتوری جدول pregnancy_nutrition.
 * مسئول واکشی مواد مغذی موردنیاز دوران بارداری، به تفکیک سه‌ماهه یا هفته.
 */
class NutritionRepository
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
     * همه‌ی مواد مغذی، مرتب‌شده بر اساس هفته‌ی شروع.
     */
    public function all(): array
    {
        try {
            $stmt = Database::connection()->query(
                'SELECT * FROM pregnancy_nutrition ORDER BY week_start ASC, id ASC'
            );

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * مواد مغذی مرتبط با یک سه‌ماهه‌ی خاص.
     * مقدار 'all' یعنی موادی که در کل دوران بارداری اهمیت دارند (trimester = 'all' در دیتابیس)
     * ورودی نامعتبر → همه‌ی رکوردها برگردونده می‌شه.
     */
    public function findByTrimester(string $trimester): array
    {
        if (!array_key_exists($trimester, self::TRIMESTER_LABELS)) {
            return $this->all();
        }

        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_nutrition WHERE trimester = :trimester ORDER BY week_start ASC, id ASC'
            );
            $stmt->execute(['trimester' => $trimester]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * مواد مغذی مرتبط با یک هفته‌ی مشخص از بارداری
     * (یعنی هفته بین week_start و week_end رکورد قرار داشته باشه).
     */
    public function findRelevantForWeek(int $week): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_nutrition
                 WHERE week_start <= :week AND week_end >= :week
                 ORDER BY week_start ASC, id ASC'
            );
            $stmt->execute(['week' => $week]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * یک ماده‌ی مغذی با شناسه‌ی مشخص.
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_nutrition WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
