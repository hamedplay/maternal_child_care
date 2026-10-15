<?php

/**
 * پاک‌سازی تبلیغات گهواره از HTML مقالات موجود.
 *
 * Usage:
 *   php tools/cleanup_gahvare_article_ads.php --dry-run
 *   php tools/cleanup_gahvare_article_ads.php --apply
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool must be executed from CLI.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.php';

use App\Core\Database;
use App\Repositories\ArticleRepository;
use App\Services\ArticleContentSanitizer;

$apply = in_array('--apply', $argv, true);
$dryRun = in_array('--dry-run', $argv, true) || !$apply;

$repository = new ArticleRepository();
$sanitizer = new ArticleContentSanitizer();
$articles = $repository->allForContentCleanup();

$changed = [];
foreach ($articles as $article) {
    $before = (string) $article['content'];
    $after = $sanitizer->removeGahvareAds($before);

    if ($after === $before) {
        continue;
    }

    $changed[] = [
        'id' => (int) $article['id'],
        'title' => (string) $article['title'],
        'before_bytes' => strlen($before),
        'after_bytes' => strlen($after),
        'content' => $after,
    ];
}

fwrite(STDOUT, 'Articles scanned: ' . count($articles) . PHP_EOL);
fwrite(STDOUT, 'Articles requiring cleanup: ' . count($changed) . PHP_EOL);

foreach ($changed as $row) {
    fwrite(
        STDOUT,
        sprintf(
            "#%d %s | %d -> %d bytes\n",
            $row['id'],
            $row['title'],
            $row['before_bytes'],
            $row['after_bytes']
        )
    );
}

if ($dryRun) {
    fwrite(STDOUT, "Dry run only. No database changes were made. Use --apply to persist cleanup.\n");
    exit(0);
}

if ($changed === []) {
    fwrite(STDOUT, "Nothing to update.\n");
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    foreach ($changed as $row) {
        $repository->updateContent($row['id'], $row['content']);
    }

    $pdo->commit();
    fwrite(STDOUT, 'Updated articles: ' . count($changed) . PHP_EOL);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Cleanup failed; transaction rolled back: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
