<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\DevelopmentMilestoneRepository;

$activePage = 'milestones';
$repository = new DevelopmentMilestoneRepository();
$maxAge = max(0, $repository->maxAgeInMonths());
$age = isset($_GET['age']) ? (int) $_GET['age'] : min(6, $maxAge);
$age = max(0, min($age, $maxAge));
$milestones = $repository->findByAgeMonths($age);
$pageTitle = 'مراحل تکامل کودک | مراقبت مادر و کودک';

$categoryLabels = [
    'motor' => 'حرکتی',
    'language' => 'زبان و گفتار',
    'cognitive' => 'شناختی',
    'social_emotional' => 'اجتماعی و عاطفی',
];
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
    <h1>مراحل تکامل کودک</h1>
    <p>توانایی‌های رشدی ثبت‌شده برای هر بازه سنی را در حوزه‌های حرکتی، زبانی، شناختی و اجتماعی/عاطفی مرور کنید.</p>
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
        <button class="cc-button" type="submit">نمایش مراحل این سن</button>
    </form>

    <?php if ($milestones): ?>
        <div class="cc-grid">
            <?php foreach ($milestones as $milestone): ?>
                <article class="cc-card">
                    <h3><?= htmlspecialchars($milestone['milestone_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <div class="cc-meta">
                        <span class="cc-badge"><?= htmlspecialchars($categoryLabels[$milestone['category']] ?? $milestone['category'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="cc-badge"><?= (int) $milestone['age_months_start'] ?> تا <?= (int) $milestone['age_months_end'] ?> ماهگی</span>
                    </div>
                    <p><?= nl2br(htmlspecialchars($milestone['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php if (!empty($milestone['warning_signs'])): ?>
                        <div class="cc-warning"><strong>علامت هشدار:</strong> <?= nl2br(htmlspecialchars($milestone['warning_signs'], ENT_QUOTES, 'UTF-8')) ?></div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="cc-empty">برای این سن مرحله تکاملی در دیتابیس فعلی ثبت نشده است.</div>
    <?php endif; ?>

    <div class="cc-note">سن رسیدن به milestoneها در کودکان می‌تواند متفاوت باشد. وجود علامت هشدار یا نگرانی درباره روند تکامل نیازمند ارزیابی پزشک کودک است.</div>
</main>
<?php include TEMPLATES_PATH . '/partials/footer.php'; ?>
<script src="assets/js/components/header.js"></script>
</body>
</html>
