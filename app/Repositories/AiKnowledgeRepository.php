<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Data Access مخصوص منابع دانشی AI.
 *
 * این Repository فقط Query اجرا می‌کند و Row خام برمی‌گرداند.
 * Preprocessing، scoring، ranking و context building در Service layer انجام می‌شوند.
 */
class AiKnowledgeRepository
{
    public function findFaqs(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT question, answer FROM faqs WHERE is_active = 1 AND (%s) LIMIT %d',
            ['question', 'answer'],
            $keywords,
            $candidateLimit,
            'findFaqs'
        );
    }

    public function findArticles(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT a.title, a.content, a.cover_image, c.name AS category_name
             FROM articles a
             LEFT JOIN categories c ON c.id = a.category
             WHERE a.is_active = 1 AND (%s)
             LIMIT %d',
            ['a.title', 'a.content'],
            $keywords,
            $candidateLimit,
            'findArticles'
        );
    }

    public function findPregnancyNutrition(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT nutrient_name, benefits, daily_requirement, food_sources
             FROM pregnancy_nutrition WHERE %s LIMIT %d',
            ['nutrient_name', 'benefits', 'food_sources'],
            $keywords,
            $candidateLimit,
            'findPregnancyNutrition'
        );
    }

    public function findPregnancyWeeks(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT week_number, development_description, mother_changes, medical_tips
             FROM pregnancy_weeks WHERE %s LIMIT %d',
            ['development_description', 'mother_changes', 'medical_tips'],
            $keywords,
            $candidateLimit,
            'findPregnancyWeeks'
        );
    }

    public function findPregnancyWarningSigns(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT symptom_name, description, possible_causes, action_required, urgency_level
             FROM pregnancy_warning_signs WHERE %s LIMIT %d',
            ['symptom_name', 'description', 'possible_causes', 'action_required'],
            $keywords,
            $candidateLimit,
            'findPregnancyWarningSigns'
        );
    }

    public function findPregnancyExercises(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT exercise_name, description, benefits, how_to_do, precautions
             FROM pregnancy_exercises WHERE %s LIMIT %d',
            ['exercise_name', 'description', 'benefits', 'how_to_do'],
            $keywords,
            $candidateLimit,
            'findPregnancyExercises'
        );
    }

    public function findPregnancySupplements(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT supplement_name, importance, dosage, precautions
             FROM pregnancy_supplements WHERE %s LIMIT %d',
            ['supplement_name', 'importance', 'precautions'],
            $keywords,
            $candidateLimit,
            'findPregnancySupplements'
        );
    }

    public function findChildNutritionByAge(int $ageMonths, int $limit = 5): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT nutrition_stage, age_start_months, age_end_months, recommended_foods,
                        foods_to_avoid, meal_frequency, tips
                 FROM child_nutrition
                 WHERE age_start_months <= :age AND age_end_months >= :age
                 ORDER BY age_start_months
                 LIMIT :limit'
            );
            $stmt->bindValue(':age', $ageMonths, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::findChildNutritionByAge خطا: ' . $e->getMessage());
            return [];
        }
    }

    public function findChildNutritionByKeywords(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT nutrition_stage, age_start_months, age_end_months, recommended_foods,
                    foods_to_avoid, meal_frequency, tips
             FROM child_nutrition WHERE %s LIMIT %d',
            ['nutrition_stage', 'recommended_foods', 'foods_to_avoid', 'tips'],
            $keywords,
            $candidateLimit,
            'findChildNutritionByKeywords'
        );
    }

    public function findChildSupplementsByAge(int $ageMonths, int $limit = 10): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT supplement_name, description, benefits, dosage, age_start_months,
                        age_end_months, daily_requirement, food_sources, precautions, is_essential
                 FROM child_supplements
                 WHERE age_start_months <= :age
                   AND (age_end_months IS NULL OR age_end_months >= :age)
                 ORDER BY is_essential DESC, age_start_months
                 LIMIT :limit'
            );
            $stmt->bindValue(':age', $ageMonths, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::findChildSupplementsByAge خطا: ' . $e->getMessage());
            return [];
        }
    }

    public function findChildSupplementsByKeywords(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT supplement_name, description, benefits, dosage, age_start_months,
                    age_end_months, daily_requirement, food_sources, precautions, is_essential
             FROM child_supplements WHERE %s LIMIT %d',
            ['supplement_name', 'description', 'benefits', 'food_sources', 'precautions'],
            $keywords,
            $candidateLimit,
            'findChildSupplementsByKeywords'
        );
    }

    public function findCommonConditions(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT condition_name, description, symptoms, home_care, when_to_see_doctor, age_group
             FROM common_conditions WHERE %s LIMIT %d',
            ['condition_name', 'description', 'symptoms', 'home_care', 'when_to_see_doctor', 'age_group'],
            $keywords,
            $candidateLimit,
            'findCommonConditions'
        );
    }

    public function findVaccinationsByAge(int $ageMonths, int $limit = 10): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT vaccine_name, description, dose_number, age_in_months,
                        age_min_months, age_max_months, precautions
                 FROM vaccination_schedule
                 WHERE (age_min_months IS NOT NULL AND age_max_months IS NOT NULL
                        AND age_min_months <= :age AND age_max_months >= :age)
                    OR age_in_months = :age
                 ORDER BY age_in_months, dose_number
                 LIMIT :limit'
            );
            $stmt->bindValue(':age', $ageMonths, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::findVaccinationsByAge خطا: ' . $e->getMessage());
            return [];
        }
    }

    public function findVaccinationsByKeywords(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT vaccine_name, description, dose_number, age_in_months,
                    age_min_months, age_max_months, precautions
             FROM vaccination_schedule WHERE %s LIMIT %d',
            ['vaccine_name', 'description', 'precautions'],
            $keywords,
            $candidateLimit,
            'findVaccinationsByKeywords'
        );
    }

    public function findMilestonesByAge(int $ageMonths, int $limit = 2): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT milestone_name, category, description, age_months_start, age_months_end, warning_signs
                 FROM development_milestones
                 WHERE age_months_start <= :age AND age_months_end >= :age
                 ORDER BY category
                 LIMIT :limit'
            );
            $stmt->bindValue(':age', $ageMonths, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::findMilestonesByAge خطا: ' . $e->getMessage());
            return [];
        }
    }

    public function findMilestonesByKeywords(array $keywords, int $candidateLimit = 20): array
    {
        return $this->findByKeywords(
            'SELECT milestone_name, category, description, age_months_start, age_months_end, warning_signs
             FROM development_milestones WHERE %s LIMIT %d',
            ['milestone_name', 'description', 'warning_signs'],
            $keywords,
            $candidateLimit,
            'findMilestonesByKeywords'
        );
    }

    public function findGrowthByAgeAndGender(int $ageMonths, ?string $gender = null): array
    {
        try {
            $genders = $gender !== null ? [$gender] : ['boy', 'girl'];
            $rows = [];
            $conn = Database::connection();

            foreach ($genders as $currentGender) {
                $stmt = $conn->prepare(
                    'SELECT gender, age_in_months, length_p3, length_p50, length_p97,
                            weight_p3, weight_p50, weight_p97, head_p50
                     FROM growth_standards
                     WHERE gender = :gender AND age_in_months = :age
                     LIMIT 1'
                );
                $stmt->execute([
                    'gender' => $currentGender,
                    'age' => $ageMonths,
                ]);
                $row = $stmt->fetch();
                if ($row) {
                    $rows[] = $row;
                }
            }

            return $rows;
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::findGrowthByAgeAndGender خطا: ' . $e->getMessage());
            return [];
        }
    }

    private function findByKeywords(
        string $sqlTemplate,
        array $columns,
        array $keywords,
        int $candidateLimit,
        string $operation
    ): array {
        if (empty($keywords)) {
            return [];
        }

        try {
            $params = [];
            $where = $this->buildLikeClause($columns, $keywords, $params);
            $candidateLimit = max(1, min($candidateLimit, 100));
            $sql = sprintf($sqlTemplate, $where, $candidateLimit);

            $stmt = Database::connection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::' . $operation . ' خطا: ' . $e->getMessage());
            return [];
        }
    }

    private function buildLikeClause(array $columns, array $keywords, array &$params): string
    {
        $conditions = [];

        foreach ($columns as $columnIndex => $column) {
            foreach ($keywords as $keywordIndex => $keyword) {
                $param = 'kw_' . $columnIndex . '_' . $keywordIndex;
                $conditions[] = $column . ' LIKE :' . $param;
                $params[$param] = '%' . $keyword . '%';
            }
        }

        return implode(' OR ', $conditions);
    }
}
