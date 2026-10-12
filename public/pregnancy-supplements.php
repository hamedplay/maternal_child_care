<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\SupplementRepository;

$activePage = 'pregnancy-supplements';

$supplementRepository = new SupplementRepository();

// ============================================================
// فیلتر سه‌ماهه از querystring (پیش‌فرض: همه)
// ============================================================
$trimesterLabels = SupplementRepository::TRIMESTER_LABELS;

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
    $supplements = $supplementRepository->all();
} else {
    $supplements = $supplementRepository->findByTrimester($activeTrimester);
}

// وقتی «همه» انتخاب شده، برای نمایش بهتر، مکمل‌ها رو زیر سرتیتر هر سه‌ماهه گروه‌بندی می‌کنیم
$groupedSupplements = [];
if ($activeTrimester === 'all') {
    foreach (array_keys($trimesterLabels) as $key) {
        $groupedSupplements[$key] = [];
    }
    foreach ($supplements as $item) {
        $key = $item['trimester'] ?? 'all';
        if (!isset($groupedSupplements[$key])) {
            $groupedSupplements[$key] = [];
        }
        $groupedSupplements[$key][] = $item;
    }
} else {
    $groupedSupplements[$activeTrimester] = $supplements;
}

$pageTitle = 'مکمل‌های ضروری دوران بارداری - مراقبت مادر و کودک';
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
    <link rel="stylesheet" href="assets/css/pages/pregnancy-supplements.css">
</head>

