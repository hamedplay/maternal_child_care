<?php

namespace App\Services;

use App\Repositories\AiKnowledgeRepository;

/**
 * مسئول orchestration فرآیند Retrieval برای RAG.
 */
class KnowledgeRetriever
{
    private AiKnowledgeRepository $knowledgeRepository;
    private QueryPreprocessor $queryPreprocessor;

    public function __construct(
        ?AiKnowledgeRepository $knowledgeRepository = null,
        ?QueryPreprocessor $queryPreprocessor = null
    ) {
        $this->knowledgeRepository = $knowledgeRepository ?? new AiKnowledgeRepository();
        $this->queryPreprocessor = $queryPreprocessor ?? new QueryPreprocessor();
    }

    public function retrieve(string $question, int $perTableLimit = 2): array
    {
        $analysis = $this->queryPreprocessor->analyze($question);
        $keywords = $analysis['keywords'];
        $ageMonths = $analysis['age_months'];
        $gender = $analysis['gender'];
        $domain = $analysis['domain'];

        if (empty($keywords) && $ageMonths === null) {
            return [];
        }

        $generalBlocks = $this->retrieveGeneralBlocks($keywords, $perTableLimit);
        $childBlocks = $this->retrieveChildBlocks($keywords, $perTableLimit, $ageMonths, $gender);
        $pregnancyBlocks = $this->retrievePregnancyBlocks($keywords, $perTableLimit);

        if ($domain === 'child') {
            $blocks = array_merge($childBlocks, $generalBlocks);
        } elseif ($domain === 'pregnancy') {
            $blocks = array_merge($pregnancyBlocks, $generalBlocks);
        } else {
            $blocks = array_merge($generalBlocks, $childBlocks, $pregnancyBlocks);
        }

        return array_slice($blocks, 0, 12);
    }

    private function retrieveGeneralBlocks(array $keywords, int $limit): array
    {
        if (empty($keywords)) {
            return [];
        }

        return array_merge(
            $this->retrieveFaqs($keywords, $limit),
            $this->retrieveArticles($keywords, $limit)
        );
    }

    private function retrieveChildBlocks(array $keywords, int $limit, ?int $ageMonths, ?string $gender): array
    {
        $blocks = [];

        if (!empty($keywords)) {
            $blocks = array_merge($blocks, $this->retrieveCommonConditions($keywords, $limit));
        }

        $blocks = array_merge($blocks, $this->retrieveChildNutrition($keywords, $limit, $ageMonths));
        $blocks = array_merge($blocks, $this->retrieveChildSupplements($keywords, $limit, $ageMonths));
        $blocks = array_merge($blocks, $this->retrieveVaccinations($keywords, $limit, $ageMonths));
        $blocks = array_merge($blocks, $this->retrieveMilestones($keywords, $limit, $ageMonths));
        $blocks = array_merge($blocks, $this->retrieveGrowth($ageMonths, $gender));

        return $blocks;
    }

    private function retrievePregnancyBlocks(array $keywords, int $limit): array
    {
        if (empty($keywords)) {
            return [];
        }

        return array_merge(
            $this->retrieveNutrition($keywords, $limit),
            $this->retrieveWeeks($keywords, $limit),
            $this->retrieveWarningSigns($keywords, $limit),
            $this->retrieveExercises($keywords, $limit),
            $this->retrieveSupplements($keywords, $limit)
        );
    }

    private function retrieveFaqs(array $keywords, int $limit): array
    {
        $rows = $this->scoreAndLimit(
            $this->knowledgeRepository->findFaqs($keywords),
            $keywords,
            ['question', 'answer'],
            $limit
        );

        return array_map(
            static fn(array $row): string => "[سوال متداول تایید‌شده‌ی سایت] پرسش: {$row['question']} | پاسخ رسمی: {$row['answer']}",
            $rows
        );
    }

    private function retrieveArticles(array $keywords, int $limit): array
    {
        $rows = $this->scoreAndLimit(
            $this->knowledgeRepository->findArticles($keywords),
            $keywords,
            ['title', 'content'],
            $limit
        );

        $blocks = [];
        foreach ($rows as $row) {
            $plainText = $this->htmlToPlainText((string) $row['content']);
            $snippet = $this->extractRelevantSnippet($plainText, $keywords);

            $line = '[مقاله سایت';
            if (!empty($row['category_name'])) {
                $line .= " - {$row['category_name']}";
            }
            $line .= "] {$row['title']}: {$snippet}";

            if (!empty($row['cover_image'])) {
                $line .= " | لینک: {$row['cover_image']}";
            }

            $blocks[] = $line;
        }

        return $blocks;
    }

    private function retrieveCommonConditions(array $keywords, int $limit): array
    {
        if (empty($keywords)) {
            return [];
        }

        $rows = $this->scoreAndLimit(
            $this->knowledgeRepository->findCommonConditions($keywords),
            $keywords,
            ['condition_name', 'description', 'symptoms', 'home_care', 'when_to_see_doctor', 'age_group'],
            $limit
        );

        $blocks = [];
        foreach ($rows as $row) {
            $blocks[] = "[بیماری/عارضه شایع کودک - {$row['age_group']}] {$row['condition_name']}: {$row['description']} "
                . "| علائم: {$row['symptoms']} | مراقبت خانگی: {$row['home_care']} "
                . "| زمان مراجعه به پزشک: {$row['when_to_see_doctor']}";
        }

        return $blocks;
    }

