<?php

require_once __DIR__ . '/../config/config.php';

use App\Services\UserProfileService;

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$activePage = 'profile';
$userId = (int) $_SESSION['user_id'];
$service = new UserProfileService();
$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $service->save($userId, $_POST);
    if ($result['success']) {
        $message = 'اطلاعات پروفایل با موفقیت ذخیره شد.';
        $messageType = 'success';
    } else {
        $message = $result['error'] ?? 'ذخیره پروفایل با خطا مواجه شد.';
        $messageType = 'error';
    }
}

$data = $service->get($userId);
$user = $data['user'] ?? [];
$profile = $data['profile'] ?? [];

$stage = $profile['stage'] ?? 'planning';
$childGender = $profile['child_gender'] ?? 'unknown';
$pageTitle = 'پروفایل من | مراقبت مادر و کودک';
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
    <link rel="stylesheet" href="assets/css/pages/profile.css">
</head>
<body>
<?php include TEMPLATES_PATH . '/partials/header.php'; ?>

<main class="profile-page">
    <section class="profile-hero">
        <h1>پروفایل من</h1>
        <p>اطلاعاتی که اینجا وارد می‌کنید برای نمایش محتوای متناسب با مرحله بارداری یا سن کودک استفاده می‌شود.</p>
    </section>

    <?php if ($message !== null): ?>
        <div class="profile-message <?= $messageType === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="profile-card">
        <form method="post">
            <div class="profile-grid">
                <div class="profile-field">
                    <label for="full_name">نام و نام خانوادگی</label>
                    <input id="full_name" name="full_name" type="text" maxlength="150" required
                        value="<?= htmlspecialchars((string) ($user['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="profile-field">
                    <label for="phone">شماره موبایل</label>
                    <input id="phone" type="text" readonly
                        value="<?= htmlspecialchars((string) ($user['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="profile-field">
                    <label for="birth_date">تاریخ تولد</label>
                    <input id="birth_date" name="birth_date" type="date"
                        value="<?= htmlspecialchars((string) ($profile['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="profile-field">
                    <label for="stage">مرحله فعلی</label>
                    <select id="stage" name="stage">
                        <option value="planning" <?= $stage === 'planning' ? 'selected' : '' ?>>قصد بارداری</option>
                        <option value="pregnant" <?= $stage === 'pregnant' ? 'selected' : '' ?>>باردار</option>
                        <option value="postpartum" <?= $stage === 'postpartum' ? 'selected' : '' ?>>پس از زایمان</option>
                        <option value="child_care" <?= $stage === 'child_care' ? 'selected' : '' ?>>مراقبت کودک</option>
                    </select>
                </div>

                <h2 class="profile-section-title">اطلاعات بارداری</h2>

                <div class="profile-field">
                    <label for="pregnancy_due_date">تاریخ تقریبی زایمان</label>
                    <input id="pregnancy_due_date" name="pregnancy_due_date" type="date"
                        value="<?= htmlspecialchars((string) ($profile['pregnancy_due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <h2 class="profile-section-title">اطلاعات کودک</h2>

                <div class="profile-field">
                    <label for="child_name">نام کودک</label>
                    <input id="child_name" name="child_name" type="text" maxlength="100"
                        value="<?= htmlspecialchars((string) ($profile['child_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="profile-field">
                    <label for="child_birth_date">تاریخ تولد کودک</label>
                    <input id="child_birth_date" name="child_birth_date" type="date"
                        value="<?= htmlspecialchars((string) ($profile['child_birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="profile-field">
                    <label for="child_gender">جنسیت کودک</label>
                    <select id="child_gender" name="child_gender">
                        <option value="unknown" <?= $childGender === 'unknown' ? 'selected' : '' ?>>مشخص نشده</option>
                        <option value="boy" <?= $childGender === 'boy' ? 'selected' : '' ?>>پسر</option>
                        <option value="girl" <?= $childGender === 'girl' ? 'selected' : '' ?>>دختر</option>
                    </select>
                </div>
            </div>

            <div class="profile-actions">
                <button type="submit" class="profile-submit">ذخیره پروفایل</button>
            </div>
        </form>

        <p class="profile-note">شماره موبایل از سیستم ورود OTP می‌آید و در این صفحه قابل تغییر نیست.</p>
    </section>
</main>

<?php include TEMPLATES_PATH . '/partials/footer.php'; ?>
<script src="assets/js/components/header.js"></script>
</body>
</html>
