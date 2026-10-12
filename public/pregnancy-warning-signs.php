<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\WarningSignRepository;

$activePage = 'pregnancy-warning-signs';

$warningSignRepository = new WarningSignRepository();

$urgencyLabels = WarningSignRepository::URGENCY_LABELS; // ترتیب از اورژانسی به خفیف، از خود ریپازیتوری
$categoryLabels = WarningSignRepository::CATEGORY_LABELS;

$trimesterLabels = [
    'all'    => 'کل دوران بارداری',
    'first'  => 'سه‌ماهه اول',
    'second' => 'سه‌ماهه دوم',
    'third'  => 'سه‌ماهه سوم',
];

// ============================================================
// فیلتر سطح اورژانسی از querystring (پیش‌فرض: همه)
// ============================================================
$activeUrgency = $_GET['urgency'] ?? 'all';
if ($activeUrgency !== 'all' && !array_key_exists($activeUrgency, $urgencyLabels)) {
    $activeUrgency = 'all';
}

if ($activeUrgency === 'all') {
    $warningSigns = $warningSignRepository->all();
} else {
    $warningSigns = $warningSignRepository->findByUrgency($activeUrgency);
}

// گروه‌بندی بر اساس سطح اورژانسی (چه فیلتر «همه» باشه چه یک سطح خاص)
$groupedSigns = [];
foreach (array_keys($urgencyLabels) as $key) {
    $groupedSigns[$key] = [];
}
foreach ($warningSigns as $item) {
    $key = $item['urgency_level'] ?? 'informational';
    if (!isset($groupedSigns[$key])) {
        $groupedSigns[$key] = [];
    }
    $groupedSigns[$key][] = $item;
}

/**
 * ساخت برچسب بازه‌ی زمانی برای هر علامت، بر اساس سه‌ماهه و هفته‌ی شروع/پایان.
 */
function ws_period_label(array $sign, array $trimesterLabels): string
{
    $trimester = $sign['trimester'] ?? 'all';
    $label = $trimesterLabels[$trimester] ?? 'کل دوران بارداری';

    if (!empty($sign['week_start']) && !empty($sign['week_end'])) {
        $label .= ' (هفته ' . (int) $sign['week_start'] . ' تا ' . (int) $sign['week_end'] . ')';
    }

    return $label;
}

$pageTitle = 'علائم خطر دوران بارداری - مراقبت مادر و کودک';
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
    <link rel="stylesheet" href="assets/css/pages/pregnancy-warning-signs.css">
</head>