    private function retrieveChildNutrition(array $keywords, int $limit, ?int $ageMonths): array
    {
        if ($ageMonths !== null) {
            $rows = $this->knowledgeRepository->findChildNutritionByAge($ageMonths, $limit);
        } elseif (!empty($keywords)) {
            $rows = $this->scoreAndLimit(
                $this->knowledgeRepository->findChildNutritionByKeywords($keywords),
                $keywords,
                ['nutrition_stage', 'recommended_foods', 'foods_to_avoid', 'tips'],
                $limit
            );
        } else {
            return [];
        }

        $blocks = [];
        foreach ($rows as $row) {
            $line = "[تغذیه کودک، {$row['age_start_months']} تا {$row['age_end_months']} ماهگی] {$row['nutrition_stage']}: "
                . "مواد توصیه‌شده: {$row['recommended_foods']} | وعده‌ها: {$row['meal_frequency']}";
            if (!empty($row['foods_to_avoid'])) {
                $line .= " | موارد پرهیز: {$row['foods_to_avoid']}";
            }
            if (!empty($row['tips'])) {
                $line .= " | نکته: {$row['tips']}";
            }
            $blocks[] = $line;
        }

        return $blocks;
    }

    private function retrieveChildSupplements(array $keywords, int $limit, ?int $ageMonths): array
    {
        if ($ageMonths !== null) {
            $rows = array_slice($this->knowledgeRepository->findChildSupplementsByAge($ageMonths), 0, $limit);
        } elseif (!empty($keywords)) {
            $rows = $this->scoreAndLimit(
                $this->knowledgeRepository->findChildSupplementsByKeywords($keywords),
                $keywords,
                ['supplement_name', 'description', 'benefits', 'food_sources', 'precautions'],
                $limit
            );
        } else {
            return [];
        }

        $blocks = [];
        foreach ($rows as $row) {
            $ageEnd = $row['age_end_months'] === null ? 'به بعد' : 'تا ' . $row['age_end_months'] . ' ماهگی';
            $line = "[مکمل کودک، از {$row['age_start_months']} ماهگی {$ageEnd}] {$row['supplement_name']}: {$row['description']} "
                . "| فواید: {$row['benefits']}";
            if (!empty($row['food_sources'])) {
                $line .= " | منابع غذایی: {$row['food_sources']}";
            }
            if (!empty($row['precautions'])) {
                $line .= " | احتیاط: {$row['precautions']}";
            }
            $blocks[] = $line;
        }

        return $blocks;
    }

    private function retrieveVaccinations(array $keywords, int $limit, ?int $ageMonths): array
    {
        if ($ageMonths !== null) {
            $rows = array_slice($this->knowledgeRepository->findVaccinationsByAge($ageMonths), 0, $limit);
        } elseif (!empty($keywords)) {
            $rows = $this->scoreAndLimit(
                $this->knowledgeRepository->findVaccinationsByKeywords($keywords),
                $keywords,
                ['vaccine_name', 'description', 'precautions'],
                $limit
            );
        } else {
            return [];
        }

        $blocks = [];
        foreach ($rows as $row) {
            $line = "[واکسیناسیون کودک، {$row['age_in_months']} ماهگی] {$row['vaccine_name']} - دوز {$row['dose_number']}";
            if (!empty($row['description'])) {
                $line .= ": {$row['description']}";
            }
            if (!empty($row['precautions'])) {
                $line .= " | احتیاط: {$row['precautions']}";
            }
            $blocks[] = $line;
        }

        return $blocks;
    }

    private function retrieveMilestones(array $keywords, int $limit, ?int $ageMonths): array
    {
        if ($ageMonths !== null) {
            $rows = $this->knowledgeRepository->findMilestonesByAge($ageMonths, $limit);
        } elseif (!empty($keywords)) {
            $rows = $this->scoreAndLimit(
                $this->knowledgeRepository->findMilestonesByKeywords($keywords),
                $keywords,
                ['milestone_name', 'description', 'warning_signs'],
                $limit
            );
        } else {
            return [];
        }

        $blocks = [];
        foreach ($rows as $row) {
            $line = "[تکامل کودک، {$row['age_months_start']} تا {$row['age_months_end']} ماهگی] {$row['milestone_name']} ({$row['category']}): {$row['description']}";
            if (!empty($row['warning_signs'])) {
                $line .= " | علائم هشدار: {$row['warning_signs']}";
            }
            $blocks[] = $line;
        }

        return $blocks;
    }

