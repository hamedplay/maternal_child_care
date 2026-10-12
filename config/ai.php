<?php

/**
 * تنظیمات سرویس هوش مصنوعی (از طریق OpenRouter).
 * کلید API رو هرگز مستقیم توی این فایل ننویس؛ از متغیر محیطی OPENROUTER_API_KEY
 * استفاده کن (مثلاً در سرور پروداکشن یا فایل .env که با getenv خونده می‌شه).
 *
 * برای گرفتن کلید:
 * 1. توی https://openrouter.ai ثبت‌نام کن.
 * 2. از بخش Keys یه API Key بساز.
 * 3. حساب رو شارژ کن (کارت‌های مجازی ریالی/دلاری صرافی‌ها یا رمزارز رو قبول می‌کنه).
 */
return [
    'openrouter_api_key' => getenv('OPENROUTER_API_KEY') ?: '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',

    'model' => getenv('OPENROUTER_MODEL') ?: 'deepseek/deepseek-chat',

    // این دو مقدار توی هدر درخواست به OpenRouter می‌رن (برای آمار خودشون، اجباری نیست ولی توصیه‌شده)
    'site_url'  => getenv('SITE_URL') ?: 'http://localhost',
    'site_name' => 'مراقبت مادر و کودک',

    // حداکثر تعداد پیام‌های قبلیِ گفتگو که به مدل فرستاده می‌شه
    // (برای کنترل هزینه و جلوگیری از طولانی شدن بیش‌ازحد context)
    'max_history_messages' => 12,
];
