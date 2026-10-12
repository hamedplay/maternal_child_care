<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\PregnancyWeekRepository;

$activePage = 'pregnancy-weeks';

$pregnancyWeekRepository = new PregnancyWeekRepository();

// اگه دیتابیس در دسترس نبود، totalWeeks() خودش ۴۰ رو fallback می‌کنه
$totalWeeks = $pregnancyWeekRepository->totalWeeks();
if ($totalWeeks < 1) {
    $totalWeeks = 40;
}

$currentWeek = 20;
if (isset($_GET['week'])) {
    $requestedWeek = (int) $_GET['week'];
    if ($requestedWeek >= 1 && $requestedWeek <= $totalWeeks) {
        $currentWeek = $requestedWeek;
    }
}

$weekData = $pregnancyWeekRepository->findByWeek($currentWeek);

$pageTitle = 'هفته ' . $currentWeek . ' بارداری - هفته به هفته | مراقبت مادر و کودک';

// ============================================================
// گروه‌بندی سه‌ماهه (استاندارد پزشکی رایج - مستقل از دیتابیس)
// ============================================================
$trimesters = [
    1 => ['label' => 'سه‌ماهه اول', 'from' => 1, 'to' => 13],
    2 => ['label' => 'سه‌ماهه دوم', 'from' => 14, 'to' => 27],
    3 => ['label' => 'سه‌ماهه سوم', 'from' => 28, 'to' => $totalWeeks],
];

$currentTrimester = 1;
foreach ($trimesters as $number => $range) {
    if ($currentWeek >= $range['from'] && $currentWeek <= $range['to']) {
        $currentTrimester = $number;
    }
}

// هفته‌ی قبل/بعد برای ناوبری سریع
$prevWeek = $currentWeek > 1 ? $currentWeek - 1 : null;
$nextWeek = $currentWeek < $totalWeeks ? $currentWeek + 1 : null;
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
    <link rel="stylesheet" href="assets/css/pages/pregnancy-weeks.css">
</head>

