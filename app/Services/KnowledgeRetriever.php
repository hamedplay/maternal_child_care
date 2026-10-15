<?php

namespace App\Services;

use App\Repositories\AiKnowledgeRepository;

/**
 * مسئول orchestration فرآیند Retrieval برای RAG.
 *
 * این کلاس سؤال preprocess‌شده را می‌گیرد، candidateها را از Repository می‌خواند،
 * scoring/ranking انجام می‌دهد و در نهایت context blockهای آماده برای LlmService می‌سازد.
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

    /**
     * @return string[]
     */
    public function retrieve(string $question, int $perTableLimit = 2): array
    {
        $analysis = $this->queryPreprocessor->analyze($question);
        $keywords = $analysis['keywords'];
        $ageMonths = $analysis['age_months'];
        $gender = $analysis['gender'];

        if (empty($keywords) && $ageMonths === null) {
            return [];
        }

        $blocks = [];

        if (!empty($keywords)) {
            $blocks = array_merge($blocks, $this->retrieveFaqs($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->retrieveArticles($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->retrieveNutrition($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->retrieveWeeks($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->retrieveWarningSigns($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->retrieveExercises($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->retrieveSupplements($keywords, $perTableLimit));
        }

        $blocks = array_merge($blocks, $this->retrieveMilestones($keywords, $perTableLimit, $ageMonths));
        $blocks = array_merge($blocks, $this->retrieveGrowth($ageMonths, $gender));

        return array_slice($blocks, 0, 12);
    }

    /** @return string[] */
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

    /** @return string[] */
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

    /** @return string[] */
    private function retrieveNutrition(array $keywords, int $limit): array
    {
        $rows = $this->scoreAndLimit(
            $this->knowledgeRepository->findPregnancyNutrition($keywords),
            $keywords,
            ['nutrient_name', 'benefits', 'food_sources'],
            $limit
        );

        return array_map(
            static fn(array $row): string => "[تغذیه] {$row['nutrient_name']}: {$row['benefits']} | نیاز روزانه: {$row['daily_requirement']} | منابع غذایی: {$row['food_sources']}",
            $rows
        );
    }

    /** @return string[] */
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

    /** @return string[] */
    private function retrieveWarningSigns(array $keywords, int $limit): array
    {
        $rows = $this->scoreAndLimit(
            $this->knowledgeRepository->findPregnancyWarningSigns($keywords),
            $keywords,
            ['symptom_name', 'description', 'possible_causes', 'action_required'],
            $limit
        );

        return array_map(
            static fn(array $row): string => "[علامت هشدار - سطح {$row['urgency_level']}] {$row['symptom_name']}: {$row['description']} | اقدام لازم: {$row['action_required']}",
            $rows
        );
    }

    /** @return string[] */
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

    /** @return string[] */
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
            $line = "[مکمل] {$row['supplement_name']}: {$row['importance']} | دوز مرجع: {$row['dosage']}";
            if (!empty($row['precautions'])) {
                $line .= " | احتیاط: {$row['precautions']}";
            }
            $blocks[] = $line;
        }

        return $blocks;
    }

    /** @return string[] */
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

    /** @return string[] */
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

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param string[] $keywords
     * @param string[] $textColumns
     * @return array<int, array<string, mixed>>
     */
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
                $scored[] = [
                    'row' => $row,
                    'score' => $score,
                ];
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

    /** @param string[] $keywords */
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
