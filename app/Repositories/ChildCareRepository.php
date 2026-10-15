<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

/**
 * Data Access صفحات کودک برای تغذیه، مکمل، واکسیناسیون و مشکلات شایع.
 * منطق نمایش و انتخاب سن در Page layer باقی می‌ماند.
 */
class ChildCareRepository
{
    public function findNutritionByAge(int $ageMonths): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT nutrition_stage, age_start_months, age_end_months, recommended_foods,
                        foods_to_avoid, meal_frequency, tips
                 FROM child_nutrition
                 WHERE age_start_months <= :age_start AND age_end_months >= :age_end
                 ORDER BY age_start_months DESC
                 LIMIT 1'
            );
            $stmt->execute(['age_start' => $ageMonths, 'age_end' => $ageMonths]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            error_log('ChildCareRepository::findNutritionByAge خطا: ' . $e->getMessage());
            return null;
        }
    }

    public function findSupplementsByAge(int $ageMonths): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT supplement_name, description, benefits, dosage, age_start_months,
                        age_end_months, daily_requirement, food_sources, precautions, is_essential
                 FROM child_supplements
                 WHERE age_start_months <= :age_start
                   AND (age_end_months IS NULL OR age_end_months >= :age_end)
                 ORDER BY is_essential DESC, supplement_name'
            );
            $stmt->execute(['age_start' => $ageMonths, 'age_end' => $ageMonths]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('ChildCareRepository::findSupplementsByAge خطا: ' . $e->getMessage());
            return [];
        }
    }

    public function findVaccinationsByAge(int $ageMonths): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT vaccine_name, description, dose_number, age_in_months,
                        age_min_months, age_max_months, precautions
                 FROM vaccination_schedule
                 WHERE age_min_months <= :age_min AND age_max_months >= :age_max
                 ORDER BY age_in_months, dose_number'
            );
            $stmt->execute(['age_min' => $ageMonths, 'age_max' => $ageMonths]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('ChildCareRepository::findVaccinationsByAge خطا: ' . $e->getMessage());
            return [];
        }
    }

    public function allVaccinations(): array
    {
        try {
            return Database::connection()->query(
                'SELECT vaccine_name, description, dose_number, age_in_months,
                        age_min_months, age_max_months, precautions
                 FROM vaccination_schedule
                 ORDER BY age_in_months, dose_number, id'
            )->fetchAll();
        } catch (PDOException $e) {
            error_log('ChildCareRepository::allVaccinations خطا: ' . $e->getMessage());
            return [];
        }
    }

    public function allConditions(): array
    {
        try {
            return Database::connection()->query(
                'SELECT condition_name, description, symptoms, home_care, when_to_see_doctor, age_group
                 FROM common_conditions
                 ORDER BY id'
            )->fetchAll();
        } catch (PDOException $e) {
            error_log('ChildCareRepository::allConditions خطا: ' . $e->getMessage());
            return [];
        }
    }

    public function maxAgeInMonths(): int
    {
        try {
            $sql = 'SELECT GREATEST(
                        COALESCE((SELECT MAX(age_end_months) FROM child_nutrition), 0),
                        COALESCE((SELECT MAX(COALESCE(age_end_months, age_start_months)) FROM child_supplements), 0),
                        COALESCE((SELECT MAX(age_max_months) FROM vaccination_schedule), 0),
                        COALESCE((SELECT MAX(age_months_end) FROM development_milestones), 0),
                        COALESCE((SELECT MAX(age_in_months) FROM growth_standards), 0)
                    )';
            $max = Database::connection()->query($sql)->fetchColumn();
            return ($max !== false && $max !== null && (int) $max > 0) ? (int) $max : 60;
        } catch (PDOException $e) {
            error_log('ChildCareRepository::maxAgeInMonths خطا: ' . $e->getMessage());
            return 60;
        }
    }
}