    private function retrieveGrowth(?int $ageMonths, ?string $gender): array
    {
        if ($ageMonths === null) {
            return [];
        }

        $rows = $this->knowledgeRepository->findGrowthByAgeAndGender($ageMonths, $gender);
        $blocks = [];

        foreach ($rows as $row) {
            $genderLabel = $row['gender'] === 'boy' ? 'پسر' : 'دختر';
            $blocks[] = "[استاندارد رشد، {$genderLabel}، {$ageMonths} ماهگی] "
                . "قد میانگین: {$row['length_p50']} سانتی‌متر (محدوده طبیعی {$row['length_p3']} تا {$row['length_p97']}) | "
                . "وزن میانگین: {$row['weight_p50']} کیلوگرم (محدوده طبیعی {$row['weight_p3']} تا {$row['weight_p97']}) | "
                . "دور سر میانگین: {$row['head_p50']} سانتی‌متر";
        }

        return $blocks;
    }

    private function retrieveNutrition(array $keywords, int $limit): array
    {
        $rows = $this->scoreAndLimit(
            $this->knowledgeRepository->findPregnancyNutrition($keywords),
            $keywords,
            ['nutrient_name', 'benefits', 'food_sources'],
            $limit
        );

        return array_map(
            static fn(array $row): string => "[تغذیه بارداری] {$row['nutrient_name']}: {$row['benefits']} | نیاز روزانه: {$row['daily_requirement']} | منابع غذایی: {$row['food_sources']}",
            $rows
        );
    }

    private function retrieveWeeks(array $keywords, int $limit): array
    {
        $rows = $this->scoreAndLimit(
            $this->knowledgeRepository->findPregnancyWeeks($keywords),
            $keywords,
            ['development_description', 'mother_changes', 'medical_tips'],
            $limit
        );

        return array_map(
            static fn(array $row): string => "[هفته {$row['week_number']} بارداری] رشد جنین: {$row['development_description']} | تغییرات مادر: {$row['mother_changes']} | توصیه پزشکی: {$row['medical_tips']}",
            $rows
        );
    }

    private function retrieveWarningSigns(array $keywords, int $limit): array
    {
        $rows = $this->scoreAndLimit(
            $this->knowledgeRepository->findPregnancyWarningSigns($keywords),
            $keywords,
            ['symptom_name', 'description', 'possible_causes', 'action_required'],
            $limit
        );

        return array_map(
            static fn(array $row): string => "[علامت هشدار بارداری - سطح {$row['urgency_level']}] {$row['symptom_name']}: {$row['description']} | اقدام لازم: {$row['action_required']}",
            $rows
        );
    }

    private function retrieveExercises(array $keywords, int $limit): array
    {
        $rows = $this->scoreAndLimit(
            $this->knowledgeRepository->findPregnancyExercises($keywords),
            $keywords,
            ['exercise_name', 'description', 'benefits', 'how_to_do'],
            $limit
        );

        $blocks = [];
        foreach ($rows as $row) {
            $line = "[ورزش بارداری] {$row['exercise_name']}: {$row['description']} | فواید: {$row['benefits']} | نحوه انجام: {$row['how_to_do']}";
            if (!empty($row['precautions'])) {
                $line .= " | احتیاط: {$row['precautions']}";
            }
            $blocks[] = $line;
        }

        return $blocks;
    }

    private function retrieveSupplements(array $keywords, int $limit): array
    {
        $rows = $this->scoreAndLimit(
            $this->knowledgeRepository->findPregnancySupplements($keywords),
            $keywords,
            ['supplement_name', 'importance', 'precautions'],
            $limit
        );

        $blocks = [];
        foreach ($rows as $row) {
            $line = "[مکمل بارداری] {$row['supplement_name']}: {$row['importance']}";
            if (!empty($row['precautions'])) {
                $line .= " | احتیاط: {$row['precautions']}";
            }
            $blocks[] = $line;
        }

        return $blocks;
    }

    private function scoreAndLimit(array $rows, array $keywords, array $textColumns, int $limit): array
    {
        $scored = [];

        foreach ($rows as $row) {
            $haystack = '';
            foreach ($textColumns as $column) {
                $haystack .= ' ' . ($row[$column] ?? '');
            }

            $score = 0;
            foreach ($keywords as $keyword) {
                if (mb_stripos($haystack, $keyword) !== false) {
                    $score++;
                }
            }

            if ($score > 0) {
                $scored[] = ['row' => $row, 'score' => $score];
            }
        }

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice(array_column($scored, 'row'), 0, $limit);
    }

    private function htmlToPlainText(string $html): string
    {
        $html = (string) preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    private function extractRelevantSnippet(string $text, array $keywords, int $context = 300): string
    {
        $bestPos = null;
        foreach ($keywords as $keyword) {
            $pos = mb_stripos($text, $keyword);
            if ($pos !== false && ($bestPos === null || $pos < $bestPos)) {
                $bestPos = $pos;
            }
        }

        $totalLength = mb_strlen($text);
        if ($bestPos === null) {
            $snippet = mb_substr($text, 0, $context * 2);
            return $snippet . ($totalLength > $context * 2 ? '...' : '');
        }

        $start = max(0, $bestPos - $context);
        $length = $context * 2;
        $snippet = mb_substr($text, $start, $length);

        if ($start > 0) {
            $snippet = '...' . $snippet;
        }
        if ($start + $length < $totalLength) {
            $snippet .= '...';
        }

        return $snippet;
    }
}
