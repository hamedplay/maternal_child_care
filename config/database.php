<?php

/**
 * تنظیمات اتصال به دیتابیس.
 * می‌تونی این مقادیر رو مستقیم عوض کنی، یا با متغیرهای محیطی (getenv)
 * از بیرون (مثلاً در سرور پروداکشن) override‌شون کنی.
 */
return [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'maternal_child_care',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset'  => 'utf8mb4',
];