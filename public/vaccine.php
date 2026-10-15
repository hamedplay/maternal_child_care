<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\ChildCareRepository;

$activePage = 'vaccine';
$repository = new ChildCareRepository();
$maxAge = max(0, $repository->maxAgeInMonths());
$age = isset($_GET['age']) ? (int) $_GET['age'] : 2;
$age = max(0, min($age, $maxAge));
$ageVaccines = $repository->findVaccinationsByAge($age);
$allVaccines = $repository->allVaccinations();
$pageTitle = 'زمان‌بندی واکسن کودک | مراقبت مادر و کودک';
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
    <h1>زمان‌بندی واکسن کودک</h1>
    <p>واکسن‌های ثبت‌شده در برنامه سامانه را بر اساس سن ببینید و در جدول پایین کل زمان‌بندی موجود را مرور کنید.</p>
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
        <button class="cc-button" type="submit">نمایش واکسن‌های این سن</button>
    </form>

    <h2 class="cc-section-title">واکسن‌های مرتبط با <?= $age ?> ماهگی</h2>
    <?php if ($ageVaccines): ?>
        <div class="cc-grid">
            <?php foreach ($ageVaccines as $vaccine): ?>
                <article class="cc-card">
                    <h3><?= htmlspecialchars($vaccine['vaccine_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <div class="cc-meta">
                        <span class="cc-badge">دوز <?= (int) $vaccine['dose_number'] ?></span>
                        <span class="cc-badge">سن توصیه‌شده: <?= (int) $vaccine['age_in_months'] ?> ماه</span>
                    </div>
                    <?php if (!empty($vaccine['description'])): ?>
                        <p><?= nl2br(htmlspecialchars($vaccine['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($vaccine['precautions'])): ?>
                        <div class="cc-warning"><?= nl2br(htmlspecialchars($vaccine['precautions'], ENT_QUOTES, 'UTF-8')) ?></div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="cc-empty">برای این سن واکسنی در جدول فعلی برنامه ثبت نشده است.</div>
    <?php endif; ?>

    <h2 class="cc-section-title">کل برنامه واکسیناسیون موجود</h2>
    <?php if ($allVaccines): ?>
        <div class="cc-table-wrap">
            <table class="cc-table">
                <thead><tr><th>سن</th><th>واکسن</th><th>دوز</th><th>توضیح</th></tr></thead>
                <tbody>
                <?php foreach ($allVaccines as $vaccine): ?>
                    <tr>
                        <td><?= (int) $vaccine['age_in_months'] ?> ماه</td>
                        <td><?= htmlspecialchars($vaccine['vaccine_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $vaccine['dose_number'] ?></td>
                        <td><?= htmlspecialchars((string) ($vaccine['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="cc-empty">برنامه واکسیناسیون در حال حاضر در دسترس نیست.</div>
    <?php endif; ?>

    <div class="cc-note">زمان دقیق واکسیناسیون باید با کارت واکسن و برنامه رسمی مرکز بهداشت کودک تطبیق داده شود؛ در بیماری حاد یا شرایط خاص، تصمیم نهایی با پزشک یا مرکز واکسیناسیون است.</div>
</main>
<?php include TEMPLATES_PATH . '/partials/footer.php'; ?>
<script src="assets/js/components/header.js"></script>
</body>
</html>
