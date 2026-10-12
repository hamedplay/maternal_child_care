<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\ExerciseRepository;

$activePage = 'pregnancy-exercises';

$exerciseRepository = new ExerciseRepository();

$trimesterLabels = ExerciseRepository::TRIMESTER_LABELS;
$categoryLabels = ExerciseRepository::CATEGORY_LABELS;
$intensityLabels = ExerciseRepository::INTENSITY_LABELS;

// ============================================================
// فیلتر سه‌ماهه از querystring (پیش‌فرض: همه)
// ============================================================
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
    $exercises = $exerciseRepository->all();
} else {
    $exercises = $exerciseRepository->findByTrimester($activeTrimester);
}

// وقتی «همه» انتخاب شده، ورزش‌ها رو زیر سرتیتر هر سه‌ماهه گروه‌بندی می‌کنیم
$groupedExercises = [];
if ($activeTrimester === 'all') {
    foreach (array_keys($trimesterLabels) as $key) {
        $groupedExercises[$key] = [];
    }
    foreach ($exercises as $item) {
        $key = $item['trimester'] ?? 'all';
        if (!isset($groupedExercises[$key])) {
            $groupedExercises[$key] = [];
        }
        $groupedExercises[$key][] = $item;
    }
} else {
    $groupedExercises[$activeTrimester] = $exercises;
}

/**
 * بازه‌ی هفته‌ی مناسب یک ورزش رو برای نمایش می‌سازه (اگه ثبت نشده باشه، چیزی نشون نمی‌ده).
 */
function ex_week_label(array $exercise): ?string
{
    if (!empty($exercise['week_start']) && !empty($exercise['week_end'])) {
        return 'هفته ' . (int) $exercise['week_start'] . ' تا ' . (int) $exercise['week_end'];
    }

    return null;
}

$pageTitle = 'ورزش‌های مناسب دوران بارداری - مراقبت مادر و کودک';
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
    <link rel="stylesheet" href="assets/css/pages/pregnancy-exercises.css">
</head>