<body>

    <!-- ============================================================
    هیرو صفحه، متصل به هدر (دقیقاً مثل صفحات دیگر بارداری)
    ============================================================ -->
    <section class="ps-hero" data-header-offset>

        <?php include TEMPLATES_PATH . '/partials/header.php'; ?>

        <div class="ps-hero-banner">
            <!--
                تصویر پیشنهادی: یک تصویر/ایلاستریشن افقی (نسبت تقریبی ۲.۴:۱، حدود ۱۹۰۰×۸۰۰ پیکسل)
                که مکمل‌های بارداری رو نشون بده؛ مثلاً قرص/کپسول‌های ویتامین کنار یک لیوان آب روی زمینه‌ی
                روشن، یا یک زن باردار در حال مصرف مکمل با دکتر/داروخانه در پس‌زمینه. پالت رنگی هماهنگ با سایت
                (سبزآبی/سرمه‌ای روی زمینه‌ی روشن). فایل رو با همین اسم توی public/assets/image/ قرار بده:
            -->
            <img src="assets/image/pregnancy-supplements-banner.png" alt="مکمل‌های ضروری بارداری" class="ps-hero-image" />
            <div class="ps-hero-overlay"></div>

            <div class="ps-hero-content">
                <span class="ps-hero-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z" /><path d="m8.5 8.5 7 7" /></svg>
                    راهنمای کامل مکمل‌های بارداری
                </span>
                <h1>مکمل‌های ضروری دوران بارداری</h1>
                <p class="subtitle">
                    مصرف درست مکمل‌ها می‌تونه از خیلی از کمبودهای رایج دوران بارداری جلوگیری کنه.
                    اینجا می‌بینید توی هر سه‌ماهه به چه مکملی نیاز دارید، دوز مصرف، زمان شروع
                    و نکات احتیاطی مهمش چیه — همیشه پیش از شروع هر مکملی با پزشک‌تان مشورت کنید.
                </p>
            </div>
        </div>

    </section>

    <!-- ============================================================
    تب‌های فیلتر سه‌ماهه
    ============================================================ -->
    <section class="ps-filter-section">
        <div class="ps-filter-tabs">
            <?php foreach ($trimesterLabels as $key => $label): ?>
                <a href="pregnancy-supplements.php?trimester=<?= urlencode($key) ?>#ps-list-section"
                    class="ps-filter-tab <?= $activeTrimester === $key ? 'active' : '' ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($trimesterWeekRanges[$key]): ?>
                        <span class="ps-filter-tab-range">(هفته <?= htmlspecialchars($trimesterWeekRanges[$key], ENT_QUOTES, 'UTF-8') ?>)</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ============================================================
    لیست مکمل‌ها (گروه‌بندی‌شده بر اساس سه‌ماهه)
    ============================================================ -->
    <section class="ps-list-section" id="ps-list-section">

        <?php $hasAnySupplement = false; ?>
        <?php foreach ($groupedSupplements as $groupKey => $groupItems): ?>
            <?php if (empty($groupItems)) continue; ?>
            <?php $hasAnySupplement = true; ?>

            <div class="ps-group">
                <h2 class="ps-group-title">
                    <?= htmlspecialchars($trimesterLabels[$groupKey] ?? $groupKey, ENT_QUOTES, 'UTF-8') ?>
                </h2>

                <div class="ps-supplement-grid">
                    <?php foreach ($groupItems as $supplement): ?>
                        <div class="ps-supplement-card">
                            <div class="ps-supplement-header">
                                <div class="ps-supplement-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z" /><path d="m8.5 8.5 7 7" /></svg>
                                </div>
                                <h3><?= htmlspecialchars($supplement['supplement_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            </div>

                            <div class="ps-supplement-badges">
                                <?php if (!empty($supplement['is_essential'])): ?>
                                    <span class="ps-badge ps-badge-essential">ضروری</span>
                                <?php else: ?>
                                    <span class="ps-badge ps-badge-optional">اختیاری</span>
                                <?php endif; ?>

                                <?php if (!empty($supplement['requires_prescription'])): ?>
                                    <span class="ps-badge ps-badge-rx">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z" /></svg>
                                        نیاز به تجویز پزشک
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($supplement['importance'])): ?>
                                <p class="ps-supplement-importance"><?= nl2br(htmlspecialchars($supplement['importance'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($supplement['dosage'])): ?>
                                <div class="ps-supplement-row">
                                    <span class="ps-supplement-row-label">دوز مصرف</span>
                                    <span class="ps-supplement-row-value"><?= htmlspecialchars($supplement['dosage'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($supplement['start_time'])): ?>
                                <div class="ps-supplement-row">
                                    <span class="ps-supplement-row-label">زمان شروع</span>
                                    <span class="ps-supplement-row-value"><?= htmlspecialchars($supplement['start_time'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($supplement['duration'])): ?>
                                <div class="ps-supplement-row">
                                    <span class="ps-supplement-row-label">مدت مصرف</span>
                                    <span class="ps-supplement-row-value"><?= htmlspecialchars($supplement['duration'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($supplement['precautions'])): ?>
                                <div class="ps-supplement-warning">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
                                    <p><?= nl2br(htmlspecialchars($supplement['precautions'], ENT_QUOTES, 'UTF-8')) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$hasAnySupplement): ?>
            <div class="ps-empty-state">
                <p>اطلاعات مکمل‌ها موقتاً در دسترس نیست؛ لطفاً بعداً دوباره سر بزنید.</p>
            </div>
        <?php endif; ?>

    </section>

    <!-- ============================================================
    تذکر پزشکی کلی
    ============================================================ -->
    <section class="ps-disclaimer-section">
        <div class="ps-disclaimer-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 1.5-2.5 2-2.5 3.5" /><path d="M12 16.5h.01" /><circle cx="12" cy="12" r="10" /></svg>
            <p>
                دوز و زمان مصرف مکمل‌ها بسته به شرایط جسمی هر فرد (کمبودها، بیماری‌های زمینه‌ای، نتیجه‌ی آزمایش‌ها)
                می‌تواند متفاوت باشد. پیش از شروع، تغییر یا قطع هر مکملی حتماً با پزشک یا ماما خودتان مشورت کنید.
            </p>
        </div>
    </section>

    <!-- ============================================================
    CTA بازاریابی: تشویق به ثبت‌نام برای شخصی‌سازی خودکار
    ============================================================ -->
    <section class="ps-cta-section">
        <div class="ps-cta-box">
            <p>
                همین حالا داری راهنمای کلی مکمل‌ها رو می‌بینی.
                <strong>ثبت‌نام کن</strong> تا بر اساس هفته‌ی دقیق بارداریت، لیست مکمل‌های موردنیاز
                و یادآوری زمان مصرف‌شون رو دریافت کنی.
            </p>
            <a href="register.php" class="btn-primary ps-cta-btn">ثبت‌نام رایگان</a>
        </div>
    </section>

    <?php include TEMPLATES_PATH . '/partials/footer.php'; ?>

    <script src="assets/js/components/header.js"></script>

</body>

</html>
