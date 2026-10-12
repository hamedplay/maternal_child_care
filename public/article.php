<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;

$activePage = 'articles';

$articleRepository = new ArticleRepository();
$categoryRepository = new CategoryRepository();

$slug = trim((string) ($_GET['slug'] ?? ''));
$article = $slug !== '' ? $articleRepository->findBySlug($slug) : null;

if ($article === null) {
    $pageTitle = 'مقاله پیدا نشد - مراقبت مادر و کودک';
} else {
    $category = $categoryRepository->findById((int) $article['category']);
    $relatedArticles = $articleRepository->findRelated((int) $article['category'], (int) $article['id'], 4);
    $pageTitle = htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') . ' - مراقبت مادر و کودک';
}

/**
 * چکیده‌ی کوتاه از متن مقاله برای نمایش روی کارت‌های مرتبط.
 */
function art_excerpt(string $content, int $length = 100): string
{
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($content)));
    if (mb_strlen($plain) <= $length) {
        return $plain;
    }

    return mb_substr($plain, 0, $length) . '…';
}

/**
 * تاریخ رو به فرمت خوانا با اعداد فارسی برمی‌گردونه.
 */
function art_persian_date(string $datetime): string
{
    $formatted = date('Y/m/d', strtotime($datetime));
    return strtr($formatted, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}

/**
 * پاک‌سازی امنیتی HTML مقاله (که از یک کرالر خارجی میاد، نه از فرم کاربر خودمون).
 * فقط تگ‌های رایج محتوا رو نگه می‌داره، اسکریپت/آی‌فریم/فرم رو کامل حذف می‌کنه،
 * و attributeهای خطرناک (onclick و مشابه، href با javascript:) رو از تگ‌های مجاز پاک می‌کنه.
 */
function art_sanitize_html(string $html): string
{
    $allowedTags = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6>'
        . '<ul><ol><li><a><img><blockquote><span><div>'
        . '<table><thead><tbody><tr><td><th><figure><figcaption>';

    $html = strip_tags($html, $allowedTags);

    if (trim($html) === '') {
        return '';
    }

    libxml_use_internal_errors(true);
    $dom = new \DOMDocument();
    $dom->loadHTML(
        '<?xml encoding="utf-8"?><div id="art-sanitize-root">' . $html . '</div>',
        LIBXML_NOERROR | LIBXML_NOWARNING
    );
    libxml_clear_errors();

    $xpath = new \DOMXPath($dom);
    foreach ($xpath->query('//*') as $node) {
        if (!($node instanceof \DOMElement)) {
            continue;
        }

        foreach (iterator_to_array($node->attributes ?? []) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);
            $isEventAttr = strpos($name, 'on') === 0;
            $isDangerousUrl = in_array($name, ['href', 'src'], true)
                && preg_match('/^\s*javascript:/i', $value);

            if ($isEventAttr || $isDangerousUrl) {
                $node->removeAttribute($attr->name);
            }
        }

        // لینک‌های داخل مقاله رو امن‌تر باز می‌کنیم
        if ($node->nodeName === 'a' && $node->hasAttribute('href')) {
            $node->setAttribute('rel', 'noopener noreferrer nofollow');
            $node->setAttribute('target', '_blank');
        }
    }

    $wrapper = $dom->getElementById('art-sanitize-root');
    if ($wrapper === null) {
        return '';
    }

    $result = '';
    foreach ($wrapper->childNodes as $child) {
        $result .= $dom->saveHTML($child);
    }

    return $result;
}
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

    <section class="art-hero art-hero-slim" data-header-offset>
        <?php include TEMPLATES_PATH . '/partials/header.php'; ?>
    </section>

    <?php if ($article === null): ?>

        <!-- ============================================================
        مقاله پیدا نشد
        ============================================================ -->
        <section class="artd-notfound-section">
            <div class="artd-notfound-box">
                <p>این مقاله پیدا نشد یا حذف شده.</p>
                <a href="articles.php" class="btn-primary">بازگشت به مقالات</a>
            </div>
        </section>

    <?php else: ?>

        <!-- ============================================================
        بردکرامب
        ============================================================ -->
        <section class="artd-breadcrumb-section">
            <div class="artd-breadcrumb">
                <a href="index.php">خانه</a>
                <span>/</span>
                <a href="articles.php">مقالات</a>
                <?php if ($category): ?>
                    <span>/</span>
                    <a href="articles.php?category=<?= urlencode($category['slug']) ?>"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
            </div>
        </section>

        <!-- ============================================================
        بنر کاور + عنوان
        ============================================================ -->
        <section class="artd-cover-section">
            <img src="<?= htmlspecialchars($article['cover_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>" class="artd-cover-image" />
        </section>

        <article class="artd-article-section">
            <div class="artd-meta">
                <?php if ($category): ?>
                    <span class="artd-category-badge"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span class="artd-date">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="3" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
                    <?= art_persian_date($article['created_at']) ?>
                </span>
            </div>

            <h1 class="artd-title"><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></h1>

            <div class="artd-content">
                <?= art_sanitize_html($article['content']) ?>
            </div>
        </article>

        <!-- ============================================================
        CTA بازاریابی
        ============================================================ -->
        <section class="artd-cta-section">
            <div class="artd-cta-box">
                <p>
                    دوست داری مطالب متناسب با هفته‌ی دقیق بارداریت رو ببینی؟
                    <strong>ثبت‌نام کن</strong> تا محتوای سایت برات شخصی‌سازی بشه.
                </p>
                <a href="register.php" class="btn-primary artd-cta-btn">ثبت‌نام رایگان</a>
            </div>
        </section>

        <!-- ============================================================
        مقالات مرتبط
        ============================================================ -->
        <?php if (!empty($relatedArticles)): ?>
            <section class="artd-related-section">
                <h2 class="artd-related-title">مقالات مرتبط</h2>
                <div class="art-grid">
                    <?php foreach ($relatedArticles as $related): ?>
                        <a href="article.php?slug=<?= urlencode($related['slug']) ?>" class="art-card">
                            <div class="art-card-image-wrapper">
                                <img src="<?= htmlspecialchars($related['cover_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($related['title'], ENT_QUOTES, 'UTF-8') ?>" class="art-card-image" loading="lazy" />
                            </div>
                            <div class="art-card-body">
                                <h3 class="art-card-title"><?= htmlspecialchars($related['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="art-card-excerpt"><?= htmlspecialchars(art_excerpt($related['content']), ENT_QUOTES, 'UTF-8') ?></p>
                                <span class="art-card-more">
                                    ادامه مطلب
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7" /><path d="M19 12H5" /></svg>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

    <?php endif; ?>

    <?php include TEMPLATES_PATH . '/partials/footer.php'; ?>

    <script src="assets/js/components/header.js"></script>

</body>

</html>