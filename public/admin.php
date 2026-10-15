<?php

require_once __DIR__ . '/../config/config.php';

use App\Services\AdminService;

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$adminUserId = (int) $_SESSION['user_id'];
$service = new AdminService();

if (!$service->isAdmin($adminUserId)) {
    http_response_code(403);
    echo 'دسترسی به پنل مدیریت مجاز نیست.';
    exit;
}

$activePage = 'admin';
$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'doctor_reply') {
        $result = $service->replyDoctor(
            $adminUserId,
            (int) ($_POST['message_id'] ?? 0),
            (string) ($_POST['reply'] ?? '')
        );
    } elseif ($action === 'doctor_reviewed') {
        $result = $service->markDoctorReviewed(
            $adminUserId,
            (int) ($_POST['message_id'] ?? 0)
        );
    } elseif ($action === 'support_status') {
        $result = $service->updateSupportStatus(
            $adminUserId,
            (int) ($_POST['message_id'] ?? 0),
            (string) ($_POST['status'] ?? '')
        );
    } else {
        $result = ['success' => false, 'error' => 'عملیات مدیریتی معتبر نیست.'];
    }

    $message = $result['success'] ? 'تغییر با موفقیت ثبت شد.' : ($result['error'] ?? 'عملیات انجام نشد.');
    $messageType = $result['success'] ? 'success' : 'error';
}

$data = $service->dashboard();
$counts = $data['counts'];
$pageTitle = 'پنل مدیریت | مراقبت مادر و کودک';
$supportStatusLabels = ['pending' => 'در انتظار', 'in_progress' => 'در حال بررسی', 'resolved' => 'حل‌شده'];
$doctorStatusLabels = ['new' => 'جدید', 'reviewed' => 'بررسی‌شده', 'replied' => 'پاسخ‌داده‌شده'];
$reminderStatusLabels = ['pending' => 'در انتظار', 'sent' => 'ارسال‌شده', 'failed' => 'ناموفق', 'cancelled' => 'لغوشده'];
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
    <link rel="stylesheet" href="assets/css/pages/admin.css">
</head>
<body>
<?php include TEMPLATES_PATH . '/partials/header.php'; ?>