<body>

    <!-- ============================================================
    هیرو صفحه، متصل به هدر (دقیقاً مثل صفحات دیگر بارداری)
    ============================================================ -->
    <section class="pe-hero" data-header-offset>

        <?php include TEMPLATES_PATH . '/partials/header.php'; ?>

        <div class="pe-hero-banner">
            <!--
                تصویر پیشنهادی: یک تصویر/ایلاستریشن افقی (نسبت تقریبی ۲.۴:۱، حدود ۱۹۰۰×۸۰۰ پیکسل)
                که ورزش سبک بارداری رو نشون بده؛ مثلاً یک زن باردار در حال یوگا یا پیاده‌روی آرام.
                پالت رنگی هماهنگ با سایت (سبزآبی/سرمه‌ای روی زمینه‌ی روشن). فایل رو با همین اسم توی
                public/assets/image/ قرار بده:
            -->
            <img src="assets/image/pregnancy-exercises-banner.png" alt="ورزش‌های مناسب دوران بارداری" class="pe-hero-image" />

            <div class="pe-hero-content">
                <span class="pe-hero-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 6.5 17.5 17.5" /><path d="m21 21-1-1" /><path d="m3 3 1 1" /><path d="m18 22 4-4" /><path d="m2 6 4-4" /><path d="m3 10 7-7" /><path d="m14 21 7-7" /></svg>
                    راهنمای فعالیت بدنی ایمن
                </span>
                <h1>ورزش‌های مناسب دوران بارداری</h1>
                <p class="subtitle">
                    فعالیت بدنی سبک و منظم به سلامت شما و جنین کمک می‌کنه. اینجا می‌بینید توی هر سه‌ماهه
                    چه ورزش‌هایی مناسب‌اند، چطور درست انجام‌شون بدید و کجاها باید احتیاط بیشتری داشته باشید
                    — همیشه پیش از شروع هر برنامه‌ی ورزشی با پزشک‌تان مشورت کنید.
                </p>
            </div>
        </div>

    </section>

    <!-- ============================================================
    تب‌های فیلتر سه‌ماهه
    ============================================================ -->
    <section class="pe-filter-section">
        <div class="pe-filter-tabs">
            <?php foreach ($trimesterLabels as $key => $label): ?>
                <a href="pregnancy-exercises.php?trimester=<?= urlencode($key) ?>#pe-list-section"
                    class="pe-filter-tab <?= $activeTrimester === $key ? 'active' : '' ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($trimesterWeekRanges[$key]): ?>
                        <span class="pe-filter-tab-range">(هفته <?= htmlspecialchars($trimesterWeekRanges[$key], ENT_QUOTES, 'UTF-8') ?>)</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ============================================================
    لیست ورزش‌ها (گروه‌بندی‌شده بر اساس سه‌ماهه)
    ============================================================ -->
    <section class="pe-list-section" id="pe-list-section">

        <?php $hasAnyExercise = false; ?>
        <?php foreach ($groupedExercises as $groupKey => $groupItems): ?>
            <?php if (empty($groupItems)) continue; ?>
            <?php $hasAnyExercise = true; ?>

            <div class="pe-group">
                <h2 class="pe-group-title">
                    <?= htmlspecialchars($trimesterLabels[$groupKey] ?? $groupKey, ENT_QUOTES, 'UTF-8') ?>
                </h2>

                <div class="pe-exercise-grid">
                    <?php foreach ($groupItems as $exercise): ?>
                        <div class="pe-exercise-card">
                            <div class="pe-exercise-header">
                                <div class="pe-exercise-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 6.5 17.5 17.5" /><path d="m21 21-1-1" /><path d="m3 3 1 1" /><path d="m18 22 4-4" /><path d="m2 6 4-4" /><path d="m3 10 7-7" /><path d="m14 21 7-7" /></svg>
                                </div>
                                <h3><?= htmlspecialchars($exercise['exercise_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            </div>

                            <div class="pe-exercise-tags">
                                <span class="pe-tag pe-tag-category">
                                    <?= htmlspecialchars($categoryLabels[$exercise['category']] ?? $exercise['category'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="pe-tag pe-tag-intensity pe-tag-intensity-<?= htmlspecialchars($exercise['intensity_level'], ENT_QUOTES, 'UTF-8') ?>">
                                    شدت: <?= htmlspecialchars($intensityLabels[$exercise['intensity_level']] ?? $exercise['intensity_level'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php $weekLabel = ex_week_label($exercise); ?>
                                <?php if ($weekLabel): ?>
                                    <span class="pe-tag pe-tag-week"><?= htmlspecialchars($weekLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($exercise['description'])): ?>
                                <p class="pe-exercise-desc"><?= nl2br(htmlspecialchars($exercise['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($exercise['benefits'])): ?>
                                <div class="pe-exercise-row">
                                    <span class="pe-exercise-row-label">فواید</span>
                                    <span class="pe-exercise-row-value"><?= nl2br(htmlspecialchars($exercise['benefits'], ENT_QUOTES, 'UTF-8')) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($exercise['how_to_do'])): ?>
                                <div class="pe-exercise-row">
                                    <span class="pe-exercise-row-label">نحوه‌ی انجام</span>
                                    <span class="pe-exercise-row-value"><?= nl2br(htmlspecialchars($exercise['how_to_do'], ENT_QUOTES, 'UTF-8')) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($exercise['duration'])): ?>
                                <div class="pe-exercise-row">
                                    <span class="pe-exercise-row-label">مدت‌زمان پیشنهادی</span>
                                    <span class="pe-exercise-row-value"><?= htmlspecialchars($exercise['duration'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($exercise['equipment_needed'])): ?>
                                <div class="pe-exercise-row">
                                    <span class="pe-exercise-row-label">وسایل موردنیاز</span>
                                    <span class="pe-exercise-row-value"><?= htmlspecialchars($exercise['equipment_needed'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($exercise['precautions'])): ?>
                                <div class="pe-exercise-warning">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
                                    <p><?= nl2br(htmlspecialchars($exercise['precautions'], ENT_QUOTES, 'UTF-8')) ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="pe-exercise-badges">
                                <?php if (!empty($exercise['is_high_risk'])): ?>
                                    <span class="pe-badge pe-badge-risk">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
                                        نیاز به احتیاط ویژه
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($exercise['requires_doctor_consultation'])): ?>
                                    <span class="pe-badge pe-badge-consult">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" /></svg>
                                        نیاز به تأیید پزشک
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$hasAnyExercise): ?>
            <div class="pe-empty-state">
                <p>اطلاعات ورزش‌ها موقتاً در دسترس نیست؛ لطفاً بعداً دوباره سر بزنید.</p>
            </div>
        <?php endif; ?>

    </section>

    <!-- ============================================================
    تذکر پزشکی کلی
    ============================================================ -->
    <section class="pe-disclaimer-section">
        <div class="pe-disclaimer-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 1.5-2.5 2-2.5 3.5" /><path d="M12 16.5h.01" /><circle cx="12" cy="12" r="10" /></svg>
            <p>
                توانایی بدنی افراد در دوران بارداری متفاوته. اگه حین ورزش دچار درد، سرگیجه، تنگی نفس شدید
                یا خونریزی شدید، فوراً ورزش رو متوقف کنید و با پزشک‌تان تماس بگیرید.
            </p>
        </div>
    </section>

    <!-- ============================================================
    CTA بازاریابی: تشویق به ثبت‌نام برای شخصی‌سازی خودکار
    ============================================================ -->
    <section class="pe-cta-section">
        <div class="pe-cta-box">
            <p>
                همین حالا داری راهنمای کلی ورزش‌ها رو می‌بینی.
                <strong>ثبت‌نام کن</strong> تا بر اساس هفته‌ی دقیق بارداریت، برنامه‌ی ورزشی
                متناسب با شرایط خودت رو دریافت کنی.
            </p>
            <a href="register.php" class="btn-primary pe-cta-btn">ثبت‌نام رایگان</a>
        </div>
    </section>

    <?php include TEMPLATES_PATH . '/partials/footer.php'; ?>

    <script src="assets/js/components/header.js"></script>

</body>

</html>
