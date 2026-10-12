<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\FeatureCardRepository;
use App\Repositories\PregnancyWeekRepository;
use App\Repositories\SiteStatsRepository;
use App\Repositories\GrowthStandardRepository;
use App\Repositories\DevelopmentMilestoneRepository;
use App\Repositories\FaqRepository;
use App\Repositories\TestimonialRepository;
use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;

$activePage = 'home';
$pageTitle = 'مراقبت مادر و کودک - خانه';

$cardRepository = new FeatureCardRepository();
$cards = $cardRepository->getHomeCards();

// TODO: وقتی پروفایل بارداری کاربر پیاده شد، اگه کاربر لاگین بود و
// pregnancy_due_date داشت، پیش‌فرض باید از روی همون تاریخ محاسبه بشه؛
// انتخاب دستی کاربر از فیلتر همچنان باید اولویت داشته باشه.
$currentWeek = 20;
if (isset($_GET['week'])) {
    $requestedWeek = (int) $_GET['week'];
    if ($requestedWeek >= 1 && $requestedWeek <= 40) {
        $currentWeek = $requestedWeek;
    }
}

$pregnancyWeekRepository = new PregnancyWeekRepository();
$weekData = $pregnancyWeekRepository->findByWeek($currentWeek);

$statsRepository = new SiteStatsRepository($pregnancyWeekRepository);
$stats = $statsRepository->getHomeStats();

// ============================================================
// سکشن ترکیبی «بارداری / کودک» (سوییچ زیر هدر)
// ============================================================
// TODO: وقتی سیستم لاگین و پروفایل خانواده پیاده شد، اگه کاربر لاگین بود
// و کودکش به دنیا اومده بود، $defaultMode باید خودکار 'child' بشه.
$defaultMode = 'pregnancy';
if (isset($_GET['tab_mode']) && $_GET['tab_mode'] === 'child') {
    $defaultMode = 'child';
}

$childGender = (($_GET['child_gender'] ?? 'boy') === 'girl') ? 'girl' : 'boy';

$growthStandardRepository = new GrowthStandardRepository();
$developmentMilestoneRepository = new DevelopmentMilestoneRepository();
$childMaxMonth = $developmentMilestoneRepository->maxAgeInMonths();

$childMonth = 0;
if (isset($_GET['child_month'])) {
    $requestedChildMonth = (int) $_GET['child_month'];
    if ($requestedChildMonth >= 0 && $requestedChildMonth <= $childMaxMonth) {
        $childMonth = $requestedChildMonth;
    }
}
$childGrowthData = $growthStandardRepository->findByGenderAndAge($childGender, $childMonth);
$childMilestones = $developmentMilestoneRepository->findByAgeMonths($childMonth);

// متن‌های نمایشی سکشن بارداری/کودک
$childGenderLabels = ['boy' => 'پسر', 'girl' => 'دختر'];
$childGenderLabel = $childGenderLabels[$childGender] ?? '';

$milestoneCategoryLabels = [
    'motor'            => 'حرکتی',
    'language'         => 'زبانی',
    'cognitive'        => 'شناختی',
    'social_emotional' => 'اجتماعی-عاطفی',
];

// ============================================================
// تیزر سوالات متداول
// ============================================================
$faqRepository = new FaqRepository();
$faqs = $faqRepository->getFeatured(4);

// ============================================================
// تیزر نظرات کاربران (فقط وقتی نظر واقعی و تاییدشده وجود داشته باشه پر می‌شه)
// ============================================================
$testimonialRepository = new TestimonialRepository();
$testimonials = $testimonialRepository->getFeatured(3);

// ============================================================
// تیزر آخرین مقالات
// ============================================================
$categoryRepository = new CategoryRepository();
$categories = $categoryRepository->all();
$categoryNameById = [];
foreach ($categories as $cat) {
    $categoryNameById[(int) $cat['id']] = $cat['name'];
}

$articleRepository = new ArticleRepository();
$latestArticlesResult = $articleRepository->paginate(1, 4, null);
$latestArticles = $latestArticlesResult['items'] ?? [];

