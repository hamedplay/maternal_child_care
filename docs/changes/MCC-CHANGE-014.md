# MCC-CHANGE-014 — پنل مدیریت محدود به Scope پروژه

## تاریخ
2026-10-15

## درخواست / مسئله
در درخواست اولیه «Admin Panel» خواسته شده بود. برای جلوگیری از Scope Creep، پنل فقط قابلیت‌هایی را مدیریت می‌کند که در همین پروژه و درخواست اولیه وجود دارند؛ CMS عمومی یا مدیریت Featureهای نامرتبط اضافه نشده است.

## وضعیت قبل

- سیستم Login بر پایه OTP وجود داشت اما مفهوم Admin مستقل نبود.
- صفحه مدیریت وجود نداشت.
- پاسخ پزشک باید جایی ثبت می‌شد تا کاربر در `doctor-contact.php` ببیند.
- وضعیت Support فقط در DB بود و UI مدیریتی نداشت.
- Reminder و AI history بعد از پیاده‌سازی فازهای قبلی هیچ نمای مدیریتی نداشتند.

## Authorization

Migration جدید:

`database/migrations/20261015_005_create_admin_users.sql`

جدول `admin_users` فقط `user_id` را به `users.id` متصل می‌کند. Login جدیدی برای Admin ساخته نشده؛ Admin همان OTP معمول سامانه را استفاده می‌کند و Authorization بعد از Login جدا بررسی می‌شود.

این تصمیم جدول `users` و جریان Authentication موجود را بی‌جهت تغییر نمی‌دهد.

## Bootstrap اولین Admin

ابزار CLI:

```bash
php tools/grant_admin.php 09121234567
```

شرایط:

1. Migration 005 اجرا شده باشد.
2. شماره قبلاً با OTP در `users` ایجاد شده باشد.

ابزار user را با شماره پیدا و `user_id` را در `admin_users` ثبت می‌کند.

## Data Access

فایل:

`app/Repositories/AdminRepository.php`

مسئولیت‌ها:

- `isAdmin()`
- `grant()`
- Dashboard counts
- فهرست User/Profile
- فهرست پیام‌های پزشک
- ثبت Reply پزشک
- mark reviewed
- فهرست Support و تغییر status
- فهرست Reminderها
- فهرست AI messages

Queryهای مربوط به جدول‌هایی که با Migrationهای جدید ساخته می‌شوند fail-safe هستند؛ اگر یک Migration هنوز اعمال نشده باشد، همان Section آرایه خالی/Count صفر می‌دهد و کل Panel Fatal نمی‌شود.

## Business Logic

فایل:

`app/Services/AdminService.php`

Validation و Actionهای مدیریتی در Service قرار دارند؛ Repository فقط Data Access است.

### Doctor workflow

Admin می‌تواند:

- پیام `new` را `reviewed` کند.
- پاسخ تا ۵۰۰۰ کاراکتر ثبت کند.

Reply:

- `reply_text` را ذخیره می‌کند.
- `replied_at = NOW()`
- status → `replied`

کاربر همان Reply را در `doctor-contact.php` می‌بیند.

### Support workflow
فقط statusهای واقعی Schema پذیرفته می‌شوند:

- `pending`
- `in_progress`
- `resolved`

Status جدید یا فرضی اضافه نشده است.

### Logging Admin actions
Actionهای تغییر‌دهنده از `UserActivityService` استفاده می‌کنند، از جمله:

- `admin_doctor_reply`
- `admin_doctor_reviewed`
- `admin_support_status_changed`

این کار مستقیماً در Scope درخواست ثبت رفتار کاربران قرار دارد.

## UI

فایل‌ها:

- `public/admin.php`
- `public/assets/css/pages/admin.css`

دسترسی:

1. Login لازم است.
2. `AdminService::isAdmin()` قبل از Render بررسی می‌شود.
3. کاربر غیر Admin پاسخ HTTP 403 می‌گیرد.

Panel شامل این بخش‌هاست:

- Dashboard counts
- پیام پزشک + Reply
- Support + تغییر status
- User/Profile overview
- Reminder overview
- AI message history

اطلاعات OTP، API Key یا Secretها در Admin UI نمایش داده نمی‌شوند.

## Navigation

`templates/partials/header.php` اکنون `AdminRepository::isAdmin()` را برای Login user بررسی می‌کند. لینک «پنل مدیریت» فقط برای Admin نمایش داده می‌شود.

`isAdmin()` در صورت اجرا نشدن Migration 005 false برمی‌گرداند تا Header سایت Fatal نشود.

## Before → After

**Before**

`OTP User → no admin authorization → no management UI`

**After**

`OTP User + admin_users authorization → scoped Admin Panel → doctor/support/reminder/user/AI management`

## Validation

- Schema واقعی Support از Dump بررسی شد و enumهای همان Schema استفاده شدند.
- Authorization در ابتدای `admin.php` قبل از Render انجام می‌شود.
- Header فقط برای Admin واقعی لینک Panel را نمایش می‌دهد.
- Doctor Reply با Migration 004 و UI کاربر تطبیق داده شد.
- Queryهای AI/Reminder/Profile که به Migrationهای جدید وابسته‌اند fail-safe طراحی شدند.
- فایل‌های `AdminRepository` و `admin.php` بعد از Commit از `main` بازخوانی شدند.
- Runtime E2E واقعی هنوز نیازمند اجرای Migrationهای 001 تا 005 روی MariaDB پروژه و Login Admin در Browser است.

## Deployment / تست واقعی

ترتیب پیشنهادی Migrationهای جدید:

```text
20261015_001_create_ai_conversations.sql
20261015_002_create_user_profiles_and_activity.sql
20261015_003_create_doctor_contact_and_reminders.sql
20261015_004_add_doctor_message_reply.sql
20261015_005_create_admin_users.sql
```

سپس:

```bash
php tools/grant_admin.php 09XXXXXXXXX
```

و با همان شماره از OTP Login کنید و `admin.php` را تست کنید.

## Rollback

- حذف لینک Admin و فایل‌های Admin کد را Rollback می‌کند.
- اگر Migration 005 اجرا شده باشد، قبل از حذف جدول `admin_users` فقط داده Authorization حذف می‌شود و جدول `users` دست‌نخورده می‌ماند.

## نتیجه نهایی
Admin Panel مورد درخواست با Scope محدود پروژه پیاده‌سازی شده است. پاسخ پزشک، Support status، User/Profile، Reminder و AI history از یک پنل قابل مشاهده/مدیریت‌اند و Authorization از Login OTP موجود جدا نگه داشته شده است.
