<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * جستجوی هوشمند روی جدول‌های مرتبط با بارداری و کودک، برای ساخت context
 * جهت ارسال به LLM (الگوی RAG سبک بدون نیاز به vector database).
 *
 * ویژگی‌ها:
 * - امتیازدهی به نتایج بر اساس تعداد کلمات match‌شده (به‌جای انتخاب تصادفی)
 * - حذف پسوندهای رایج فارسی (light stemming) تا "خستگی‌ها" هم با "خستگی" match بشه
 * - دیکشنری مترادف برای چند اصطلاح رایج
 * - تشخیص سن (از روی «۶ ماهه»، «دو ساله» و ...) برای جدول‌های سن‌محور
 * - تشخیص جنسیت (پسر/دختر) برای استاندارد رشد
 */
class AiKnowledgeRepository
{
    /** کلمات بی‌اهمیت فارسی که از جستجو حذف می‌شن */
    private const STOPWORDS = [
        'و', 'در', 'به', 'از', 'که', 'را', 'با', 'یا', 'برای', 'این', 'آن',
        'چیست', 'چیه', 'چطور', 'چگونه', 'آیا', 'می', 'است', 'هست', 'کدام',
        'چه', 'یک', 'من', 'شما', 'باید', 'کنم', 'کنیم', 'داره', 'دارم',
    ];

    /** پسوندهایی که برای پیدا کردن ریشه‌ی کلمه حذف می‌شن (طولانی‌ترین اول) */
    private const SUFFIXES = ['های', 'ها', 'یی', 'ات', 'ان', 'ی'];

    /**
     * مترادف‌های رایج — هر وقت اصطلاح جدیدی دیدی که کاربرها زیاد استفاده می‌کنن
     * ولی توی دیتابیس با کلمه‌ی دیگه‌ای ثبت شده، همینجا اضافه‌ش کن.
     */
    private const SYNONYMS = [
        'تهوع'   => ['استفراغ', 'دلشوره', 'دل‌آشوبه'],
        'خستگی'  => ['بیحالی', 'ضعف', 'بی‌حالی'],
        'کمردرد' => ['کمر'],
        'سردرد'  => ['میگرن'],
        'گرفتگی' => ['کرامپ', 'انقباض', 'اسپاسم'],
        'یبوست'  => ['دل‌درد'],
        'خواب'   => ['بی‌خوابی'],
    ];

    /** معادل چند عدد رایج فارسی — برای تشخیص سن وقتی به‌جای رقم، با کلمه نوشته شده */
    private const WORD_NUMBERS = [
        'یک' => 1, 'دو' => 2, 'سه' => 3, 'چهار' => 4, 'پنج' => 5, 'شش' => 6,
        'هفت' => 7, 'هشت' => 8, 'نه' => 9, 'ده' => 10, 'یازده' => 11, 'دوازده' => 12,
        'هجده' => 18, 'بیست' => 20,
    ];

