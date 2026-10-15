<?php

/**
 * Import reviewed Persian CDC milestone records into development_milestones.
 *
 * Usage:
 *   php tools/crawlers/import_cdc_milestones.php reviewed.json --dry-run
 *   php tools/crawlers/import_cdc_milestones.php reviewed.json
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This importer must be executed from CLI.\n");
    exit(1);
}

require_once dirname(__DIR__, 2) . '/config/config.php';

use App\Core\Database;

$inputPath = $argv[1] ?? '';
$dryRun = in_array('--dry-run', $argv, true);

if ($inputPath === '' || !is_file($inputPath)) {
    fwrite(STDERR, "Usage: php tools/crawlers/import_cdc_milestones.php reviewed.json [--dry-run]\n");
    exit(1);
}

$raw = file_get_contents($inputPath);
$data = json_decode((string) $raw, true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON input.\n");
    exit(1);
}

$records = $data['records'] ?? null;
if (!is_array($records)) {
    fwrite(STDERR, "JSON must contain a records array.\n");
    exit(1);
}

$allowedCategories = ['motor', 'language', 'cognitive', 'social_emotional'];
$allowedTypes = ['gross_motor', 'fine_motor', 'speech', 'social', 'cognitive'];
$validated = [];
$errors = [];

foreach ($records as $index => $record) {
    if (!is_array($record)) {
        $errors[] = "Record #{$index}: expected object.";
        continue;
    }

    $name = trim((string) ($record['milestone_name_fa'] ?? ''));
    $description = trim((string) ($record['description_fa'] ?? ''));
    $category = (string) ($record['category'] ?? '');
    $type = (string) ($record['milestone_type'] ?? '');
    $ageStart = filter_var($record['age_months_start'] ?? null, FILTER_VALIDATE_INT);
    $ageEnd = filter_var($record['age_months_end'] ?? null, FILTER_VALIDATE_INT);
    $warningSigns = trim((string) ($record['warning_signs_fa'] ?? ''));
    $sourceUrl = trim((string) ($record['source_url'] ?? ''));

    if ($name === '' || $description === '') {
        $errors[] = "Record #{$index}: milestone_name_fa and description_fa are required.";
    }
    if (!in_array($category, $allowedCategories, true)) {
        $errors[] = "Record #{$index}: invalid category '{$category}'.";
    }
    if (!in_array($type, $allowedTypes, true)) {
        $errors[] = "Record #{$index}: invalid milestone_type '{$type}'.";
    }
    if ($ageStart === false || $ageEnd === false || $ageStart < 0 || $ageEnd < $ageStart) {
        $errors[] = "Record #{$index}: invalid age range.";
    }
    if ($sourceUrl === '' || !str_starts_with($sourceUrl, 'https://www.cdc.gov/act-early/milestones/')) {
        $errors[] = "Record #{$index}: source_url must point to the CDC milestones path.";
    }

    if ($errors !== [] && str_starts_with(end($errors), "Record #{$index}:")) {
        continue;
    }

    $validated[] = [
        'milestone_name' => $name,
        'category' => $category,
        'description' => $description,
        'age_months_start' => (int) $ageStart,
        'age_months_end' => (int) $ageEnd,
        'milestone_type' => $type,
        'warning_signs' => $warningSigns !== '' ? $warningSigns : null,
    ];
}

if ($errors !== []) {
    fwrite(STDERR, "Validation failed:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

fwrite(STDOUT, 'Validated records: ' . count($validated) . PHP_EOL);
if ($dryRun) {
    fwrite(STDOUT, "Dry run only. No database changes were made.\n");
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $exists = $pdo->prepare(
        'SELECT id FROM development_milestones
         WHERE milestone_name = :milestone_name
           AND category = :category
           AND age_months_start = :age_start
           AND age_months_end = :age_end
           AND milestone_type = :milestone_type
         LIMIT 1'
    );

    $insert = $pdo->prepare(
        'INSERT INTO development_milestones
            (milestone_name, category, description, age_months_start, age_months_end, milestone_type, warning_signs)
         VALUES
            (:milestone_name, :category, :description, :age_start, :age_end, :milestone_type, :warning_signs)'
    );

    $inserted = 0;
    $skipped = 0;

    foreach ($validated as $record) {
        $key = [
            'milestone_name' => $record['milestone_name'],
            'category' => $record['category'],
            'age_start' => $record['age_months_start'],
            'age_end' => $record['age_months_end'],
            'milestone_type' => $record['milestone_type'],
        ];

        $exists->execute($key);
        if ($exists->fetchColumn() !== false) {
            $skipped++;
            continue;
        }

        $insert->execute($key + [
            'description' => $record['description'],
            'warning_signs' => $record['warning_signs'],
        ]);
        $inserted++;
    }

    $pdo->commit();
    fwrite(STDOUT, "Imported: {$inserted}; skipped existing: {$skipped}\n");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Import failed; transaction rolled back: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
