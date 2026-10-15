<?php

/**
 * تنظیمات سرویس‌های بیرونی.
 * Secretها مستقیم در Repository قرار نمی‌گیرند و از Environment خوانده می‌شوند.
 */
return [
    'kavenegar' => [
        'api_key' => getenv('KAVENEGAR_API_KEY') ?: '',
        'otp_template' => getenv('KAVENEGAR_OTP_TEMPLATE') ?: 'verify',
    ],

    'bale' => [
        'bot_token' => getenv('BALE_BOT_TOKEN') ?: '',
        'bot_username' => getenv('BALE_BOT_USERNAME') ?: '',
        'webhook_secret' => getenv('BALE_WEBHOOK_SECRET') ?: '',
    ],

    'otp_pepper' => getenv('OTP_PEPPER') ?: 'CHANGE-THIS-TO-A-LONG-RANDOM-SECRET',
];
