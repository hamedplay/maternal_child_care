# MCC-CHANGE-013 — ارتباط با پزشک و Reminder بله

## تاریخ
2026-10-15

## درخواست
دو نیاز صریح درخواست اولیه پیاده‌سازی شد:

1. گزینه «ارتباط با پزشک» مشابه Contact/Support ولی با Workflow مستقل پزشکی.
2. ثبت نوبت پزشک/دارو و ارسال Reminder از طریق بله.

## وضعیت قبل

- فقط `support_messages` برای bug/question/suggestion وجود داشت.
- Domain مستقلی برای پیام پزشک نبود.
- جدول Reminder وجود نداشت.
- اتصال کاربر به `chat_id` بله وجود نداشت.
- Bale Bot Service/Worker/Webhook وجود نداشت.

## Migrationها

### `20261015_003_create_doctor_contact_and_reminders.sql`

ایجاد:

- `doctor_messages`
- `bale_user_channels`
- `bale_link_tokens`
- `reminders`

### `20261015_004_add_doctor_message_reply.sql`

بدون ویرایش Migration قبلی، فیلدهای زیر به doctor_messages اضافه شدند:

- `reply_text`
- `replied_at`

## ارتباط با پزشک

فایل‌ها:

- `DoctorMessageRepository`
- `DoctorContactService`
- `public/doctor-contact.php`

کاربر Login‌شده Subject/Message ثبت می‌کند. status پیام `new/reviewed/replied` است. بعد از پاسخ Admin/پزشک، `reply_text` در history همان صفحه دیده می‌شود.

این Workflow از `support_messages` جدا است تا درخواست پزشکی با bug/support عمومی مخلوط نشود.

## Reminder

فایل‌ها:

- `ReminderRepository`
- `ReminderService`
- `public/reminders.php`
- `tools/send_due_reminders.php`

نوع Reminder:

- `appointment`
- `medication`

هر Reminder شامل title، notes، `event_at` و `remind_at` است.

Validation:

- event باید در آینده باشد.
- remind_at نباید بعد از event_at باشد.
- Reminder pending قابل لغو است.

## اتصال بله

فایل‌ها:

- `BaleService`
- `BaleChannelRepository`
- `BaleLinkService`
- `public/bale-webhook.php`

Bot API ارسال متن:

`POST https://tapi.bale.ai/bot<TOKEN>/sendMessage`

`chat_id` از طریق Link Flow دریافت می‌شود؛ شماره موبایل برای Bot API کافی نیست.

کاربر یک Code تصادفی ۱۶-Hex با عمر ۱۵ دقیقه می‌گیرد و در بازوی بله می‌فرستد:

`/start CODE`

در DB فقط SHA-256 Code ذخیره می‌شود.

مصرف Token + اتصال chat_id داخل یک Transaction انجام می‌شود.

## Worker

`tools/send_due_reminders.php`

- فقط Reminderهای pending و due را می‌خواند.
- فقط کاربری که Bale channel فعال دارد انتخاب می‌شود.
- `GET_LOCK` جلوی دو Worker همزمان را می‌گیرد.
- success → sent/sent_at
- failure → attempts/last_error
- بعد از ۳ خطا → failed

## Configuration

در `config/services.php`:

- `BALE_BOT_TOKEN`
- `BALE_BOT_USERNAME`
- `BALE_WEBHOOK_SECRET`

## Navigation

Dropdown حساب کاربری اکنون شامل:

- پروفایل من
- ارتباط با پزشک
- یادآوری‌ها
- خروج

## مستند Deployment

`docs/BALE_REMINDERS.md`

شامل Migration، ENV، Webhook و Cron است.

## Validation

- Support schema موجود بررسی شد و Doctor Domain جدا طراحی شد.
- Bot API و `tapi.bale.ai` با نمونه‌های عمومی رسمی سازمان Bale تطبیق داده شد.
- `chat_id` از Update استاندارد `message.chat.id` گرفته می‌شود.
- concurrency worker با DB lock کنترل شده است.
- هیچ Token واقعی در Repository قرار نگرفته است.
- E2E واقعی بله انجام نشده؛ نیازمند Bot Token، Webhook عمومی HTTPS، Migration و Cron محیط واقعی است.

## نتیجه
فاز ۷ از نظر کد کامل شده است؛ Deployment واقعی باید Webhook/Token/Cron را تنظیم و ارسال واقعی را Validate کند.
