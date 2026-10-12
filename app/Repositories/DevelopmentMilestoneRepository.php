<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class DevelopmentMilestoneRepository
{
    /**
     * مراحل تکاملی مرتبط با یک سن مشخص (بر حسب ماه) رو برمی‌گردونه؛
     * یعنی رکوردهایی که این سن داخل بازه‌ی age_months_start تا age_months_end قرار می‌گیره.
     *
     * @return array<int, array{milestone_name:string, category:string, description:string,
     *                          age_months_start:int, age_months_end:int, milestone_type:string,
     *                          warning_signs:?string}>
     */
    public function findByAgeMonths(int $ageMonths): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT milestone_name, category, description, age_months_start, age_months_end,
                        milestone_type, warning_signs
                 FROM development_milestones
                 WHERE age_months_start <= :age_start AND age_months_end >= :age_end
                 ORDER BY category, age_months_start'
            );
            $stmt->execute([
                'age_start' => $ageMonths,
                'age_end'   => $ageMonths,
            ]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('DevelopmentMilestoneRepository::findByAgeMonths خطا: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * بیشترین سنی (ماه) که تو جدول ثبت شده - برای محدود کردن select سن استفاده می‌شه.
     * اگه دیتابیس در دسترس نبود، ۶۰ (مقدار شناخته‌شده‌ی فعلی) fallback می‌شه.
     */
    public function maxAgeInMonths(): int
    {
        try {
            $max = Database::connection()->query('SELECT MAX(age_months_end) FROM development_milestones')->fetchColumn();
            return ($max !== false && $max !== null) ? (int) $max : 60;
        } catch (PDOException $e) {
            error_log('DevelopmentMilestoneRepository::maxAgeInMonths خطا: ' . $e->getMessage());
            return 60;
        }
    }
}