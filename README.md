# ساختار پروژه

```
project-root/
├── public/                      ← تنها پوشه‌ای که باید وب‌روت (document root) هاست بهش اشاره کنه
│   ├── index.php
│   └── assets/
│       ├── css/
│       │   ├── base.css              فونت‌ها + ریست + متغیرهای رنگی مشترک
│       │   ├── components/           استایل هر کامپوننت قابل‌استفاده‌ی مجدد
│       │   │   ├── header.css
│       │   │   ├── button.css
│       │   │   └── card.css
│       │   └── pages/                استایل اختصاصی هر صفحه
│       │       └── home.css
│       ├── js/
│       │   ├── components/
│       │   │   └── header.js
│       │   └── pages/                (خالیه، برای اسکریپت‌های اختصاصی هر صفحه)
│       ├── fonts/                    ← فایل‌های woff فونت رو اینجا بذارید
│       └── image/                    ← banner.png گذاشته شده، logo.png رو باید اضافه کنید
│
├── app/                          منطق شی‌گرا (بیرون از public/, مستقیم در دسترس مرورگر نیست)
│   ├── Controllers/               (فعلاً خالی — برای وقتی صفحات منطق پیچیده‌تری گرفتن)
│   ├── Models/                    (فعلاً خالی)
│   ├── Services/                  (فعلاً خالی)
│   └── Repositories/
│       └── FeatureCardRepository.php   داده‌ی کارت‌های صفحه‌ی خانه
│
├── templates/                    partial های PHP قابل include
│   ├── partials/
│   │   └── header.php            فقط مارک‌آپ هدر (بدون style/script)
│   └── components/
│       └── card.php              یک کارت با گرفتن آرایه‌ی $card
│
├── config/
│   └── config.php                مسیرهای پایه + autoload کلاس‌های App\
│
└── composer.json                 برای autoload استاندارد PSR-4 (اختیاری، اگه composer نصب کنید)
```

## نکات مهم

1. **وب‌روت هاست رو روی `public/` بذارید**، نه روی ریشه‌ی پروژه. این‌طوری پوشه‌های `app/`, `templates/`, `config/`
   از دسترس مستقیم مرورگر خارج می‌مونن.

2. **فایل‌های فونت** (`iranyekanwebbold...woff` و بقیه) که قبلاً توی `header.php` بودن رو باید توی
   `public/assets/fonts/` قرار بدید — مسیرشون توی `base.css` نسبت به همون پوشه تنظیم شده.

3. **`logo.png`** رو هم باید توی `public/assets/image/` بذارید (فقط `banner.png` همراه این پروژه هست).

4. **صفحات بعدی** (`growth.php`, `vaccine.php`, `faq.php`, `about.php`) رو هم داخل `public/` بسازید،
   دقیقاً مثل الگوی `index.php`:
   - یک `$activePage` مخصوص همون صفحه ست کنید (مثلاً `'growth'`)
   - `config/config.php` و `templates/partials/header.php` رو include کنید
   - یک فایل CSS مخصوص خودش زیر `assets/css/pages/` بسازید

5. **افزودن کلاس OOP جدید:** فقط یک فایل مثل `app/Repositories/ArticleRepository.php` با
   `namespace App\Repositories;` بسازید — چه Composer نصب باشه چه نه، `config.php` خودش
   کلاس رو پیدا و لود می‌کنه (فایل `FeatureCardRepository.php` نمونه‌ی آماده‌ست).

6. اگه بعداً Composer نصب کردید، کافیه از ریشه‌ی پروژه `composer install` بزنید؛
   `config.php` خودکار به autoload استاندارد Composer سوییچ می‌کنه.