<body>

    <!-- ============================================================
    هیرو صفحه، متصل به هدر (دقیقاً مثل صفحات دیگر بارداری)
    ============================================================ -->
    <section class="wsg-hero" data-header-offset>

        <?php include TEMPLATES_PATH . '/partials/header.php'; ?>

        <div class="wsg-hero-banner">
            <!--
                تصویر پیشنهادی: یک تصویر/ایلاستریشن افقی (نسبت تقریبی ۲.۴:۱، حدود ۱۹۰۰×۸۰۰ پیکسل)
                با حال‌وهوای اطمینان‌بخش و آرام (نه ترسناک)؛ مثلاً یک زن باردار در حال تماس با پزشک
                یا مشورت آرام با ماما. پالت رنگی هماهنگ با سایت. فایل رو با همین اسم توی
                public/assets/image/ قرار بده:
            -->
            <img src="assets/image/pregnancy-warning-signs-banner.png" alt="علائم خطر دوران بارداری" class="wsg-hero-image" />

            <div class="wsg-hero-content">
                <span class="wsg-hero-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
                    راهنمای شناخت علائم خطر
                </span>
                <h1>علائم خطر دوران بارداری</h1>
                <p class="subtitle">
                    خیلی از تغییرات بدن در بارداری طبیعی‌اند، اما بعضی علائم نیاز به توجه فوری دارند.
                    اینجا می‌تونید بر اساس درجه‌ی اهمیت هر علامت، بفهمید کِی باید نگران باشید،
                    کِی با پزشک تماس بگیرید و کِی باید مستقیم به بیمارستان مراجعه کنید.
                </p>
            </div>
        </div>

    </section>

    <!-- ============================================================
    هشدار اورژانسی همیشه‌نمایان
    ============================================================ -->
    <section class="wsg-emergency-alert-section">
        <div class="wsg-emergency-alert">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
            <p>
                <strong>در صورت مشاهده‌ی هر یک از علائم اورژانسی،</strong>
                فوراً با اورژانس <strong>۱۱۵</strong> تماس بگیرید یا به نزدیک‌ترین بیمارستان مراجعه کنید؛
                منتظر نمونید تا وضعیت بدتر بشه.
            </p>
        </div>
    </section>

    <!-- ============================================================
    تب‌های فیلتر سطح اورژانسی
    ============================================================ -->
    <section class="wsg-filter-section">
        <div class="wsg-filter-tabs">
            <a href="pregnancy-warning-signs.php?urgency=all#wsg-list-section"
                class="wsg-filter-tab <?= $activeUrgency === 'all' ? 'active' : '' ?>">
                همه
            </a>
            <?php foreach ($urgencyLabels as $key => $label): ?>
                <a href="pregnancy-warning-signs.php?urgency=<?= urlencode($key) ?>#wsg-list-section"
                    class="wsg-filter-tab wsg-filter-tab-<?= $key ?> <?= $activeUrgency === $key ? 'active' : '' ?>">
                    <span class="wsg-filter-dot"></span>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ============================================================
    لیست علائم (گروه‌بندی‌شده بر اساس سطح اورژانسی)
    ============================================================ -->
    <section class="wsg-list-section" id="wsg-list-section">

        <?php $hasAnySign = false; ?>
        <?php foreach ($groupedSigns as $urgencyKey => $signs): ?>
            <?php if (empty($signs)) continue; ?>
            <?php $hasAnySign = true; ?>

            <div class="wsg-group">
                <h2 class="wsg-group-title wsg-group-title-<?= $urgencyKey ?>">
                    <span class="wsg-group-dot"></span>
                    <?= htmlspecialchars($urgencyLabels[$urgencyKey] ?? $urgencyKey, ENT_QUOTES, 'UTF-8') ?>
                </h2>

                <div class="wsg-sign-grid">
                    <?php foreach ($signs as $sign): ?>
                        <div class="wsg-sign-card wsg-sign-card-<?= $urgencyKey ?>">
                            <div class="wsg-sign-header">
                                <h3><?= htmlspecialchars($sign['symptom_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <span class="wsg-category-chip">
                                    <?= htmlspecialchars($categoryLabels[$sign['category']] ?? $sign['category'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>

                            <span class="wsg-period-chip"><?= htmlspecialchars(ws_period_label($sign, $trimesterLabels), ENT_QUOTES, 'UTF-8') ?></span>

                            <?php if (!empty($sign['description'])): ?>
                                <p class="wsg-sign-desc"><?= nl2br(htmlspecialchars($sign['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($sign['possible_causes'])): ?>
                                <div class="wsg-sign-row">
                                    <span class="wsg-sign-row-label">علل احتمالی</span>
                                    <span class="wsg-sign-row-value"><?= nl2br(htmlspecialchars($sign['possible_causes'], ENT_QUOTES, 'UTF-8')) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($sign['action_required'])): ?>
                                <div class="wsg-sign-action wsg-sign-action-<?= $urgencyKey ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><path d="m22 4-10 10-3-3" /></svg>
                                    <p><?= nl2br(htmlspecialchars($sign['action_required'], ENT_QUOTES, 'UTF-8')) ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="wsg-sign-badges">
                                <?php if (!empty($sign['go_to_hospital'])): ?>
                                    <span class="wsg-badge wsg-badge-hospital">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" /><rect x="3" y="3" width="18" height="18" rx="2" /></svg>
                                        مراجعه به بیمارستان
                                    </span>
                                <?php elseif (!empty($sign['call_doctor'])): ?>
                                    <span class="wsg-badge wsg-badge-call">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" /></svg>
                                        تماس با پزشک
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($sign['immediate_action'])): ?>
                                    <span class="wsg-badge wsg-badge-immediate">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                                        اقدام فوری
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$hasAnySign): ?>
            <div class="wsg-empty-state">
                <p>اطلاعات علائم خطر موقتاً در دسترس نیست؛ لطفاً بعداً دوباره سر بزنید.</p>
            </div>
        <?php endif; ?>

    </section>

    <!-- ============================================================
    تذکر پزشکی کلی
    ============================================================ -->
    <section class="wsg-disclaimer-section">
        <div class="wsg-disclaimer-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 1.5-2.5 2-2.5 3.5" /><path d="M12 16.5h.01" /><circle cx="12" cy="12" r="10" /></svg>
            <p>
                این راهنما جایگزین معاینه و تشخیص پزشک نیست. اگر نسبت به هر تغییری در بدن‌تان مطمئن نیستید،
                حتی اگر توی این لیست هم نبود، بهتره با پزشک یا ماما خودتان تماس بگیرید.
            </p>
        </div>
    </section>

    <!-- ============================================================
    CTA بازاریابی: تشویق به ثبت‌نام برای شخصی‌سازی خودکار
    ============================================================ -->
    <section class="wsg-cta-section">
        <div class="wsg-cta-box">
            <p>
                همین حالا داری راهنمای کلی علائم خطر رو می‌بینی.
                <strong>ثبت‌نام کن</strong> تا بر اساس هفته‌ی دقیق بارداریت، علائم مهم‌تر برات
                برجسته‌تر نشون داده بشن.
            </p>
            <a href="register.php" class="btn-primary wsg-cta-btn">ثبت‌نام رایگان</a>
        </div>
    </section>

    <?php include TEMPLATES_PATH . '/partials/footer.php'; ?>

    <script src="assets/js/components/header.js"></script>

</body>

</html>
