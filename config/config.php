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
     * وقتی Composer رو راه انداختید، این بلاک عملاً استفاده نمی‌شه.
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
 * سشن رو همینجا و همیشه اول از همه استارت می‌کنیم (قبل از هر echo/HTML).
 * چون header.php وسط <body> صفحات مختلف include می‌شه، اگه session_start()
 * رو اونجا صدا بزنیم، اون موقع دیگه هدرهای HTTP فرستاده شدن و session کار نمی‌کنه.
 * اینجا (بالای همه‌چیز، همون اول config.php) هنوز هیچ خروجی‌ای ارسال نشده.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}