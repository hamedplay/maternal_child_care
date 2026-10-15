<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\ChildCareRepository;

$activePage = 'conditions';
$repository = new ChildCareRepository();
$conditions = $repository->allConditions();
$pageTitle = 'مشکلات شایع کودک | مراقبت مادر و کودک';
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
    <h1>مشکلات شایع کودک</h1>
    <p>علائم، مراقبت خانگی و زمان مراجعه به پزشک را بر اساس داده‌های فعلی سامانه مرور کنید.</p>
</section>
<main class="cc-container">
    <?php if ($conditions): ?>
        <div class="cc-grid">
            <?php foreach ($conditions as $condition): ?>
                <article class="cc-card">
                    <h2><?= htmlspecialchars($condition['condition_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="cc-meta"><span class="cc-badge"><?= htmlspecialchars($condition['age_group'], ENT_QUOTES, 'UTF-8') ?></span></div>
                    <p><?= nl2br(htmlspecialchars($condition['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <p><strong>علائم:</strong> <?= nl2br(htmlspecialchars($condition['symptoms'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <p><strong>مراقبت خانگی:</strong> <?= nl2br(htmlspecialchars($condition['home_care'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <div class="cc-warning"><strong>چه زمانی به پزشک مراجعه کنیم:</strong> <?= nl2br(htmlspecialchars($condition['when_to_see_doctor'], ENT_QUOTES, 'UTF-8')) ?></div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="cc-empty">اطلاعات مشکلات شایع کودک در حال حاضر در دسترس نیست.</div>
    <?php endif; ?>

    <div class="cc-note">این صفحه برای آموزش و راهنمایی عمومی است. در علائم شدید، کاهش هوشیاری، مشکل تنفس، تشنج، کم‌آبی واضح یا هر وضعیت نگران‌کننده، ارزیابی پزشکی را به تأخیر نیندازید.</div>
</main>
<?php include TEMPLATES_PATH . '/partials/footer.php'; ?>
<script src="assets/js/components/header.js"></script>
</body>
</html>
