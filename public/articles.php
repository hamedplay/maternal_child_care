<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;

$activePage = 'articles';

$categoryRepository = new CategoryRepository();
$articleRepository = new ArticleRepository();

$categories = $categoryRepository->all();

// نگاشت سریع id → نام دسته‌بندی، برای نمایش بج روی کارت‌ها
$categoryNameById = [];
foreach ($categories as $cat) {
    $categoryNameById[(int) $cat['id']] = $cat['name'];
}

// ============================================================
// فیلتر دسته‌بندی از querystring
// ============================================================
$activeCategorySlug = $_GET['category'] ?? 'all';
$activeCategory = $activeCategorySlug !== 'all' ? $categoryRepository->findBySlug($activeCategorySlug) : null;
$activeCategoryId = $activeCategory ? (int) $activeCategory['id'] : null;

$perPage = 12;
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = $articleRepository->paginate($page, $perPage, $activeCategoryId);
$articles = $result['items'];
$totalArticles = $result['total'];
$totalPages = max(1, (int) ceil($totalArticles / $perPage));

/**
 * چکیده‌ی کوتاه از متن مقاله برای نمایش روی کارت.
 */
function art_excerpt(string $content, int $length = 130): string
{
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($content)));
    if (mb_strlen($plain) <= $length) {
        return $plain;
    }

    return mb_substr($plain, 0, $length) . '…';
}

/**
 * لینک صفحه‌بندی/فیلتر رو با حفظ querystring فعلی می‌سازه.
 */
function art_build_link(string $categorySlug, int $page): string
{
    $params = [];
    if ($categorySlug !== 'all') {
        $params['category'] = $categorySlug;
    }
    if ($page > 1) {
        $params['page'] = $page;
    }

    $query = http_build_query($params);
    return 'articles.php' . ($query ? '?' . $query : '');
}

$pageTitle = $activeCategory
    ? htmlspecialchars($activeCategory['name'], ENT_QUOTES, 'UTF-8') . ' - مقالات - مراقبت مادر و کودک'
    : 'مقالات - مراقبت مادر و کودک';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $pageTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components/header.css">
    <link rel="stylesheet" href="assets/css/components/button.css">
    <link rel="stylesheet" href="assets/css/components/footer.css">
    <link rel="stylesheet" href="assets/css/pages/articles.css">
</head>

<body>

    <!-- ============================================================
    هیرو صفحه، متصل به هدر
    ============================================================ -->
    <section class="art-hero" data-header-offset>

        <?php include TEMPLATES_PATH . '/partials/header.php'; ?>

        <div class="art-hero-inner">
            <span class="art-hero-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" /><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" /></svg>
                کتابخانه‌ی مقالات
            </span>
            <h1>مقالات</h1>
            <p class="subtitle">
                مطالب تخصصی و به‌روز درباره‌ی باروری، بارداری، زایمان و مراقبت از نوزاد،
                نوشته‌شده برای پاسخ به سوال‌های واقعی شما.
            </p>
        </div>

    </section>

    <!-- ============================================================
    تب‌های فیلتر دسته‌بندی
    ============================================================ -->
    <section class="art-filter-section">
        <div class="art-filter-tabs-wrapper">
            <div class="art-filter-tabs">
                <a href="<?= art_build_link('all', 1) ?>" class="art-filter-tab <?= $activeCategorySlug === 'all' ? 'active' : '' ?>">همه</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= art_build_link($cat['slug'], 1) ?>" class="art-filter-tab <?= $activeCategorySlug === $cat['slug'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================
    گرید مقالات
    ============================================================ -->
    <section class="art-grid-section">
        <?php if (empty($articles)): ?>
            <div class="art-empty-state">
                <p>مقاله‌ای توی این دسته‌بندی پیدا نشد.</p>
            </div>
        <?php else: ?>
            <div class="art-grid">
                <?php foreach ($articles as $article): ?>
                    <a href="article.php?slug=<?= urlencode($article['slug']) ?>" class="art-card">
                        <div class="art-card-image-wrapper">
                            <img src="<?= htmlspecialchars($article['cover_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>" class="art-card-image" loading="lazy" />
                            <?php if (!empty($categoryNameById[(int) $article['category']])): ?>
                                <span class="art-card-badge"><?= htmlspecialchars($categoryNameById[(int) $article['category']], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="art-card-body">
                            <h3 class="art-card-title"><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="art-card-excerpt"><?= htmlspecialchars(art_excerpt($article['content']), ENT_QUOTES, 'UTF-8') ?></p>
                            <span class="art-card-more">
                                ادامه مطلب
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7" /><path d="M19 12H5" /></svg>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ============================================================
        صفحه‌بندی
        ============================================================ -->
        <?php if ($totalPages > 1): ?>
            <div class="art-pagination">
                <a href="<?= art_build_link($activeCategorySlug, max(1, $page - 1)) ?>"
                    class="art-page-btn art-page-prev <?= $page <= 1 ? 'disabled' : '' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
                    قبلی
                </a>

                <span class="art-page-indicator">صفحه <?= $page ?> از <?= $totalPages ?></span>

                <a href="<?= art_build_link($activeCategorySlug, min($totalPages, $page + 1)) ?>"
                    class="art-page-btn art-page-next <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    بعدی
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
                </a>
            </div>
        <?php endif; ?>
    </section>

    <?php include TEMPLATES_PATH . '/partials/footer.php'; ?>

    <script src="assets/js/components/header.js"></script>

</body>

</html>