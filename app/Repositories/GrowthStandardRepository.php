<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class GrowthStandardRepository
{
    /**
     * استاندارد رشد یک جنسیت و سن مشخص (بر حسب ماه) رو برمی‌گردونه.
     *
     * @return array{gender:string, age_in_months:int, length_p3:?string, length_p50:?string,
     *               length_p97:?string, weight_p3:?string, weight_p50:?string, weight_p97:?string,
     *               head_p3:?string, head_p50:?string, head_p97:?string}|null
     */
    public function findByGenderAndAge(string $gender, int $ageMonths): ?array
    {
        if (!in_array($gender, ['boy', 'girl'], true)) {
            return null;
        }

        try {
            $stmt = Database::connection()->prepare(
                'SELECT gender, age_in_months, length_p3, length_p50, length_p97,
                        weight_p3, weight_p50, weight_p97, head_p3, head_p50, head_p97
                 FROM growth_standards
                 WHERE gender = :gender AND age_in_months = :age
                 LIMIT 1'
            );
            $stmt->execute(['gender' => $gender, 'age' => $ageMonths]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            error_log('GrowthStandardRepository::findByGenderAndAge خطا: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * بیشترین سنی (ماه) که تو جدول ثبت شده - برای ساخت select سن استفاده می‌شه.
     * اگه دیتابیس در دسترس نبود، ۱۲ (مقدار شناخته‌شده‌ی فعلی) fallback می‌شه.
     */
    public function maxAgeInMonths(): int
    {
        try {
            $max = Database::connection()->query('SELECT MAX(age_in_months) FROM growth_standards')->fetchColumn();
            return ($max !== false && $max !== null) ? (int) $max : 12;
        } catch (PDOException $e) {
            error_log('GrowthStandardRepository::maxAgeInMonths خطا: ' . $e->getMessage());
            return 12;
        }
    }
}
