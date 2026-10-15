# راه‌اندازی Reminder بله

این سند مربوط به فاز Reminder نوبت پزشک و دارو است.

## 1. Migrationها

به ترتیب اجرا شوند:

```text
database/migrations/20261015_003_create_doctor_contact_and_reminders.sql
database/migrations/20261015_004_add_doctor_message_reply.sql
```

Migration قبلی ویرایش نشده و reply پزشک با Migration جدا اضافه شده است.

## 2. Environment

```bash
BALE_BOT_TOKEN=...
BALE_BOT_USERNAME=...
BALE_WEBHOOK_SECRET=...
```

- `BALE_BOT_TOKEN`: توکن بازو/ربات بله.
- `BALE_BOT_USERNAME`: فقط برای نمایش نام بازو به کاربر در صفحه اتصال.
- `BALE_WEBHOOK_SECRET`: Secret مربوط به URL وب‌هوک.

## 3. Endpoint بله

ارسال پیام توسط `app/Services/BaleService.php` انجام می‌شود:

```text
POST https://tapi.bale.ai/bot<TOKEN>/sendMessage
```

Payload مورد استفاده:

```json
{
  "chat_id": "...",
  "text": "..."
}
```

## 4. اتصال حساب کاربر به بله

کاربر در `reminders.php` روی «ساخت کد اتصال بله» می‌زند.

سامانه یک کد ۱۶ کاراکتری Hex با اعتبار ۱۵ دقیقه تولید می‌کند و در DB فقط SHA-256 آن ذخیره می‌شود.

کاربر در بازوی بله دستور زیر را می‌فرستد:

```text
/start CODE
```

بله Update را به این endpoint می‌فرستد:

```text
/public/bale-webhook.php?secret=<BALE_WEBHOOK_SECRET>
```

Webhook `chat.id` را از Update دریافت و پس از مصرف موفق Token به `user_id` متصل می‌کند.

Token و اتصال `chat_id` در یک Transaction انجام می‌شوند؛ اگر اتصال شکست بخورد Token مصرف‌شده باقی نمی‌ماند.

## 5. تنظیم Webhook

URL عمومی سایت باید HTTPS باشد. Webhook را روی URL واقعی سرور تنظیم کنید؛ مثال مفهومی:

```bash
curl -X POST "https://tapi.bale.ai/bot${BALE_BOT_TOKEN}/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://example.com/bale-webhook.php?secret=YOUR_SECRET"}'
```

نام دامنه و Secret باید با محیط واقعی جایگزین شوند.

## 6. Worker Reminder

Worker:

```bash
php tools/send_due_reminders.php
```

پیشنهاد Cron برای بررسی دقیقه‌ای:

```cron
* * * * * cd /path/to/maternal_child_care && /usr/bin/php tools/send_due_reminders.php >> /var/log/maternal-reminders.log 2>&1
```

Worker با MariaDB `GET_LOCK` از اجرای همزمان دو worker جلوگیری می‌کند.

Reminder فقط وقتی انتخاب می‌شود که:

- status = `pending`
- `remind_at <= NOW()`
- کاربر یک `bale_user_channels` فعال داشته باشد

در Success:

- status → `sent`
- `sent_at` ثبت می‌شود

در Failure:

- attempts افزایش می‌یابد
- خطا در `last_error` ذخیره می‌شود
- بعد از سه Failure وضعیت → `failed`

## 7. صفحات کاربر

- `doctor-contact.php` — ارسال سؤال برای پزشک و دیدن پاسخ
- `reminders.php` — اتصال بله، ثبت/لغو Reminder و مشاهده وضعیت

هر دو صفحه نیازمند Login هستند.

## 8. Validation واقعی موردنیاز

پس از Deployment:

1. Migration 003 و 004 اجرا شوند.
2. Bot token و username و webhook secret تنظیم شوند.
3. Webhook ثبت شود.
4. کاربر کد اتصال بسازد و `/start CODE` را در بله بفرستد.
5. اتصال در `bale_user_channels` بررسی شود.
6. Reminder آزمایشی چند دقیقه بعد ساخته شود.
7. Cron اجرا و دریافت پیام در بله تأیید شود.
8. Failure test با Token نامعتبر یا قطع موقت API انجام شود.
