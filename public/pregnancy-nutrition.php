<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\NutritionRepository;

$activePage = 'pregnancy-nutrition';

$nutritionRepository = new NutritionRepository();

// ============================================================
// فیلتر سه‌ماهه از querystring (پیش‌فرض: همه)
// ============================================================
$trimesterLabels = NutritionRepository::TRIMESTER_LABELS;

$activeTrimester = $_GET['trimester'] ?? 'all';
if (!array_key_exists($activeTrimester, $trimesterLabels)) {
    $activeTrimester = 'all';
}

// بازه‌ی هفته برای نمایش کنار هر تب (فقط جهت نمایش، مستقل از دیتابیس)
$trimesterWeekRanges = [
    'all'    => null,
    'first'  => '۱ تا ۱۲',
    'second' => '۱۳ تا ۲۶',
    'third'  => '۲۷ تا ۴۰',
];

if ($activeTrimester === 'all') {
    $nutrients = $nutritionRepository->all();
} else {
    $nutrients = $nutritionRepository->findByTrimester($activeTrimester);
}

// وقتی «همه» انتخاب شده، برای نمایش بهتر، موادِ مغذی رو زیر سرتیتر هر سه‌ماهه گروه‌بندی می‌کنیم
$groupedNutrients = [];
if ($activeTrimester === 'all') {
    foreach (array_keys($trimesterLabels) as $key) {
        $groupedNutrients[$key] = [];
    }
    foreach ($nutrients as $item) {
        $key = $item['trimester'] ?? 'all';
        if (!isset($groupedNutrients[$key])) {
            $groupedNutrients[$key] = [];
        }
        $groupedNutrients[$key][] = $item;
    }
} else {
    $groupedNutrients[$activeTrimester] = $nutrients;
}

$pageTitle = 'تغذیه دوران بارداری - مراقبت مادر و کودک';
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
    <link rel="stylesheet" href="assets/css/components/footer.css">
    <link rel="stylesheet" href="assets/css/pages/pregnancy-nutrition.css">
</head>

