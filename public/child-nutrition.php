<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\ChildCareRepository;

$activePage = 'child-nutrition';
$repository = new ChildCareRepository();
$maxAge = max(0, $repository->maxAgeInMonths());
$age = isset($_GET['age']) ? (int) $_GET['age'] : 6;
$age = max(0, min($age, $maxAge));
$nutrition = $repository->findNutritionByAge($age);
$supplements = $repository->findSupplementsByAge($age);
$pageTitle = 'تغذیه کودک | مراقبت مادر و کودک';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components/header.css">
    <link rel="stylesheet" href="assets/css/components/footer.css">
    <link rel="stylesheet" href="assets/css/pages/child-care.css">
</head>
<body>
<?php include TEMPLATES_PATH . '/partials/header.php'; ?>
<section class="cc-hero">
    <h1>تغذیه کودک</h1>
    <p>راهنمای تغذیه و مکمل‌های ثبت‌شده در سامانه را متناسب با سن کودک مشاهده کنید.</p>
</section>
<main class="cc-container">
    <form class="cc-filter" method="get">
        <div class="cc-field">
            <label for="age">سن کودک</label>
            <select id="age" name="age">
                <?php for ($m = 0; $m <= $maxAge; $m++): ?>
                    <option value="<?= $m ?>" <?= $m === $age ? 'selected' : '' ?>><?= $m ?> ماه</option>
                <?php endfor; ?>
            </select>
        </div>
        <button class="cc-button" type="submit">نمایش راهنما</button>
    </form>

    <h2 class="cc-section-title">راهنمای تغذیه در <?= $age ?> ماهگی</h2>
    <?php if ($nutrition): ?>
        <article class="cc-card">
            <h2><?= htmlspecialchars($nutrition['nutrition_stage'], ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="cc-meta">
                <span class="cc-badge"><?= (int) $nutrition['age_start_months'] ?> تا <?= (int) $nutrition['age_end_months'] ?> ماهگی</span>
                <span class="cc-badge"><?= htmlspecialchars($nutrition['meal_frequency'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <p><strong>مواد غذایی توصیه‌شده:</strong> <?= nl2br(htmlspecialchars($nutrition['recommended_foods'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php if (!empty($nutrition['foods_to_avoid'])): ?>
                <div class="cc-warning"><strong>موارد پرهیز:</strong> <?= nl2br(htmlspecialchars($nutrition['foods_to_avoid'], ENT_QUOTES, 'UTF-8')) ?></div>
            <?php endif; ?>
            <?php if (!empty($nutrition['tips'])): ?>
                <p><strong>نکات:</strong> <?= nl2br(htmlspecialchars($nutrition['tips'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>
        </article>
    <?php else: ?>
        <div class="cc-empty">برای این سن راهنمای تغذیه‌ای در دیتابیس موجود نیست.</div>
    <?php endif; ?>

    <h2 class="cc-section-title">مکمل‌های مرتبط با این سن</h2>
    <?php if ($supplements): ?>
        <div class="cc-grid">
            <?php foreach ($supplements as $supplement): ?>
                <article class="cc-card">
                    <h3><?= htmlspecialchars($supplement['supplement_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <div class="cc-meta">
                        <?php if ((int) $supplement['is_essential'] === 1): ?><span class="cc-badge">ضروری در داده مرجع</span><?php endif; ?>
                    </div>
                    <p><?= nl2br(htmlspecialchars($supplement['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <p><strong>فواید:</strong> <?= nl2br(htmlspecialchars($supplement['benefits'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <p><strong>دوز مرجع ثبت‌شده:</strong> <?= htmlspecialchars($supplement['dosage'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if (!empty($supplement['food_sources'])): ?>
                        <p><strong>منابع غذایی:</strong> <?= nl2br(htmlspecialchars($supplement['food_sources'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($supplement['precautions'])): ?>
                        <div class="cc-warning"><?= nl2br(htmlspecialchars($supplement['precautions'], ENT_QUOTES, 'UTF-8')) ?></div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="cc-empty">برای این سن مکمل مشخصی در داده فعلی ثبت نشده است.</div>
    <?php endif; ?>

    <div class="cc-note">مقادیر مکمل صرفاً داده مرجع سامانه هستند؛ شروع، قطع یا تغییر دوز مکمل کودک باید با پزشک یا مراقب سلامت انجام شود.</div>
</main>
<?php include TEMPLATES_PATH . '/partials/footer.php'; ?>
<script src="assets/js/components/header.js"></script>
</body>
</html>