    /**
     * @return string[] تکه‌های متنی آماده برای پاس‌دادن به LlmService::ask()
     */
    public function search(string $question, int $perTableLimit = 2): array
    {
        $keywords  = $this->extractKeywords($question);
        $ageMonths = $this->extractAgeInMonths($question);
        $gender    = $this->extractGender($question);

        if (empty($keywords) && $ageMonths === null) {
            return [];
        }

        $blocks = [];

        if (!empty($keywords)) {
            $blocks = array_merge($blocks, $this->searchFaqs($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->searchArticles($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->searchNutrition($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->searchWeeks($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->searchWarningSigns($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->searchExercises($keywords, $perTableLimit));
            $blocks = array_merge($blocks, $this->searchSupplements($keywords, $perTableLimit));
        }

        // این دوتا حتی بدون کلمه‌کلیدی معنادار هم صرفاً با تشخیص سن کار می‌کنن
        $blocks = array_merge($blocks, $this->searchMilestones($keywords, $perTableLimit, $ageMonths));
        $blocks = array_merge($blocks, $this->searchGrowth($ageMonths, $gender));

        // سقف کلی تا context خیلی بزرگ نشه (هزینه و سرعت پاسخ)
        return array_slice($blocks, 0, 12);
    }

    // ------------------------------------------------------------------
    // استخراج اطلاعات از متن سوال
    // ------------------------------------------------------------------

    private function extractKeywords(string $question): array
    {
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $question);
        $words = preg_split('/\s+/u', trim((string) $clean));

        $keywords = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) < 3 || in_array($word, self::STOPWORDS, true)) {
                continue;
            }

            $keywords[] = $word;

            $stem = $this->stem($word);
            if ($stem !== $word && mb_strlen($stem) >= 3) {
                $keywords[] = $stem;
            }

            if (isset(self::SYNONYMS[$word])) {
                $keywords = array_merge($keywords, self::SYNONYMS[$word]);
            }
        }

        return array_values(array_unique($keywords));
    }

    /** حذف پسوندهای رایج فارسی برای رسیدن به ریشه‌ی احتمالی کلمه */
    private function stem(string $word): string
    {
        foreach (self::SUFFIXES as $suffix) {
            $len = mb_strlen($suffix);
            if (mb_strlen($word) > $len + 2 && mb_substr($word, -$len) === $suffix) {
                return mb_substr($word, 0, -$len);
            }
        }
        return $word;
    }

    /** تشخیص سن به ماه از روی متن سوال (مثل «۶ ماهه»، «دو ساله»، «۱۸ ماهگی») */
    private function extractAgeInMonths(string $question): ?int
    {
        $normalized = strtr($question, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        if (preg_match('/(\d+)\s*(ماهگی|ماهه|ماه)/u', $normalized, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/(\d+)\s*(ساله|سالگی|سال)/u', $normalized, $m)) {
            return ((int) $m[1]) * 12;
        }

        foreach (self::WORD_NUMBERS as $word => $num) {
            if (preg_match('/' . preg_quote($word, '/') . '\s*(ماهگی|ماهه|ماه)/u', $normalized)) {
                return $num;
            }
            if (preg_match('/' . preg_quote($word, '/') . '\s*(ساله|سالگی|سال)/u', $normalized)) {
                return $num * 12;
            }
        }

        return null;
    }

    /** تشخیص جنسیت کودک از روی متن سوال */
    private function extractGender(string $question): ?string
    {
        if (mb_strpos($question, 'دختر') !== false) {
            return 'girl';
        }
        if (mb_strpos($question, 'پسر') !== false) {
            return 'boy';
        }
        return null;
    }

    // ------------------------------------------------------------------
    // ابزار کمکی مشترک برای جستجوی متنی + امتیازدهی
    // ------------------------------------------------------------------

    /** ساخت شرط WHERE برای LIKE روی چند ستون × چند کلمه، با پارامترهای امن (بدون SQL injection) */
    private function buildLikeClause(array $columns, array $keywords, array &$params): string
    {
        $conditions = [];
        foreach ($columns as $column) {
            foreach ($keywords as $i => $keyword) {
                $param = 'kw_' . preg_replace('/[^a-z_]/i', '', $column) . '_' . $i;
                $conditions[] = "$column LIKE :$param";
                $params[$param] = '%' . $keyword . '%';
            }
        }
        return implode(' OR ', $conditions);
    }

    /**
     * از بین یه مجموعه رکورد (candidate pool)، بر اساس تعداد کلمات match‌شده
     * توی ستون‌های متنی مشخص‌شده امتیاز می‌ده و نزولی مرتب می‌کنه.
     * این جایگزین ترتیب تصادفی/بدون‌رتبه‌ی نسخه‌ی قبلیه.
     */
    private function scoreAndSort(array $rows, array $keywords, array $textColumns): array
    {
        $scored = [];
        foreach ($rows as $row) {
            $haystack = '';
            foreach ($textColumns as $col) {
                $haystack .= ' ' . ($row[$col] ?? '');
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

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_column($scored, 'row');
    }

    // ------------------------------------------------------------------
    // جستجوی هر جدول
    // ------------------------------------------------------------------

    private function searchFaqs(array $keywords, int $limit): array
    {
        try {
            $params  = [];
            $columns = ['question', 'answer'];
            $where   = $this->buildLikeClause($columns, $keywords, $params);

            $stmt = Database::connection()->prepare(
                "SELECT question, answer FROM faqs WHERE is_active = 1 AND ($where) LIMIT 20"
            );
            $stmt->execute($params);

            $rows = $this->scoreAndSort($stmt->fetchAll(), $keywords, $columns);
            $rows = array_slice($rows, 0, $limit);

            $blocks = [];
            foreach ($rows as $row) {
                $blocks[] = "[سوال متداول تایید‌شده‌ی سایت] پرسش: {$row['question']} | پاسخ رسمی: {$row['answer']}";
            }
            return $blocks;
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::searchFaqs خطا: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * جستجو توی جدول articles. محتوای هر مقاله HTML و معمولاً خیلی طولانیه،
     * پس به‌جای پاس‌دادن کل مقاله به LLM، فقط تیتر + بخشی از متن که نزدیک‌ترین
     * به کلمه‌کلیدی جستجوشده هست (به همراه اسم دسته‌بندی و لینک) رو برمی‌گردونیم.
     */
    private function searchArticles(array $keywords, int $limit): array
    {
        try {
            $params  = [];
            $columns = ['title', 'content'];
            $where   = $this->buildLikeClause($columns, $keywords, $params);

            $stmt = Database::connection()->prepare(
                "SELECT a.title, a.content, a.cover_image, c.name AS category_name
                 FROM articles a
                 LEFT JOIN categories c ON c.id = a.category
                 WHERE a.is_active = 1 AND ($where)
                 LIMIT 20"
            );
            $stmt->execute($params);

            $rows = $this->scoreAndSort($stmt->fetchAll(), $keywords, $columns);
            $rows = array_slice($rows, 0, $limit);

            $blocks = [];
            foreach ($rows as $row) {
                $plainText = $this->htmlToPlainText((string) $row['content']);
                $snippet   = $this->extractRelevantSnippet($plainText, $keywords);

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
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::searchArticles خطا: ' . $e->getMessage());
            return [];
        }
    }

    /** حذف تگ‌های HTML و decode کردن entity‌ها، برای رسیدن به متن ساده‌ی مقاله */
    private function htmlToPlainText(string $html): string
    {
        // اسکریپت/استایل اصلاً محتوای متنی معنادار ندارن، قبل از strip_tags حذفشون می‌کنیم
        $html = (string) preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $html);

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    /**
     * به‌جای فرستادن کل متن مقاله، فقط بخشی از متن که نزدیک اولین کلمه‌کلیدی
     * match‌شده هست رو (به همراه کمی متن قبل و بعدش برای حفظ context) برمی‌گردونه.
     * اگه هیچ کلمه‌ای پیدا نشد (مثلا match فقط روی تیتر بوده)، از ابتدای متن می‌بریم.
     */
    private function extractRelevantSnippet(string $text, array $keywords, int $context = 300): string
    {
        $bestPos = null;
        foreach ($keywords as $keyword) {
            $pos = mb_stripos($text, $keyword);
            if ($pos !== false && ($bestPos === null || $pos < $bestPos)) {
                $bestPos = $pos;
            }
        }

        $totalLen = mb_strlen($text);

        if ($bestPos === null) {
            $snippet = mb_substr($text, 0, $context * 2);
            return $snippet . ($totalLen > $context * 2 ? '...' : '');
        }

        $start  = max(0, $bestPos - $context);
        $length = $context * 2;
        $snippet = mb_substr($text, $start, $length);

        if ($start > 0) {
            $snippet = '...' . $snippet;
        }
        if ($start + $length < $totalLen) {
            $snippet .= '...';
        }

        return $snippet;
    }

    private function searchNutrition(array $keywords, int $limit): array
    {
        try {
            $params  = [];
            $columns = ['nutrient_name', 'benefits', 'food_sources'];
            $where   = $this->buildLikeClause($columns, $keywords, $params);

            $stmt = Database::connection()->prepare(
                "SELECT nutrient_name, benefits, daily_requirement, food_sources
                 FROM pregnancy_nutrition WHERE $where LIMIT 20"
            );
            $stmt->execute($params);

            $rows = $this->scoreAndSort($stmt->fetchAll(), $keywords, $columns);
            $rows = array_slice($rows, 0, $limit);

            $blocks = [];
            foreach ($rows as $row) {
                $blocks[] = "[تغذیه] {$row['nutrient_name']}: {$row['benefits']} | نیاز روزانه: {$row['daily_requirement']} | منابع غذایی: {$row['food_sources']}";
            }
            return $blocks;
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::searchNutrition خطا: ' . $e->getMessage());
            return [];
        }
    }

    private function searchWeeks(array $keywords, int $limit): array
    {
        try {
            $params  = [];
            $columns = ['development_description', 'mother_changes', 'medical_tips'];
            $where   = $this->buildLikeClause($columns, $keywords, $params);

            $stmt = Database::connection()->prepare(
                "SELECT week_number, development_description, mother_changes, medical_tips
                 FROM pregnancy_weeks WHERE $where LIMIT 20"
            );
            $stmt->execute($params);

            $rows = $this->scoreAndSort($stmt->fetchAll(), $keywords, $columns);
            $rows = array_slice($rows, 0, $limit);

            $blocks = [];
            foreach ($rows as $row) {
                $blocks[] = "[هفته {$row['week_number']} بارداری] رشد جنین: {$row['development_description']} | تغییرات مادر: {$row['mother_changes']} | توصیه پزشکی: {$row['medical_tips']}";
            }
            return $blocks;
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::searchWeeks خطا: ' . $e->getMessage());
            return [];
        }
    }

    private function searchWarningSigns(array $keywords, int $limit): array
    {
        try {
            $params  = [];
            $columns = ['symptom_name', 'description', 'possible_causes', 'action_required'];
            $where   = $this->buildLikeClause($columns, $keywords, $params);

            $stmt = Database::connection()->prepare(
                "SELECT symptom_name, description, action_required, urgency_level
                 FROM pregnancy_warning_signs WHERE $where LIMIT 20"
            );
            $stmt->execute($params);

            $rows = $this->scoreAndSort($stmt->fetchAll(), $keywords, $columns);
            $rows = array_slice($rows, 0, $limit);

            $blocks = [];
            foreach ($rows as $row) {
                $blocks[] = "[علامت هشدار - سطح {$row['urgency_level']}] {$row['symptom_name']}: {$row['description']} | اقدام لازم: {$row['action_required']}";
            }
            return $blocks;
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::searchWarningSigns خطا: ' . $e->getMessage());
            return [];
        }
    }

    private function searchExercises(array $keywords, int $limit): array
    {
        try {
            $params  = [];
            $columns = ['exercise_name', 'description', 'benefits', 'how_to_do'];
            $where   = $this->buildLikeClause($columns, $keywords, $params);

            $stmt = Database::connection()->prepare(
                "SELECT exercise_name, description, benefits, how_to_do, precautions
                 FROM pregnancy_exercises WHERE $where LIMIT 20"
            );
            $stmt->execute($params);

            $rows = $this->scoreAndSort($stmt->fetchAll(), $keywords, $columns);
            $rows = array_slice($rows, 0, $limit);

            $blocks = [];
            foreach ($rows as $row) {
                $line = "[ورزش بارداری] {$row['exercise_name']}: {$row['description']} | فواید: {$row['benefits']} | نحوه انجام: {$row['how_to_do']}";
                if (!empty($row['precautions'])) {
                    $line .= " | احتیاط: {$row['precautions']}";
                }
                $blocks[] = $line;
            }
            return $blocks;
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::searchExercises خطا: ' . $e->getMessage());
            return [];
        }
    }

    private function searchSupplements(array $keywords, int $limit): array
    {
        try {
            $params  = [];
            $columns = ['supplement_name', 'importance', 'precautions'];
            $where   = $this->buildLikeClause($columns, $keywords, $params);

            $stmt = Database::connection()->prepare(
                "SELECT supplement_name, importance, dosage, precautions
                 FROM pregnancy_supplements WHERE $where LIMIT 20"
            );
            $stmt->execute($params);

            $rows = $this->scoreAndSort($stmt->fetchAll(), $keywords, $columns);
            $rows = array_slice($rows, 0, $limit);

            $blocks = [];
            foreach ($rows as $row) {
                $line = "[مکمل] {$row['supplement_name']}: {$row['importance']} | دوز مرجع: {$row['dosage']}";
                if (!empty($row['precautions'])) {
                    $line .= " | احتیاط: {$row['precautions']}";
                }
                $blocks[] = $line;
            }
            return $blocks;
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::searchSupplements خطا: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * development_milestones سن‌محوره: اگه سن از سوال استخراج شده باشه،
     * مستقیم بر اساس بازه‌ی سنی جستجو می‌کنیم (دقیق‌تر از کلمه‌کلیدی).
     * اگه سن مشخص نبود، به‌صورت fallback با کلمه‌کلیدی جستجو می‌کنیم.
     */
    private function searchMilestones(array $keywords, int $limit, ?int $ageMonths): array
    {
        if ($ageMonths === null && empty($keywords)) {
            return [];
        }

        try {
            $conn = Database::connection();

            if ($ageMonths !== null) {
                $stmt = $conn->prepare(
                    'SELECT milestone_name, category, description, age_months_start, age_months_end, warning_signs
                     FROM development_milestones
                     WHERE age_months_start <= :age AND age_months_end >= :age
                     ORDER BY category
                     LIMIT :limit'
                );
                $stmt->bindValue(':age', $ageMonths, PDO::PARAM_INT);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll();
            } else {
                $params  = [];
                $columns = ['milestone_name', 'description', 'warning_signs'];
                $where   = $this->buildLikeClause($columns, $keywords, $params);

                $stmt = $conn->prepare(
                    "SELECT milestone_name, category, description, age_months_start, age_months_end, warning_signs
                     FROM development_milestones WHERE $where LIMIT 20"
                );
                $stmt->execute($params);
                $rows = $this->scoreAndSort($stmt->fetchAll(), $keywords, $columns);
                $rows = array_slice($rows, 0, $limit);
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
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::searchMilestones خطا: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * growth_standards کاملاً عددیه (بدون متن قابل جستجو)، پس فقط وقتی
     * سن از سوال استخراج شده باشه فعال می‌شه. اگه جنسیت مشخص نبود، هر دو رو برمی‌گردونه.
     */
    private function searchGrowth(?int $ageMonths, ?string $gender): array
    {
        if ($ageMonths === null) {
            return [];
        }

        try {
            $conn    = Database::connection();
            $genders = $gender !== null ? [$gender] : ['boy', 'girl'];
            $blocks  = [];

            foreach ($genders as $g) {
                $stmt = $conn->prepare(
                    'SELECT gender, length_p3, length_p50, length_p97,
                            weight_p3, weight_p50, weight_p97, head_p50
                     FROM growth_standards
                     WHERE gender = :gender AND age_in_months = :age
                     LIMIT 1'
                );
                $stmt->execute(['gender' => $g, 'age' => $ageMonths]);
                $row = $stmt->fetch();

                if (!$row) {
                    continue;
                }

                $genderLabel = $g === 'boy' ? 'پسر' : 'دختر';
                $blocks[] = "[استاندارد رشد، {$genderLabel}، {$ageMonths} ماهگی] "
                    . "قد میانگین: {$row['length_p50']} سانتی‌متر (محدوده طبیعی {$row['length_p3']} تا {$row['length_p97']}) | "
                    . "وزن میانگین: {$row['weight_p50']} کیلوگرم (محدوده طبیعی {$row['weight_p3']} تا {$row['weight_p97']}) | "
                    . "دور سر میانگین: {$row['head_p50']} سانتی‌متر";
            }

            return $blocks;
        } catch (PDOException $e) {
            error_log('AiKnowledgeRepository::searchGrowth خطا: ' . $e->getMessage());
            return [];
        }
    }
}