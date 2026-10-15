<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\GrowthStandardRepository;

$activePage = 'growth';
$repository = new GrowthStandardRepository();
$maxAge = max(0, $repository->maxAgeInMonths());

$age = isset($_GET['age']) ? (int) $_GET['age'] : min(6, $maxAge);
$age = max(0, min($age, $maxAge));
$gender = (isset($_GET['gender']) && $_GET['gender'] === 'girl') ? 'girl' : 'boy';
$data = $repository->findByGenderAndAge($gender, $age);
$pageTitle = 'رشد کودک | مراقبت مادر و کودک';
$genderLabel = $gender === 'girl' ? 'دختر' : 'پسر';
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
    <h1>رشد کودک</h1>
    <p>استانداردهای رشد ثبت‌شده در سامانه را بر اساس سن و جنسیت ببینید. این اعداد مرجع آماری هستند و جایگزین ارزیابی پزشک نیستند.</p>
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
        <div class="cc-field">
            <label for="gender">جنسیت</label>
            <select id="gender" name="gender">
                <option value="boy" <?= $gender === 'boy' ? 'selected' : '' ?>>پسر</option>
                <option value="girl" <?= $gender === 'girl' ? 'selected' : '' ?>>دختر</option>
            </select>
        </div>
        <button class="cc-button" type="submit">نمایش</button>
    </form>

    <?php if ($data): ?>
        <div class="cc-grid">
            <article class="cc-card">
                <h2>قد</h2>
                <div class="cc-meta"><span class="cc-badge"><?= $age ?> ماهگی - <?= $genderLabel ?></span></div>
                <p>میانه: <strong><?= htmlspecialchars((string) $data['length_p50'], ENT_QUOTES, 'UTF-8') ?> سانتی‌متر</strong></p>
                <p>محدوده صدک ۳ تا ۹۷: <?= htmlspecialchars((string) $data['length_p3'], ENT_QUOTES, 'UTF-8') ?> تا <?= htmlspecialchars((string) $data['length_p97'], ENT_QUOTES, 'UTF-8') ?> سانتی‌متر</p>
            </article>
            <article class="cc-card">
                <h2>وزن</h2>
                <p>میانه: <strong><?= htmlspecialchars((string) $data['weight_p50'], ENT_QUOTES, 'UTF-8') ?> کیلوگرم</strong></p>
                <p>محدوده صدک ۳ تا ۹۷: <?= htmlspecialchars((string) $data['weight_p3'], ENT_QUOTES, 'UTF-8') ?> تا <?= htmlspecialchars((string) $data['weight_p97'], ENT_QUOTES, 'UTF-8') ?> کیلوگرم</p>
            </article>
            <article class="cc-card">
                <h2>دور سر</h2>
                <p>میانه: <strong><?= htmlspecialchars((string) $data['head_p50'], ENT_QUOTES, 'UTF-8') ?> سانتی‌متر</strong></p>
                <?php if ($data['head_p3'] !== null && $data['head_p97'] !== null): ?>
                    <p>محدوده صدک ۳ تا ۹۷: <?= htmlspecialchars((string) $data['head_p3'], ENT_QUOTES, 'UTF-8') ?> تا <?= htmlspecialchars((string) $data['head_p97'], ENT_QUOTES, 'UTF-8') ?> سانتی‌متر</p>
                <?php endif; ?>
            </article>
        </div>
        <div class="cc-note">رشد هر کودک الگوی خودش را دارد. تغییر روند رشد در طول زمان از مقایسه یک عدد منفرد مهم‌تر است؛ برای نگرانی درباره رشد با پزشک کودک مشورت کنید.</div>
    <?php else: ?>
        <div class="cc-empty">برای سن و جنسیت انتخاب‌شده داده رشد در دیتابیس موجود نیست.</div>
    <?php endif; ?>
</main>
<?php include TEMPLATES_PATH . '/partials/footer.php'; ?>
<script src="assets/js/components/header.js"></script>
</body>
</html>