/**
 * چکیده‌ی کوتاه از متن مقاله برای نمایش روی کارت.
 */
function home_article_excerpt(string $content, int $length = 110): string
{
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($content)));
    if (mb_strlen($plain) <= $length) {
        return $plain;
    }

    return mb_substr($plain, 0, $length) . '…';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components/header.css">
    <link rel="stylesheet" href="assets/css/components/button.css">
    <link rel="stylesheet" href="assets/css/components/card.css">
    <link rel="stylesheet" href="assets/css/components/footer.css">
    <link rel="stylesheet" href="assets/css/pages/home.css">
    <link rel="stylesheet" href="assets/css/components/mother-baby-toggle.css">
    <link rel="stylesheet" href="assets/css/pages/articles.css">
</head>

<body>

    <!-- ============================================================
    سکشن سفید متصل به هدر
    ============================================================ -->
    <section class="banner-section" data-header-offset>

        <?php include TEMPLATES_PATH . '/partials/header.php'; ?>

        <!-- بنر -->
        <div class="banner-wrapper">

            <!-- تصویر بنر -->
            <img src="assets/image/banner.png" alt="مراقبت مادر و کودک" class="banner-image" />

            <!-- گرادیان محو کننده برای خوانایی متن -->
            <div class="banner-overlay"></div>

            <!-- محتوای روی بنر -->
            <div class="banner-content">
                <h1>با آگاهی، مادر و کودک سالم‌تر</h1>
                <p class="subtitle">
                    منبع معتبر اطلاعات برای دوران بارداری و مراقبت از کودکان
                </p>
                <a href="ai-qa.php" class="btn-primary">
                    چت با هوش مصنوعی
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5" />
                        <path d="m12 19-7-7 7-7" />
                    </svg>
                </a>
            </div>

        </div>

        <!-- ============================================================
        کارت‌های ویژگی
        ============================================================ -->
        <section class="content-section">
            <div class="cards-grid">
                <?php foreach ($cards as $card): ?>
                    <!-- کارت ویژگی: انتظار می‌ره $card شامل icon/title/description باشه -->
                    <div class="card">
                        <div class="card-icon">
                            <?= $card['icon'] ?>
                        </div>
                        <h3><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($card['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </section>

    <!-- ============================================================
    سوییچ بارداری / کودک - همون اول به کاربر نشون می‌ده سایت هم برای
    دوران بارداری هم برای بعد از تولد محتوا داره
    ============================================================ -->
    <section class="mother-baby-section" id="mother-baby-section">
        <div class="section-header">
            <div>
                <h2>هم برای بارداری، هم برای بعد از تولد</h2>
                <p class="section-subtitle">با یک سوییچ ساده، اطلاعات مناسب هر مرحله رو ببینید</p>
            </div>
        </div>

        <!-- سوییچ حالت -->
        <div class="mode-switch" role="tablist">
            <button type="button"
                class="mode-switch-btn <?= $defaultMode === 'pregnancy' ? 'active' : '' ?>"
                data-mode="pregnancy" role="tab" aria-selected="<?= $defaultMode === 'pregnancy' ? 'true' : 'false' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7.5-4.6-10-9.2C.4 8.4 2 5 5.5 5c2 0 3.4 1 4.5 2.5C11.1 6 12.5 5 14.5 5 18 5 19.6 8.4 22 11.8 19.5 16.4 12 21 12 21Z" /></svg>
                بارداری
            </button>
            <button type="button"
                class="mode-switch-btn <?= $defaultMode === 'child' ? 'active' : '' ?>"
                data-mode="child" role="tab" aria-selected="<?= $defaultMode === 'child' ? 'true' : 'false' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M9 12h.01" /><path d="M15 12h.01" /><path d="M9.5 16c.7.4 1.6.6 2.5.6s1.8-.2 2.5-.6" /></svg>
                کودک به دنیا اومده
            </button>
        </div>

        <!-- پنل بارداری -->
        <div class="mode-panel <?= $defaultMode === 'pregnancy' ? 'active' : '' ?>" data-panel="pregnancy">
            <?php if ($weekData): ?>
                <div class="mode-result-card">
                    <div class="mode-result-badge">
                        <span class="mode-result-badge-number"><?= (int) $weekData['week_number'] ?></span>
                        <span class="mode-result-badge-label">هفته</span>
                    </div>
                    <div class="mode-result-body">
                        <?php if (!empty($weekData['fetus_size_comparison'])): ?>
                            <p class="mode-result-summary">
                                اندازه‌ی جنین در این هفته تقریباً <?= htmlspecialchars($weekData['fetus_size_comparison'], ENT_QUOTES, 'UTF-8') ?> است.
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($weekData['development_description'])): ?>
                            <p class="mode-result-desc">
                                <?= htmlspecialchars(mb_substr($weekData['development_description'], 0, 160), ENT_QUOTES, 'UTF-8') ?>…
                            </p>
                        <?php endif; ?>

                        <div class="mode-result-meta">
                            <?php if (!empty($weekData['fetus_length_cm'])): ?>
                                <span>قد تقریبی: <?= htmlspecialchars((string) $weekData['fetus_length_cm'], ENT_QUOTES, 'UTF-8') ?> سانتی‌متر</span>
                            <?php endif; ?>
                            <?php if (!empty($weekData['fetus_weight_g'])): ?>
                                <span>وزن تقریبی: <?= htmlspecialchars((string) $weekData['fetus_weight_g'], ENT_QUOTES, 'UTF-8') ?> گرم</span>
                            <?php endif; ?>
                        </div>

                        <a href="pregnancy-weeks.php?week=<?= (int) $weekData['week_number'] ?>" class="btn-primary mode-result-cta">
                            مشاهده کامل هفته
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="mode-result-card mode-result-empty">
                    <p>اطلاعات این هفته موقتاً در دسترس نیست.</p>
                </div>
            <?php endif; ?>

            <form method="get" action="index.php#mother-baby-section" class="mode-select-form">
                <input type="hidden" name="tab_mode" value="pregnancy">
                <label for="mb-week-select">مشاهده‌ی هفته‌ی دیگر:</label>
                <select name="week" id="mb-week-select">
                    <?php for ($w = 1; $w <= 40; $w++): ?>
                        <option value="<?= $w ?>" <?= $w === $currentWeek ? 'selected' : '' ?>>هفته <?= $w ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit">نمایش</button>
            </form>

            <div class="mode-marketing-cta">
                <p>
                    الان داری هفته‌ای رو که خودت انتخاب کردی می‌بینی.
                    <strong>ثبت‌نام کن</strong> تا هر بار خودکار هفته‌ی واقعی بارداریت رو برات نشون بدیم.
                </p>
                <a href="register.php" class="btn-primary">ثبت‌نام رایگان</a>
            </div>
        </div>

        <!-- پنل کودک: نتیجه‌ی ترکیبی از growth_standards + development_milestones -->
        <div class="mode-panel <?= $defaultMode === 'child' ? 'active' : '' ?>" data-panel="child">
            <?php if ($childGrowthData || !empty($childMilestones)): ?>
                <div class="mode-result-card">
                    <div class="mode-result-badge">
                        <span class="mode-result-badge-number"><?= (int) $childMonth ?></span>
                        <span class="mode-result-badge-label">ماهگی</span>
                    </div>
                    <div class="mode-result-body">
                        <p class="mode-result-summary">
                            <?php if ($childGrowthData && !empty($childGrowthData['length_p50']) && !empty($childGrowthData['weight_p50'])): ?>
                                کودک <?= htmlspecialchars($childGenderLabel, ENT_QUOTES, 'UTF-8') ?> شما در <?= (int) $childMonth ?> ماهگی معمولاً
                                قدی حدود <?= htmlspecialchars((string) $childGrowthData['length_p50'], ENT_QUOTES, 'UTF-8') ?> سانتی‌متر
                                و وزنی حدود <?= htmlspecialchars((string) $childGrowthData['weight_p50'], ENT_QUOTES, 'UTF-8') ?> کیلوگرم دارد<?php if (!empty($childGrowthData['head_p50'])): ?>
                                    (دور سر تقریبی: <?= htmlspecialchars((string) $childGrowthData['head_p50'], ENT_QUOTES, 'UTF-8') ?> سانتی‌متر).
                                <?php else: ?>.
                                <?php endif; ?>
                            <?php else: ?>
                                برای کودک <?= htmlspecialchars($childGenderLabel, ENT_QUOTES, 'UTF-8') ?> در <?= (int) $childMonth ?> ماهگی، داده‌ی استاندارد رشد ثبت نشده
                                (این جدول فعلاً فقط ۰ تا ۱۲ ماهگی رو پوشش می‌ده).
                            <?php endif; ?>
                        </p>

                        <?php if (!empty($childMilestones)): ?>
                            <p class="mode-result-milestones-title">در این سن، کودک شما معمولاً باید بتواند:</p>
                            <ul class="mode-milestone-list">
                                <?php foreach ($childMilestones as $milestone): ?>
                                    <li>
                                        <span class="mode-milestone-category"><?= htmlspecialchars($milestoneCategoryLabels[$milestone['category']] ?? $milestone['category'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?= htmlspecialchars($milestone['milestone_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="mode-result-desc">مرحله‌ی تکاملی خاصی برای این سن ثبت نشده.</p>
                        <?php endif; ?>

                        <a href="milestones.php" class="btn-primary mode-result-cta">
                            مشاهده کامل مراحل تکامل
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="mode-result-card mode-result-empty">
                    <p>اطلاعاتی برای این سن پیدا نشد.</p>
                </div>
            <?php endif; ?>

            <form method="get" action="index.php#mother-baby-section" class="mode-select-form">
                <input type="hidden" name="tab_mode" value="child">
                <label for="mb-gender-select">جنسیت:</label>
                <select name="child_gender" id="mb-gender-select">
                    <option value="boy" <?= $childGender === 'boy' ? 'selected' : '' ?>>پسر</option>
                    <option value="girl" <?= $childGender === 'girl' ? 'selected' : '' ?>>دختر</option>
                </select>
                <label for="mb-month-select">سن (ماه):</label>
                <select name="child_month" id="mb-month-select">
                    <?php for ($m = 0; $m <= $childMaxMonth; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $childMonth ? 'selected' : '' ?>><?= $m ?> ماهگی</option>
                    <?php endfor; ?>
                </select>
                <button type="submit">نمایش</button>
            </form>

            <div class="mode-marketing-cta">
                <p>
                    الان داری سنی رو که خودت انتخاب کردی می‌بینی.
                    <strong>ثبت‌نام کن</strong> تا خودکار سن واقعی فرزندت رو نشون بدیم و رشد و تکاملش رو پیگیری کنیم.
                </p>
                <a href="register.php" class="btn-primary">ثبت‌نام رایگان</a>
            </div>
        </div>
    </section>

    <!-- ============================================================
    نوار آماری
    ============================================================ -->
    <section class="stats-bar-section">
        <div class="stats-bar">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-item">
                    <span class="stat-value"><?= (int) $stat['value'] ?></span>
                    <span class="stat-label"><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ============================================================
    آخرین مقالات (فعلاً فقط هدر، بدنه بعداً پر می‌شه)
    ============================================================ -->
    <section class="articles-teaser-section">
        <div class="section-header">
            <div>
                <h2>آخرین مقالات</h2>
                <p class="section-subtitle">مطالب آموزشی تازه درباره‌ی بارداری و کودک</p>
            </div>
            <a href="articles.php" class="section-link">مشاهده همه</a>
        </div>

        <?php if (!empty($latestArticles)): ?>
            <div class="art-grid">
                <?php foreach ($latestArticles as $article): ?>
                    <a href="article.php?slug=<?= urlencode($article['slug']) ?>" class="art-card">
                        <div class="art-card-image-wrapper">
                            <img src="<?= htmlspecialchars($article['cover_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>" class="art-card-image" loading="lazy" />
                            <?php if (!empty($categoryNameById[(int) $article['category']])): ?>
                                <span class="art-card-badge"><?= htmlspecialchars($categoryNameById[(int) $article['category']], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="art-card-body">
                            <h3 class="art-card-title"><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="art-card-excerpt"><?= htmlspecialchars(home_article_excerpt($article['content']), ENT_QUOTES, 'UTF-8') ?></p>
                            <span class="art-card-more">
                                ادامه مطلب
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7" /><path d="M19 12H5" /></svg>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="articles-empty-state">
                <p>به‌زودی مقالات آموزشی اینجا نمایش داده می‌شن.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============================================================
    سوالات متداول
    ============================================================ -->
    <section class="faq-teaser-section">
        <div class="section-header">
            <div>
                <h2>سوالات متداول</h2>
                <p class="section-subtitle">پاسخ کوتاه به پرتکرارترین سوالات</p>
            </div>
            <a href="faq.php" class="section-link">مشاهده همه</a>
        </div>

        <?php if (!empty($faqs)): ?>
            <div class="faq-accordion">
                <?php foreach ($faqs as $faq): ?>
                    <details class="faq-item">
                        <summary class="faq-question">
                            <span><?= htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8') ?></span>
                            <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
                        </summary>
                        <p class="faq-answer"><?= htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8') ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="faq-empty-state">
                <p>سوالات متداول به‌زودی اینجا اضافه می‌شن.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============================================================
    نظرات کاربران
    ============================================================ -->
    <section class="testimonials-section">
        <div class="section-header">
            <div>
                <h2>تجربه‌ی مادرهای دیگر</h2>
                <p class="section-subtitle">نظراتی که کاربران واقعی ثبت کرده‌اند</p>
            </div>
        </div>

        <?php if (!empty($testimonials)): ?>
            <div class="testimonials-grid">
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card">
                        <?php if (!empty($testimonial['rating'])): ?>
                            <div class="testimonial-rating" aria-label="<?= (int) $testimonial['rating'] ?> از ۵">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $i <= (int) $testimonial['rating'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.5"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2Z" /></svg>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>

                        <p class="testimonial-content">«<?= htmlspecialchars($testimonial['content'], ENT_QUOTES, 'UTF-8') ?>»</p>

                        <div class="testimonial-author">
                            <span class="testimonial-name"><?= htmlspecialchars($testimonial['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($testimonial['role_or_context'])): ?>
                                <span class="testimonial-role"><?= htmlspecialchars($testimonial['role_or_context'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="testimonials-empty-state">
                <p>هنوز نظری ثبت نشده؛ به محض دریافت بازخورد واقعی از کاربران، اینجا نمایش داده می‌شه.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============================================================
    CTA پایانی (ثبت‌نام)
    ============================================================ -->
    <section class="signup-cta-section">
        <div class="signup-cta-box">
            <div class="signup-cta-text">
                <h2>هنوز عضو نشدی؟</h2>
                <p>رایگان ثبت‌نام کن و از یادآوری شخصی‌سازی‌شده، ثبت رشد کودک و مشاوره استفاده کن.</p>
            </div>
            <a href="register.php" class="btn-secondary-light signup-cta-btn">
                ثبت‌نام رایگان
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
            </a>
        </div>
    </section>

    <?php include TEMPLATES_PATH . '/partials/footer.php'; ?>

    <!-- ============================================================
    ویجت شناور تماس با پشتیبانی
    انتظار می‌ره $supportOpen / $supportSent / $supportError بالای همین
    فایل ست شده باشن.
    ============================================================ -->

        <!-- پنل -->
      <?php include TEMPLATES_PATH . '/partials/support-widget.php'; ?>

    <script src="assets/js/components/header.js"></script>
    <script src="assets/js/components/mother-baby-toggle.js"></script>


</body>

</html>