<body>

    <!-- ============================================================
    هیرو صفحه، متصل به هدر (دقیقاً مثل صفحه‌ی خانه)
    ============================================================ -->
    <section class="pw-hero" data-header-offset>

        <?php include TEMPLATES_PATH . '/partials/header.php'; ?>

        <div class="pw-hero-banner">
            <!--
                تصویر پیشنهادی: یک تصویر/ایلاستریشن افقی (نسبت تقریبی ۲.۴:۱ مثل banner.png، حدود ۱۹۰۰×۸۰۰ پیکسل)
                که روند هفته‌به‌هفته بارداری رو نشون بده؛ مثلاً یک زن باردار آرام کنار یک تقویم/نمودار رشد جنین،
                یا مجموعه‌ای از سیلوئت‌های شکم بارداری در سه‌ماهه‌های مختلف. رنگ‌بندی نرم و هماهنگ با پالت سایت
                (سبزآبی/سرمه‌ای روی زمینه‌ی روشن) باشه تا با gradient پشت تصویر ترکیب بشه.
                فایل رو با همین اسم توی public/assets/image/ قرار بده:
            -->
            <img src="assets/image/pregnancy-weeks-banner.png" alt="هفته به هفته بارداری" class="pw-hero-image" />
            <div class="pw-hero-overlay"></div>

            <div class="pw-hero-content">
                <span class="pw-trimester-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                    هفته <?= (int) $currentWeek ?> از <?= (int) $totalWeeks ?> - <?= htmlspecialchars($trimesters[$currentTrimester]['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <h1>هفته به هفته بارداری</h1>
                <p class="subtitle">
                    از همون هفته‌ی اول تا لحظه‌ی زایمان، هر هفته رو با جزئیات دنبال کنید:
                    اندازه‌ی جنین، تغییرات بدن مادر و نکاتی که این هفته باید بدونید.
                </p>
            </div>
        </div>

    </section>

    <!-- ============================================================
    ناوبری سه‌ماهه‌ها + انتخاب سریع هفته
    ============================================================ -->
    <section class="pw-week-nav-section">

        <div class="pw-trimester-links">
            <?php foreach ($trimesters as $number => $range): ?>
                <a href="pregnancy-weeks.php?week=<?= (int) $range['from'] ?>#pw-detail-section"
                    class="pw-trimester-link <?= $currentTrimester === $number ? 'active' : '' ?>">
                    <?= htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8') ?>
                    (هفته <?= (int) $range['from'] ?> تا <?= (int) $range['to'] ?>)
                </a>
            <?php endforeach; ?>
        </div>

        <div class="pw-week-pills-wrapper">
            <div class="pw-week-pills">
                <?php for ($w = 1; $w <= $totalWeeks; $w++): ?>
                    <a href="pregnancy-weeks.php?week=<?= $w ?>#pw-detail-section"
                        class="pw-week-pill <?= $w === $currentWeek ? 'active' : '' ?>">
                        <?= $w ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>

    </section>

    <!-- ============================================================
    کارت جزئیات هفته‌ی انتخاب‌شده
    ============================================================ -->
    <section class="pw-detail-section" id="pw-detail-section">
        <?php if ($weekData): ?>
            <div class="pw-detail-card">
                <div class="pw-detail-badge">
                    <span class="pw-detail-badge-number"><?= (int) $weekData['week_number'] ?></span>
                    <span class="pw-detail-badge-label">هفته</span>
                </div>

                <div class="pw-detail-body">
                    <?php if (!empty($weekData['fetus_size_comparison'])): ?>
                        <p class="pw-detail-summary">
                            اندازه‌ی جنین در این هفته تقریباً <?= htmlspecialchars($weekData['fetus_size_comparison'], ENT_QUOTES, 'UTF-8') ?> است.
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($weekData['development_description'])): ?>
                        <p class="pw-detail-desc">
                            <?= nl2br(htmlspecialchars($weekData['development_description'], ENT_QUOTES, 'UTF-8')) ?>
                        </p>
                    <?php endif; ?>

                    <div class="pw-detail-meta">
                        <?php if (!empty($weekData['fetus_length_cm'])): ?>
                            <span>قد تقریبی: <?= htmlspecialchars((string) $weekData['fetus_length_cm'], ENT_QUOTES, 'UTF-8') ?> سانتی‌متر</span>
                        <?php endif; ?>
                        <?php if (!empty($weekData['fetus_weight_g'])): ?>
                            <span>وزن تقریبی: <?= htmlspecialchars((string) $weekData['fetus_weight_g'], ENT_QUOTES, 'UTF-8') ?> گرم</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="pw-detail-card pw-detail-empty">
                <p>اطلاعات هفته‌ی <?= (int) $currentWeek ?> موقتاً در دسترس نیست؛ لطفاً هفته‌ی دیگه‌ای رو انتخاب کنید.</p>
            </div>
        <?php endif; ?>

        <div class="pw-nav-buttons">
            <a href="pregnancy-weeks.php?week=<?= (int) ($prevWeek ?? $currentWeek) ?>#pw-detail-section"
                class="pw-nav-btn pw-nav-btn-prev" <?= $prevWeek === null ? 'aria-disabled="true"' : '' ?>>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
                هفته قبل
            </a>
            <a href="pregnancy-weeks.php?week=<?= (int) ($nextWeek ?? $currentWeek) ?>#pw-detail-section"
                class="pw-nav-btn pw-nav-btn-next" <?= $nextWeek === null ? 'aria-disabled="true"' : '' ?>>
                هفته بعد
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
            </a>
        </div>
    </section>

    <!-- ============================================================
    تغییرات مادر / نکات پزشکی این هفته
    ============================================================ -->
    <?php if ($weekData && (!empty($weekData['mother_changes']) || !empty($weekData['medical_tips']))): ?>
        <div class="pw-info-grid">
            <?php if (!empty($weekData['mother_changes'])): ?>
                <div class="pw-info-card">
                    <div class="pw-info-card-header">
                        <div class="pw-info-card-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
                        </div>
                        <h3>تغییرات بدن مادر</h3>
                    </div>
                    <p><?= nl2br(htmlspecialchars($weekData['mother_changes'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($weekData['medical_tips'])): ?>
                <div class="pw-info-card">
                    <div class="pw-info-card-header">
                        <div class="pw-info-card-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 1.5-2.5 2-2.5 3.5" /><path d="M12 16.5h.01" /><circle cx="12" cy="12" r="10" /></svg>
                        </div>
                        <h3>نکات پزشکی این هفته</h3>
                    </div>
                    <p><?= nl2br(htmlspecialchars($weekData['medical_tips'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================
    CTA بازاریابی: تشویق به ثبت‌نام برای شخصی‌سازی خودکار
    ============================================================ -->
    <section class="pw-cta-section">
        <div class="pw-cta-box">
            <p>
                الان داری هفته‌ای رو که خودت انتخاب کردی می‌بینی.
                <strong>ثبت‌نام کن</strong> تا هر بار خودکار هفته‌ی واقعی بارداریت رو برات نشون بدیم
                و یادآوری‌ها، مکمل‌ها و ورزش‌های پیشنهادی هم متناسب با شرایط خودت شخصی‌سازی بشن.
            </p>
            <a href="register.php" class="btn-primary pw-cta-btn">ثبت‌نام رایگان</a>
        </div>
    </section>

    <?php include TEMPLATES_PATH . '/partials/footer.php'; ?>

    <script src="assets/js/components/header.js"></script>

</body>

</html>
