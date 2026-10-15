<?php

require_once __DIR__ . '/../config/config.php';

use App\Services\DoctorContactService;

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$activePage = 'doctor-contact';
$userId = (int) $_SESSION['user_id'];
$service = new DoctorContactService();
$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $service->submit($userId, $_POST);
    if ($result['success']) {
        $message = 'پیام شما برای بررسی پزشک ثبت شد.';
        $messageType = 'success';
    } else {
        $message = $result['error'] ?? 'ثبت پیام انجام نشد.';
        $messageType = 'error';
    }
}

$history = $service->history($userId);
$pageTitle = 'ارتباط با پزشک | مراقبت مادر و کودک';
$statusLabels = [
    'new' => 'جدید',
    'reviewed' => 'بررسی‌شده',
    'replied' => 'پاسخ‌داده‌شده',
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
    <link rel="stylesheet" href="assets/css/pages/profile.css">
</head>
<body>
<?php include TEMPLATES_PATH . '/partials/header.php'; ?>

<main class="profile-page">
    <section class="profile-hero">
        <h1>ارتباط با پزشک</h1>
        <p>سؤال یا توضیحی که نیاز به بررسی پزشک دارد اینجا ثبت کنید. این بخش برای موارد اورژانسی نیست.</p>
    </section>

    <?php if ($message !== null): ?>
        <div class="profile-message <?= $messageType === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="profile-card">
        <form method="post">
            <div class="profile-grid">
                <div class="profile-field full">
                    <label for="subject">موضوع *</label>
                    <input id="subject" name="subject" type="text" maxlength="150" required
                        value="<?= htmlspecialchars((string) ($_POST['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="profile-field full">
                    <label for="message">شرح سؤال یا وضعیت *</label>
                    <textarea id="message" name="message" rows="7" maxlength="5000" required><?= htmlspecialchars((string) ($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="profile-actions">
                <button type="submit" class="profile-submit">ارسال برای پزشک</button>
            </div>
        </form>

        <p class="profile-note">اگر علائم شدید، مشکل تنفس، کاهش هوشیاری، تشنج، خونریزی یا وضعیت اورژانسی وجود دارد، منتظر پاسخ این بخش نمانید و از خدمات اورژانسی استفاده کنید.</p>
    </section>

    <?php if ($history): ?>
        <section class="profile-recommendation-section">
            <div class="profile-recommendation-heading">
                <div>
                    <h2>پیام‌های قبلی</h2>
                    <p>وضعیت بررسی درخواست‌هایی که از این حساب ثبت کرده‌اید.</p>
                </div>
            </div>
            <div class="profile-faq-list">
                <?php foreach ($history as $row): ?>
                    <details class="profile-faq-item">
                        <summary>
                            <?= htmlspecialchars($row['subject'], ENT_QUOTES, 'UTF-8') ?>
                            — <?= htmlspecialchars($statusLabels[$row['status']] ?? $row['status'], ENT_QUOTES, 'UTF-8') ?>
                        </summary>
                        <p><?= nl2br(htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <?php if (!empty($row['reply_text'])): ?>
                            <div class="profile-message success" style="margin-top:12px">
                                <strong>پاسخ پزشک:</strong><br>
                                <?= nl2br(htmlspecialchars($row['reply_text'], ENT_QUOTES, 'UTF-8')) ?>
                                <?php if (!empty($row['replied_at'])): ?>
                                    <br><small><?= htmlspecialchars($row['replied_at'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <p><small>ثبت: <?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></small></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include TEMPLATES_PATH . '/partials/footer.php'; ?>
<script src="assets/js/components/header.js"></script>
</body>
</html>
