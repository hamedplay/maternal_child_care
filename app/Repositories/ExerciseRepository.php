<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

/**
 * ریپازیتوری جدول pregnancy_exercises.
 * مسئول واکشی ورزش‌های مناسب دوران بارداری، به تفکیک سه‌ماهه یا هفته.
 */
class ExerciseRepository
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
     * لیبل فارسی دسته‌بندی ورزش‌ها.
     */
    public const CATEGORY_LABELS = [
        'cardio'       => 'هوازی',
        'strength'     => 'قدرتی',
        'flexibility'  => 'انعطاف‌پذیری',
        'pelvic_floor' => 'کف لگن',
        'breathing'    => 'تنفسی',
    ];

    /**
     * لیبل فارسی سطح شدت ورزش.
     */
    public const INTENSITY_LABELS = [
        'light'         => 'سبک',
        'moderate'      => 'متوسط',
        'moderate_high' => 'متوسط به بالا',
    ];

    /**
     * همه‌ی ورزش‌ها، مرتب‌شده بر اساس هفته‌ی شروع.
     */
    public function all(): array
    {
        try {
            $stmt = Database::connection()->query(
                'SELECT * FROM pregnancy_exercises ORDER BY week_start ASC, id ASC'
            );

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * ورزش‌های مرتبط با یک سه‌ماهه‌ی خاص.
     * مقدار 'all' یعنی ورزش‌هایی که در کل دوران بارداری مناسب‌اند (trimester = 'all' در دیتابیس)
     * ورودی نامعتبر → همه‌ی رکوردها برگردونده می‌شه.
     */
    public function findByTrimester(string $trimester): array
    {
        if (!array_key_exists($trimester, self::TRIMESTER_LABELS)) {
            return $this->all();
        }

        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_exercises WHERE trimester = :trimester ORDER BY week_start ASC, id ASC'
            );
            $stmt->execute(['trimester' => $trimester]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * ورزش‌های مرتبط با یک هفته‌ی مشخص از بارداری.
     * رکوردهایی که week_start/week_end ندارن (NULL) یعنی برای کل دوران مناسب‌اند و همیشه لحاظ می‌شن.
     */
    public function findRelevantForWeek(int $week): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_exercises
                 WHERE (week_start IS NULL OR week_start <= :week)
                   AND (week_end IS NULL OR week_end >= :week)
                 ORDER BY week_start ASC, id ASC'
            );
            $stmt->execute(['week' => $week]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * یک ورزش با شناسه‌ی مشخص.
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_exercises WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