<body>

    <!-- ============================================================
    هیرو صفحه، متصل به هدر (دقیقاً مثل صفحه‌ی هفته‌به‌هفته)
    ============================================================ -->
    <section class="pn-hero" data-header-offset>

        <?php include TEMPLATES_PATH . '/partials/header.php'; ?>

        <div class="pn-hero-banner">
            <!--
                تصویر پیشنهادی: یک تصویر/ایلاستریشن افقی (نسبت تقریبی ۲.۴:۱، حدود ۱۹۰۰×۸۰۰ پیکسل)
                که تغذیه‌ی سالم دوران بارداری رو نشون بده؛ مثلاً یک زن باردار در حال خوردن میوه/سبزیجات
                یا میز پر از غذاهای سالم (سبزیجات برگ‌سبز، لبنیات، ماهی، آجیل). پالت رنگی هماهنگ با سایت
                (سبزآبی/سرمه‌ای روی زمینه‌ی روشن). فایل رو با همین اسم توی public/assets/image/ قرار بده:
            -->
            <img src="assets/image/pregnancy-nutrition-banner.png" alt="تغذیه دوران بارداری" class="pn-hero-image" />
            <div class="pn-hero-overlay"></div>

            <div class="pn-hero-content">
                <span class="pn-hero-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v6" /><path d="M8 4c0 2.5 1.5 4 4 4s4-1.5 4-4" /><path d="M6 10c-2 3-2 8 1 11a8 8 0 0 0 10 0c3-3 3-8 1-11" /></svg>
                    راهنمای کامل تغذیه بارداری
                </span>
                <h1>تغذیه دوران بارداری</h1>
                <p class="subtitle">
                    هر ماده‌ی مغذی نقش مهمی توی سلامت شما و رشد جنین داره. اینجا می‌تونید ببینید
                    توی هر سه‌ماهه به چه ویتامین‌ها و مواد معدنی‌ای بیشتر نیاز دارید، از کجا تأمینش کنید
                    و چه مقدار برای‌تان کافیه.
                </p>
            </div>
        </div>

    </section>

    <!-- ============================================================
    تب‌های فیلتر سه‌ماهه
    ============================================================ -->
    <section class="pn-filter-section">
        <div class="pn-filter-tabs">
            <?php foreach ($trimesterLabels as $key => $label): ?>
                <a href="pregnancy-nutrition.php?trimester=<?= urlencode($key) ?>#pn-list-section"
                    class="pn-filter-tab <?= $activeTrimester === $key ? 'active' : '' ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($trimesterWeekRanges[$key]): ?>
                        <span class="pn-filter-tab-range">(هفته <?= htmlspecialchars($trimesterWeekRanges[$key], ENT_QUOTES, 'UTF-8') ?>)</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ============================================================
    لیست مواد مغذی (گروه‌بندی‌شده بر اساس سه‌ماهه)
    ============================================================ -->
    <section class="pn-list-section" id="pn-list-section">

        <?php $hasAnyNutrient = false; ?>
        <?php foreach ($groupedNutrients as $groupKey => $groupItems): ?>
            <?php if (empty($groupItems)) continue; ?>
            <?php $hasAnyNutrient = true; ?>

            <div class="pn-group">
                <h2 class="pn-group-title">
                    <?= htmlspecialchars($trimesterLabels[$groupKey] ?? $groupKey, ENT_QUOTES, 'UTF-8') ?>
                </h2>

                <div class="pn-nutrient-grid">
                    <?php foreach ($groupItems as $nutrient): ?>
                        <div class="pn-nutrient-card">
                            <div class="pn-nutrient-header">
                                <div class="pn-nutrient-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v6" /><path d="M8 4c0 2.5 1.5 4 4 4s4-1.5 4-4" /><path d="M6 10c-2 3-2 8 1 11a8 8 0 0 0 10 0c3-3 3-8 1-11" /></svg>
                                </div>
                                <h3><?= htmlspecialchars($nutrient['nutrient_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            </div>

                            <?php if (!empty($nutrient['weeks_recommended'])): ?>
                                <span class="pn-nutrient-weeks">هفته‌های <?= htmlspecialchars($nutrient['weeks_recommended'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>

                            <?php if (!empty($nutrient['benefits'])): ?>
                                <p class="pn-nutrient-benefits"><?= nl2br(htmlspecialchars($nutrient['benefits'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($nutrient['daily_requirement'])): ?>
                                <div class="pn-nutrient-row">
                                    <span class="pn-nutrient-row-label">نیاز روزانه</span>
                                    <span class="pn-nutrient-row-value"><?= htmlspecialchars($nutrient['daily_requirement'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($nutrient['food_sources'])): ?>
                                <div class="pn-nutrient-row">
                                    <span class="pn-nutrient-row-label">منابع غذایی</span>
                                    <span class="pn-nutrient-row-value"><?= nl2br(htmlspecialchars($nutrient['food_sources'], ENT_QUOTES, 'UTF-8')) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($nutrient['supplement_advice'])): ?>
                                <div class="pn-nutrient-tip">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 1.5-2.5 2-2.5 3.5" /><path d="M12 16.5h.01" /><circle cx="12" cy="12" r="10" /></svg>
                                    <p><?= nl2br(htmlspecialchars($nutrient['supplement_advice'], ENT_QUOTES, 'UTF-8')) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$hasAnyNutrient): ?>
            <div class="pn-empty-state">
                <p>اطلاعات تغذیه‌ای موقتاً در دسترس نیست؛ لطفاً بعداً دوباره سر بزنید.</p>
            </div>
        <?php endif; ?>

    </section>

    <!-- ============================================================
    CTA بازاریابی: تشویق به ثبت‌نام برای شخصی‌سازی خودکار
    ============================================================ -->
    <section class="pn-cta-section">
        <div class="pn-cta-box">
            <p>
                همین حالا داری راهنمای کلی تغذیه رو می‌بینی.
                <strong>ثبت‌نام کن</strong> تا بر اساس هفته‌ی دقیق بارداریت، لیست مواد مغذی موردنیاز
                و پیشنهاد وعده‌های غذایی شخصی‌سازی‌شده رو دریافت کنی.
            </p>
            <a href="register.php" class="btn-primary pn-cta-btn">ثبت‌نام رایگان</a>
        </div>
    </section>

    <?php include TEMPLATES_PATH . '/partials/footer.php'; ?>

    <script src="assets/js/components/header.js"></script>

</body>

</html>
