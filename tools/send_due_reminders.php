<?php

/**
 * ارسال Reminderهای due از طریق بله.
 * پیشنهاد اجرا با Cron: هر 1 دقیقه.
 *
 * php tools/send_due_reminders.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This worker must be executed from CLI.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.php';

use App\Core\Database;
use App\Repositories\ReminderRepository;
use App\Services\BaleService;

$pdo = Database::connection();
$lockName = 'maternal_child_care_send_due_reminders';
$lockStmt = $pdo->prepare('SELECT GET_LOCK(:name, 0)');
$lockStmt->execute(['name' => $lockName]);
$locked = (int) $lockStmt->fetchColumn() === 1;

if (!$locked) {
    fwrite(STDOUT, "Another reminder worker is already running.\n");
    exit(0);
}

$repository = new ReminderRepository();
$bale = new BaleService();

try {
    if (!$bale->isConfigured()) {
        fwrite(STDERR, "BALE_BOT_TOKEN is not configured.\n");
        exit(1);
    }

    // config.php timezone را Asia/Tehran تنظیم می‌کند؛ مقایسه due نیز با همان timezone انجام می‌شود.
    $now = date('Y-m-d H:i:s');
    $due = $repository->findDue($now, 100);
    fwrite(STDOUT, 'Due reminders: ' . count($due) . PHP_EOL);

    foreach ($due as $reminder) {
        $typeLabel = $reminder['reminder_type'] === 'medication' ? 'یادآوری دارو' : 'یادآوری نوبت پزشک';
        $text = "🔔 {$typeLabel}\n\n"
            . $reminder['title'] . "\n"
            . 'زمان رویداد: ' . $reminder['event_at'];

        if (!empty($reminder['notes'])) {
            $text .= "\n\nیادداشت: " . $reminder['notes'];
        }

        $result = $bale->sendMessage((string) $reminder['chat_id'], $text);
        if ($result['success']) {
            $repository->markSent((int) $reminder['id'], date('Y-m-d H:i:s'));
            fwrite(STDOUT, 'Sent reminder #' . (int) $reminder['id'] . PHP_EOL);
        } else {
            $error = (string) ($result['error'] ?? 'Unknown Bale error');
            $repository->markFailedAttempt((int) $reminder['id'], $error, 3);
            fwrite(STDERR, 'Failed reminder #' . (int) $reminder['id'] . ': ' . $error . PHP_EOL);
        }
    }
} finally {
    $release = $pdo->prepare('SELECT RELEASE_LOCK(:name)');
    $release->execute(['name' => $lockName]);
}
