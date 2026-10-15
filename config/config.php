<?php

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('TEMPLATES_PATH', BASE_PATH . '/templates');

date_default_timezone_set('Asia/Tehran');

/*
 * اگه composer install اجرا شده و vendor/autoload.php وجود داره،
 * از همون استفاده کن (روش استاندارد و پیشنهادی).
 */
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} else {
    /*
     * فال‌بک: تا وقتی Composer نصب/اجرا نشده، این autoloader ساده
     * کلاس‌های namespace‌ی App\ رو از پوشه‌ی app/ پیدا می‌کنه.
     */
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

/*
 * Session مرکزی قبل از هر خروجی HTML.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * ثبت Requestهای کاربر لاگین‌شده برای history/personalization.
 * UserActivityRepository fail-safe است؛ اگر Migration هنوز اجرا نشده باشد،
 * Logging نباید Request اصلی سایت را مختل کند.
 */
if (isset($_SESSION['user_id'])) {
    (new \App\Services\UserActivityService())->captureCurrentRequest((int) $_SESSION['user_id']);
}
