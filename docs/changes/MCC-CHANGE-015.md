# MCC-CHANGE-015 — اصلاحات پیدا‌شده در Deployment Validation بله و Reminder

## تاریخ
2026-10-15

## درخواست / زمینه
بعد از تکمیل Featureهای اولیه، مرحله Deploy/Validate روی کد واقعی `main` شروع شد. هدف این مرحله اضافه‌کردن Feature جدید نبود؛ فقط پیدا کردن خطاهایی بود که می‌توانستند هنگام اجرای واقعی Workflowهای درخواست‌شده باعث Failure شوند.

---

## ایراد ۱ — اتصال بله واقعاً اتمیک نبود

### وضعیت قبل
مستند فاز Reminder اعلام می‌کرد مصرف کد اتصال و ثبت `chat_id` در یک Transaction انجام می‌شود، اما پیاده‌سازی واقعی دو مرحله مستقل داشت:

1. Token با `used_at` مصرف و Transaction commit می‌شد.
2. سپس `chat_id` در `bale_user_channels` ثبت می‌شد.

اگر مرحله دوم به هر دلیل fail می‌شد، Token قبلاً مصرف شده بود و کاربر مجبور بود کد جدید بگیرد.

### Root Cause
مرز Transaction فقط `bale_link_tokens` را پوشش می‌داد و عملیات `bale_user_channels` خارج Transaction بود.

### تغییر
در `BaleChannelRepository` متد اتمیک زیر ایجاد شد:

`connectWithToken(tokenHash, chatId, now)`

ترتیب جدید:

`BEGIN → SELECT token FOR UPDATE → UPSERT chat_id → UPDATE token used_at → COMMIT`

در هر Exception:

`ROLLBACK`

بنابراین اگر اتصال Channel شکست بخورد، Token نیز مصرف نمی‌شود.

### فایل‌های تغییرکرده

- `app/Repositories/BaleChannelRepository.php`
- `app/Services/BaleLinkService.php`

### Validation
- Caller قدیمی `consumeAndConnect()` در `BaleLinkService` شناسایی و با قرارداد جدید هماهنگ شد.
- Search روی Repository برای `consumeAndConnect` بعد از اصلاح نتیجه‌ای برنگرداند.
- `chat_id` و `used_at` اکنون داخل یک Transaction نوشته می‌شوند.

---

## ایراد ۲ — مقایسه زمان Reminder به timezone دیتابیس وابسته بود

### وضعیت قبل
`config/config.php` timezone PHP را صریحاً روی `Asia/Tehran` قرار می‌دهد و زمان‌های فرم Reminder نیز با همین timezone تولید و در DB ذخیره می‌شوند.

اما Worker برای تشخیص Reminderهای due از این شرط استفاده می‌کرد:

`remind_at <= NOW()`

`NOW()` تابع MariaDB است و به timezone Session دیتابیس وابسته است. اگر MariaDB روی UTC یا timezone متفاوت اجرا شود، زمان ارسال Reminder می‌تواند با زمان مورد انتظار کاربر اختلاف داشته باشد.

### تغییر
زمان جاری اکنون در Worker توسط PHP و با همان timezone مرکزی پروژه تولید می‌شود:

`date('Y-m-d H:i:s')`

و به Repository ارسال می‌شود:

`findDue($now, 100)`

Query جدید:

`remind_at <= :now`

همچنین `sent_at` بعد از ارسال موفق با timestamp تولیدشده از همان timezone برنامه ثبت می‌شود.

### فایل‌های تغییرکرده

- `app/Repositories/ReminderRepository.php`
- `tools/send_due_reminders.php`

### نتیجه
تشخیص due بودن Reminder دیگر به timezone تنظیم‌شده روی MariaDB وابسته نیست و با timezone مرکزی `Asia/Tehran` برنامه هماهنگ است.

---

## نکته مرتبط Token بله
مقایسه `expires_at` نیز از `NOW()` دیتابیس جدا شد. `BaleLinkService` timestamp فعلی PHP را به `connectWithToken()` می‌دهد و Repository از `expires_at > :now` استفاده می‌کند.

به این ترتیب TTL کد اتصال و زمان Reminder هر دو با یک مرجع زمانی برنامه کار می‌کنند.

---

## Validation انجام‌شده

- هر پنج Migration موجود در `database/migrations` بازخوانی و ترتیب FKها بررسی شد.
- `users.id` با FKهای Migrationهای جدید از نوع `INT(10) UNSIGNED` سازگار است.
- قرارداد `BaleLinkService ↔ BaleChannelRepository` بعد از اصلاح بازخوانی شد.
- قرارداد `send_due_reminders.php ↔ ReminderRepository` بعد از تغییر امضا هماهنگ شد.
- تلاش برای `git clone` و اجرای `php -l` سراسری در محیط Validation انجام شد، اما DNS محیط CLI اجازه resolve کردن `github.com` را نداد؛ بنابراین Syntax check سراسری روی checkout واقعی در این محیط ادعا نمی‌شود.

## Validation باقی‌مانده روی سرور واقعی

1. اجرای Migrationها به ترتیب `001 → 005`.
2. اجرای `php -l` روی فایل‌های PHP تغییرکرده.
3. ایجاد کد اتصال بله و تست `/start CODE`.
4. بررسی اینکه Failure در اتصال Channel باعث مصرف Token نشود.
5. ساخت Reminder آزمایشی و بررسی ارسال در دقیقه مورد انتظار Tehran.
6. اجرای Cron و بررسی `sent_at`, `attempts`, `last_error`.

## Rollback
این Change Schema جدیدی اضافه نکرده است. Rollback فقط مستلزم برگرداندن سه فایل Runtime به نسخه قبل است، اما انجام آن توصیه نمی‌شود چون نسخه قبل دو defect مشخص فوق را دوباره ایجاد می‌کند.
