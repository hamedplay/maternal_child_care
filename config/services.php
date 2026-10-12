<?php

/**
 * تنظیمات سرویس‌های بیرونی (فعلاً فقط کاوه‌نگار).
 * کلید API رو مستقیم اینجا نذارید؛ از متغیر محیطی ست کنید (envهای سرور یا .env).
 */
return [
    'kavenegar' => [
        // از پنل کاوه‌نگار: تنظیمات > API Key
        'api_key' => getenv('KAVENEGAR_API_KEY') ?: '',

        // اسم قالب (Template) که توی پنل کاوه‌نگار برای پیامک کد تایید ساختید
        // (بخش Verify Lookup > Templates). بدون ساختن این قالب، ارسال شکست می‌خوره.
        'otp_template' => getenv('KAVENEGAR_OTP_TEMPLATE') ?: 'verify',
    ],

    // یه رشته‌ی تصادفی و طولانی که فقط سمت سرور می‌مونه؛ برای هش‌کردن کد تایید استفاده می‌شه.
    // حتماً موقع دیپلوی روی سرور واقعی عوضش کنید (مثلاً با یه env جدا).
    'otp_pepper' => getenv('OTP_PEPPER') ?: 'CHANGE-THIS-TO-A-LONG-RANDOM-SECRET',
];