<main class="admin-page">
    <section class="admin-hero">
        <h1>پنل مدیریت</h1>
        <p>مدیریت بخش‌هایی که در Scope این پروژه پیاده‌سازی شده‌اند: کاربران، پیام پزشک، پشتیبانی، یادآوری و گفتگوهای AI.</p>
    </section>

    <?php if ($message !== null): ?>
        <div class="admin-message <?= $messageType === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="admin-stats">
        <div class="admin-stat"><strong><?= (int) $counts['users'] ?></strong><span>کاربر</span></div>
        <div class="admin-stat"><strong><?= (int) $counts['doctor_new'] ?></strong><span>پیام جدید پزشک</span></div>
        <div class="admin-stat"><strong><?= (int) $counts['support_open'] ?></strong><span>پشتیبانی باز</span></div>
        <div class="admin-stat"><strong><?= (int) $counts['reminders_pending'] ?></strong><span>یادآوری در انتظار</span></div>
        <div class="admin-stat"><strong><?= (int) $counts['ai_messages'] ?></strong><span>پیام AI ذخیره‌شده</span></div>
    </section>

    <section class="admin-section">
        <h2>ارتباط با پزشک</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>کاربر</th><th>موضوع / پیام</th><th>وضعیت</th><th>پاسخ / عملیات</th></tr></thead>
                <tbody>
                <?php foreach ($data['doctor_messages'] as $row): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars((string) ($row['full_name'] ?: '-'), ENT_QUOTES, 'UTF-8') ?><br>
                            <span class="admin-muted"><bdi dir="ltr"><?= htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8') ?></bdi></span>
                        </td>
                        <td class="admin-content">
                            <strong><?= htmlspecialchars($row['subject'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <?= nl2br(htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8')) ?><br>
                            <span class="admin-muted"><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><span class="admin-badge"><?= htmlspecialchars($doctorStatusLabels[$row['status']] ?? $row['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <?php if ($row['status'] === 'new'): ?>
                                <form method="post" class="admin-form-inline" style="margin-bottom:8px">
                                    <input type="hidden" name="action" value="doctor_reviewed">
                                    <input type="hidden" name="message_id" value="<?= (int) $row['id'] ?>">
                                    <button class="admin-button secondary" type="submit">علامت بررسی‌شده</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" class="admin-form-inline">
                                <input type="hidden" name="action" value="doctor_reply">
                                <input type="hidden" name="message_id" value="<?= (int) $row['id'] ?>">
                                <textarea name="reply" maxlength="5000" required><?= htmlspecialchars((string) ($row['reply_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                <button class="admin-button" type="submit">ثبت پاسخ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($data['doctor_messages'])): ?><tr><td colspan="4">پیامی موجود نیست یا Migration مربوط هنوز اجرا نشده است.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-section">
        <h2>پیام‌های پشتیبانی</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>فرستنده</th><th>نوع</th><th>پیام</th><th>وضعیت</th></tr></thead>
                <tbody>
                <?php foreach ($data['support_messages'] as $row): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars((string) ($row['full_name'] ?: '-'), ENT_QUOTES, 'UTF-8') ?><br>
                            <span class="admin-muted"><?= htmlspecialchars((string) ($row['email'] ?: $row['phone_number'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><?= htmlspecialchars($row['message_type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="admin-content"><?= nl2br(htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8')) ?></td>
                        <td>
                            <form method="post" class="admin-form-inline">
                                <input type="hidden" name="action" value="support_status">
                                <input type="hidden" name="message_id" value="<?= (int) $row['id'] ?>">
                                <select name="status">
                                    <?php foreach ($supportStatusLabels as $status => $label): ?>
                                        <option value="<?= $status ?>" <?= $row['status'] === $status ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="admin-button" type="submit">ذخیره</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($data['support_messages'])): ?><tr><td colspan="4">پیام پشتیبانی موجود نیست.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-section">
        <h2>کاربران و پروفایل</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>ID</th><th>نام / شماره</th><th>مرحله</th><th>اطلاعات مرتبط</th><th>آخرین ورود</th></tr></thead>
                <tbody>
                <?php foreach ($data['users'] as $row): ?>
                    <tr>
                        <td><?= (int) $row['id'] ?></td>
                        <td><?= htmlspecialchars((string) ($row['full_name'] ?: '-'), ENT_QUOTES, 'UTF-8') ?><br><bdi dir="ltr"><?= htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8') ?></bdi></td>
                        <td><?= htmlspecialchars((string) ($row['stage'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if (!empty($row['pregnancy_due_date'])): ?>زایمان: <?= htmlspecialchars($row['pregnancy_due_date'], ENT_QUOTES, 'UTF-8') ?><br><?php endif; ?>
                            <?php if (!empty($row['child_name'])): ?>کودک: <?= htmlspecialchars($row['child_name'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                            <?php if (!empty($row['child_birth_date'])): ?> — <?= htmlspecialchars($row['child_birth_date'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string) ($row['last_login_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-section">
        <h2>یادآوری‌ها</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>کاربر</th><th>عنوان</th><th>نوع</th><th>رویداد / یادآوری</th><th>وضعیت</th><th>خطا</th></tr></thead>
                <tbody>
                <?php foreach ($data['reminders'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($row['full_name'] ?: $row['phone']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['reminder_type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['event_at'], ENT_QUOTES, 'UTF-8') ?><br><span class="admin-muted">ارسال: <?= htmlspecialchars($row['remind_at'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars($reminderStatusLabels[$row['status']] ?? $row['status'], ENT_QUOTES, 'UTF-8') ?> (<?= (int) $row['attempts'] ?> تلاش)</td>
                        <td class="admin-content"><?= htmlspecialchars((string) ($row['last_error'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($data['reminders'])): ?><tr><td colspan="6">Reminder موجود نیست یا Migration مربوط اجرا نشده است.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-section">
        <h2>پیام‌های اخیر AI</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Conversation</th><th>کاربر</th><th>Role</th><th>محتوا</th><th>زمان</th></tr></thead>
                <tbody>
                <?php foreach ($data['ai_messages'] as $row): ?>
                    <tr>
                        <td>#<?= (int) $row['conversation_id'] ?></td>
                        <td><?= htmlspecialchars((string) ($row['full_name'] ?: $row['phone'] ?: 'مهمان'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="admin-content"><?= nl2br(htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8')) ?></td>
                        <td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($data['ai_messages'])): ?><tr><td colspan="5">پیام AI موجود نیست یا Migration AI اجرا نشده است.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include TEMPLATES_PATH . '/partials/footer.php'; ?>
<script src="assets/js/components/header.js"></script>
</body>
</html>
