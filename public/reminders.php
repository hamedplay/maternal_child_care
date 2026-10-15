<?php

require_once __DIR__ . '/../config/config.php';

use App\Services\BaleLinkService;
use App\Services\ReminderService;

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$activePage = 'reminders';
$userId = (int) $_SESSION['user_id'];
$reminderService = new ReminderService();
$baleLinkService = new BaleLinkService();
$message = null;
$messageType = null;
$linkCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create');

    if ($action === 'generate_bale_code') {
        try {
            $linkCode = $baleLinkService->createCode($userId);
            $message = 'کد اتصال بله ساخته شد. این کد ۱۵ دقیقه اعتبار دارد.';
            $messageType = 'success';
        } catch (Throwable $e) {
            error_log('reminders.php generate Bale code خطا: ' . $e->getMessage());
            $message = 'ساخت کد اتصال انجام نشد. Migration بله/یادآوری باید روی دیتابیس اجرا شده باشد.';
            $messageType = 'error';
        }
    } elseif ($action === 'cancel') {
        $id = (int) ($_POST['reminder_id'] ?? 0);
        $cancelled = $id > 0 && $reminderService->cancel($userId, $id);
        $message = $cancelled ? 'یادآوری لغو شد.' : 'این یادآوری قابل لغو نبود.';
        $messageType = $cancelled ? 'success' : 'error';
    } else {
        $result = $reminderService->create($userId, $_POST);
        $message = $result['success'] ? 'یادآوری ثبت شد.' : ($result['error'] ?? 'ثبت یادآوری انجام نشد.');
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

$channel = $baleLinkService->channelForUser($userId);
$reminders = $reminderService->history($userId);
$config = require BASE_PATH . '/config/services.php';
$botUsername = trim((string) ($config['bale']['bot_username'] ?? ''));
$pageTitle = 'یادآوری‌ها | مراقبت مادر و کودک';
$typeLabels = ['appointment' => 'نوبت پزشک', 'medication' => 'دارو'];
$statusLabels = ['pending' => 'در انتظار', 'sent' => 'ارسال‌شده', 'failed' => 'ناموفق', 'cancelled' => 'لغوشده'];
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
        <h1>یادآوری نوبت پزشک و دارو</h1>
        <p>زمان رویداد و زمان یادآوری را ثبت کنید؛ پیام در زمان تعیین‌شده از طریق بازوی بله ارسال می‌شود.</p>
    </section>

    <?php if ($message !== null): ?>
        <div class="profile-message <?= $messageType === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="profile-card">
        <h2>اتصال بله</h2>
        <?php if ($channel && (int) $channel['is_active'] === 1): ?>
            <div class="profile-message success">بله به حساب شما متصل است و Reminderهای فعال می‌توانند ارسال شوند.</div>
        <?php else: ?>
            <p class="profile-note">
                برای ارسال Reminder باید حساب بله را به سامانه وصل کنید.
                <?php if ($botUsername !== ''): ?>بازوی <strong>@<?= htmlspecialchars($botUsername, ENT_QUOTES, 'UTF-8') ?></strong> را در بله باز کنید.<?php endif; ?>
            </p>

            <form method="post">
                <input type="hidden" name="action" value="generate_bale_code">
                <button type="submit" class="profile-submit">ساخت کد اتصال بله</button>
            </form>

            <?php if ($linkCode !== null): ?>
                <div class="profile-recommendation-section">
                    <h3>کد اتصال</h3>
                    <p>در بازوی بله این دستور را دقیقاً ارسال کنید:</p>
                    <p><strong><bdi dir="ltr">/start <?= htmlspecialchars($linkCode['code'], ENT_QUOTES, 'UTF-8') ?></bdi></strong></p>
                    <p class="profile-note">اعتبار تا <?= htmlspecialchars($linkCode['expires_at'], ENT_QUOTES, 'UTF-8') ?> به وقت سرور.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="profile-card" style="margin-top:28px">
        <h2>ثبت یادآوری</h2>
        <form method="post">
            <input type="hidden" name="action" value="create">
            <div class="profile-grid">
                <div class="profile-field">
                    <label for="reminder_type">نوع یادآوری</label>
                    <select id="reminder_type" name="reminder_type" required>
                        <option value="appointment">نوبت پزشک</option>
                        <option value="medication">دارو</option>
                    </select>
                </div>
                <div class="profile-field">
                    <label for="title">عنوان *</label>
                    <input id="title" name="title" type="text" maxlength="180" required>
                </div>
                <div class="profile-field">
                    <label for="event_at">زمان نوبت / مصرف *</label>
                    <input id="event_at" name="event_at" type="datetime-local" required>
                </div>
                <div class="profile-field">
                    <label for="remind_at">زمان ارسال یادآوری *</label>
                    <input id="remind_at" name="remind_at" type="datetime-local" required>
                </div>
                <div class="profile-field full">
                    <label for="notes">یادداشت</label>
                    <textarea id="notes" name="notes" rows="4" maxlength="3000"></textarea>
                </div>
            </div>
            <div class="profile-actions">
                <button type="submit" class="profile-submit">ثبت یادآوری</button>
            </div>
        </form>
    </section>

    <?php if ($reminders): ?>
        <section class="profile-recommendation-section">
            <div class="profile-recommendation-heading">
                <div><h2>یادآوری‌های من</h2><p>وضعیت ارسال Reminderهای ثبت‌شده.</p></div>
            </div>
            <div class="profile-faq-list">
                <?php foreach ($reminders as $row): ?>
                    <div class="profile-faq-item">
                        <strong><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>
                            <?= htmlspecialchars($typeLabels[$row['reminder_type']] ?? $row['reminder_type'], ENT_QUOTES, 'UTF-8') ?> —
                            <?= htmlspecialchars($statusLabels[$row['status']] ?? $row['status'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p>رویداد: <?= htmlspecialchars($row['event_at'], ENT_QUOTES, 'UTF-8') ?> | یادآوری: <?= htmlspecialchars($row['remind_at'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if (!empty($row['notes'])): ?><p><?= nl2br(htmlspecialchars($row['notes'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
                        <?php if ($row['status'] === 'pending'): ?>
                            <form method="post">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="reminder_id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="profile-submit">لغو یادآوری</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include TEMPLATES_PATH . '/partials/footer.php'; ?>
<script src="assets/js/components/header.js"></script>
</body>
</html>
