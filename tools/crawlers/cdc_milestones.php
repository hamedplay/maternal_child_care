<?php

/**
 * CDC Developmental Milestones crawler.
 *
 * هدف این ابزار دریافت داده مرجع خام از صفحات رسمی CDC و تولید Export JSON است.
 * این فایل عمداً مستقیماً در دیتابیس محصول INSERT نمی‌کند؛ چون متن CDC انگلیسی است
 * و قبل از ورود به UI فارسی باید ترجمه/بازبینی محتوایی شود.
 *
 * Usage:
 *   php tools/crawlers/cdc_milestones.php
 *   php tools/crawlers/cdc_milestones.php /tmp/cdc_milestones.json
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This crawler must be executed from CLI.\n");
    exit(1);
}

if (!extension_loaded('curl') || !extension_loaded('dom')) {
    fwrite(STDERR, "Required PHP extensions: curl, dom\n");
    exit(1);
}

$pages = [
    2  => '2-months',
    4  => '4-months',
    6  => '6-months',
    9  => '9-months',
    12 => '1-year',
    15 => '15-months',
    18 => '18-months',
    24 => '2-years',
    30 => '30-months',
    36 => '3-years',
    48 => '4-years',
    60 => '5-years',
];

$categoryMap = [
    'social/emotional' => 'social_emotional',
    'language/communication' => 'language_communication',
    'cognitive' => 'cognitive',
    'movement/physical' => 'movement_physical',
];

$outputPath = $argv[1] ?? dirname(__DIR__, 2)
    . '/storage/crawlers/cdc_milestones_' . date('Ymd_His') . '.json';

$records = [];
$sourcePages = [];

foreach ($pages as $ageMonths => $slug) {
    $url = 'https://www.cdc.gov/act-early/milestones/' . $slug . '.html';
    fwrite(STDOUT, "Fetching {$url}\n");

    $html = fetchHtml($url);
    $pageRecords = parseMilestones($html, $url, $ageMonths, $categoryMap);

    if ($pageRecords === []) {
        fwrite(STDERR, "Warning: no milestones parsed from {$url}\n");
    }

    $records = array_merge($records, $pageRecords);
    $sourcePages[] = $url;

    // Crawl محترمانه؛ بین درخواست‌ها فاصله می‌گذاریم.
    usleep(500000);
}

$payload = [
    'export_version' => 1,
    'source' => [
        'name' => 'Centers for Disease Control and Prevention (CDC)',
        'base_url' => 'https://www.cdc.gov/act-early/milestones/',
        'source_pages' => $sourcePages,
        'attribution' => 'Source: Centers for Disease Control and Prevention (CDC).',
        'non_endorsement' => 'Use of CDC material does not imply endorsement by CDC, HHS, or the U.S. Government.',
        'availability_note' => 'The source material is available without charge on CDC.gov.',
    ],
    'crawled_at' => gmdate('c'),
    'records_count' => count($records),
    'records' => $records,
];

$directory = dirname($outputPath);
if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
    fwrite(STDERR, "Cannot create output directory: {$directory}\n");
    exit(1);
}

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false || file_put_contents($outputPath, $json . PHP_EOL) === false) {
    fwrite(STDERR, "Cannot write crawler export: {$outputPath}\n");
    exit(1);
}

fwrite(STDOUT, 'Done. Records: ' . count($records) . PHP_EOL);
fwrite(STDOUT, 'Export: ' . $outputPath . PHP_EOL);

function fetchHtml(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'MaternalChildCareContentCrawler/1.0 (+educational health content ingestion)',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml',
            'Accept-Language: en-US,en;q=0.9',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $html = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($html === false || $error !== '') {
        throw new RuntimeException("HTTP request failed for {$url}: {$error}");
    }

    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("Unexpected HTTP {$status} for {$url}");
    }

    return $html;
}

/**
 * @param array<string,string> $categoryMap
 * @return array<int,array<string,mixed>>
 */
function parseMilestones(string $html, string $url, int $ageMonths, array $categoryMap): array
{
    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$loaded) {
        throw new RuntimeException("Unable to parse HTML from {$url}");
    }

    $xpath = new DOMXPath($dom);
    $headings = $xpath->query('//main//h3 | //*[@role="main"]//h3 | //h3');

    if ($headings === false) {
        return [];
    }

    $records = [];

    foreach ($headings as $heading) {
        $headingText = normalizeText($heading->textContent);
        $sourceCategory = detectCategory($headingText, $categoryMap);

        if ($sourceCategory === null) {
            continue;
        }

        $node = $heading->nextSibling;
        while ($node !== null) {
            if ($node instanceof DOMElement && in_array(strtolower($node->tagName), ['h2', 'h3'], true)) {
                break;
            }

            if ($node instanceof DOMElement) {
                $items = $node->tagName === 'li'
                    ? [$node]
                    : iterator_to_array($node->getElementsByTagName('li'));

                foreach ($items as $item) {
                    $text = normalizeText($item->textContent);
                    if ($text === '' || isNavigationNoise($text)) {
                        continue;
                    }

                    $records[] = [
                        'source_name' => 'CDC',
                        'source_url' => $url,
                        'age_months' => $ageMonths,
                        'source_category' => $sourceCategory,
                        'milestone_text_en' => $text,
                    ];
                }
            }

            $node = $node->nextSibling;
        }
    }

    // DOM بعضی Templateها ممکن است همان milestone را در markup تکرار کند.
    $unique = [];
    foreach ($records as $record) {
        $key = $record['age_months'] . '|' . $record['source_category'] . '|' . $record['milestone_text_en'];
        $unique[$key] = $record;
    }

    return array_values($unique);
}

/** @param array<string,string> $categoryMap */
function detectCategory(string $heading, array $categoryMap): ?string
{
    $normalized = strtolower($heading);

    foreach ($categoryMap as $needle => $category) {
        if (str_contains($normalized, $needle)) {
            return $category;
        }
    }

    return null;
}

function normalizeText(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

function isNavigationNoise(string $text): bool
{
    $noise = [
        'view a photo',
        'view a video',
        'download',
        'audio description',
    ];

    $lower = strtolower($text);
    foreach ($noise as $needle) {
        if ($lower === $needle || str_starts_with($lower, $needle . ' ')) {
            return true;
        }
    }

    return false;
}
