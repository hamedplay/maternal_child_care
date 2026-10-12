<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class PregnancyWeekRepository
{
    /**
     * اطلاعات یک هفته‌ی مشخص از بارداری رو برمی‌گردونه.
     * اگه دیتابیس در دسترس نبود یا هفته پیدا نشد، null برمی‌گردونه
     * تا صفحه‌ی اصلی به‌جای کرش، حالت fallback نشون بده.
     *
     * @return array{week_number:int, fetus_size_comparison:?string, fetus_length_cm:?string,
     *               fetus_weight_g:?string, development_description:?string,
     *               mother_changes:?string, medical_tips:?string}|null
     */
    public function findByWeek(int $weekNumber): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT week_number, fetus_size_comparison, fetus_length_cm, fetus_weight_g,
                        development_description, mother_changes, medical_tips
                 FROM pregnancy_weeks
                 WHERE week_number = :week
                 LIMIT 1'
            );
            $stmt->execute(['week' => $weekNumber]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            error_log('PregnancyWeekRepository::findByWeek خطا: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * تعداد کل هفته‌های ثبت‌شده (برای بخش آمار).
     * اگه دیتابیس در دسترس نبود، ۴۰ (مقدار شناخته‌شده‌ی فعلی) fallback می‌شه.
     */
    public function totalWeeks(): int
    {
        try {
            $count = Database::connection()
                ->query('SELECT COUNT(*) FROM pregnancy_weeks')
                ->fetchColumn();

            return (int) $count;
        } catch (PDOException $e) {
            error_log('PregnancyWeekRepository::totalWeeks خطا: ' . $e->getMessage());
            return 40;
        }
    }
}