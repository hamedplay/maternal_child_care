<?php

namespace App\Services;

/**
 * مسئول تمام پیش‌پردازش‌های سؤال کاربر قبل از Retrieval.
 * این کلاس هیچ دسترسی‌ای به دیتابیس ندارد.
 */
class QueryPreprocessor
{
    private const STOPWORDS = [
        'و', 'در', 'به', 'از', 'که', 'را', 'با', 'یا', 'برای', 'این', 'آن',
        'چیست', 'چیه', 'چطور', 'چگونه', 'آیا', 'می', 'است', 'هست', 'کدام',
        'چه', 'یک', 'من', 'شما', 'باید', 'کنم', 'کنیم', 'داره', 'دارم',
    ];

    private const SUFFIXES = ['های', 'ها', 'یی', 'ات', 'ان', 'ی'];

    private const SYNONYMS = [
        'تهوع' => ['استفراغ', 'دلشوره', 'دل آشوبه'],
        'خستگی' => ['بیحالی', 'ضعف', 'بی حالی'],
        'کمردرد' => ['کمر درد', 'کمر'],
        'سردرد' => ['میگرن'],
        'گرفتگی' => ['کرامپ', 'انقباض', 'اسپاسم'],
        'یبوست' => ['دل درد'],
        'خواب' => ['بی خوابی'],
        'نوزاد' => ['شیرخوار'],
        'شیرخوار' => ['نوزاد'],
        'کودک' => ['بچه'],
        'بچه' => ['کودک'],
        'واکسن' => ['واکسیناسیون'],
        'واکسیناسیون' => ['واکسن'],
        'تب' => ['تب دار', 'تب دارم'],
    ];

    private const WORD_NUMBERS = [
        'یک' => 1, 'دو' => 2, 'سه' => 3, 'چهار' => 4, 'پنج' => 5, 'شش' => 6,
        'هفت' => 7, 'هشت' => 8, 'نه' => 9, 'ده' => 10, 'یازده' => 11, 'دوازده' => 12,
        'سیزده' => 13, 'چهارده' => 14, 'پانزده' => 15, 'شانزده' => 16, 'هفده' => 17,
        'هجده' => 18, 'نوزده' => 19, 'بیست' => 20,
    ];

    private const PREGNANCY_MARKERS = [
        'بارداری', 'باردار', 'جنین', 'زایمان', 'تخمک', 'سه ماهه', 'تریمستر',
        'هفته بارداری', 'رحم', 'جفت', 'کیسه آب',
    ];

    private const CHILD_MARKERS = [
        'نوزاد', 'شیرخوار', 'کودک', 'بچه', 'طفل', 'واکسن', 'واکسیناسیون',
        'پوشک', 'کولیک', 'دندان درآوردن', 'رشد کودک', 'تکامل کودک',
    ];

    public function normalize(string $question): string
    {
        $normalized = strtr($question, [
            'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            "\u{200C}" => ' ',
        ]);

        $normalized = (string) preg_replace('/\s+/u', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * @return array{question:string, keywords:string[], age_months:?int, gender:?string, domain:string}
     */
    public function analyze(string $question): array
    {
        $normalized = $this->normalize($question);
        $domain = $this->detectDomain($normalized);

        return [
            'question' => $normalized,
            'keywords' => $this->extractKeywords($normalized),
            // عبارت‌هایی مثل «سه ماهه بارداری» نباید به سن سه‌ماهه کودک تبدیل شوند.
            'age_months' => $domain === 'pregnancy' ? null : $this->extractAgeInMonths($normalized),
            'gender' => $this->extractGender($normalized),
            'domain' => $domain,
        ];
    }

    /** @return string[] */
    public function extractKeywords(string $question): array
    {
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $this->normalize($question));
        $words = preg_split('/\s+/u', trim((string) $clean));

        $keywords = [];
        foreach ($words as $word) {
            $word = trim((string) $word);
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

    public function extractAgeInMonths(string $question): ?int
    {
        $normalized = $this->normalize($question);

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

    public function extractGender(string $question): ?string
    {
        $normalized = $this->normalize($question);

        if (mb_strpos($normalized, 'دختر') !== false) {
            return 'girl';
        }
        if (mb_strpos($normalized, 'پسر') !== false) {
            return 'boy';
        }

        return null;
    }

    private function detectDomain(string $question): string
    {
        $hasPregnancyMarker = $this->containsAny($question, self::PREGNANCY_MARKERS);
        $hasChildMarker = $this->containsAny($question, self::CHILD_MARKERS);

        if ($hasPregnancyMarker && !$hasChildMarker) {
            return 'pregnancy';
        }

        if ($hasChildMarker && !$hasPregnancyMarker) {
            return 'child';
        }

        return 'general';
    }

    /** @param string[] $markers */
    private function containsAny(string $text, array $markers): bool
    {
        foreach ($markers as $marker) {
            if (mb_stripos($text, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

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
}
