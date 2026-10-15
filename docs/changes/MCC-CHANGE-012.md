# MCC-CHANGE-012 — پروفایل غنی، Activity Logging و Personalization

## تاریخ
2026-10-15

## درخواست / مسئله
در درخواست اولیه لازم بود پروفایل کاربر کامل‌تر شود، اطلاعات شخصی لازم بعد از Login قابل ثبت باشد، تعاملات کاربر در دیتابیس ذخیره شوند، سامانه Personalization داشته باشد و Article/FAQ بر اساس نیاز کاربر پیشنهاد شوند.

Dump واقعی نشان داد جدول `users` فقط `phone`، `full_name`، وضعیت کاربر و timestampهای Login/Create/Update را دارد و جدول مستقلی برای Profile یا Activity وجود ندارد.

## وضعیت قبل

- `users` فقط داده پایه Login داشت.
- صفحه Profile واقعی وجود نداشت.
- منوی حساب فقط نام/شماره و Logout را نشان می‌داد.
- رفتار صفحات کاربر به شکل پایدار در DB ثبت نمی‌شد.
- FAQ/Articleها برای همه یکسان بودند.
- تاریخ تقریبی زایمان یا تاریخ تولد کودک برای هدایت مستقیم به محتوای مرتبط استفاده نمی‌شد.

## تصمیم فنی
به‌جای شلوغ کردن جدول `users`، داده‌های Personalization در جدول ۱به۱ `user_profiles` نگهداری می‌شوند. Activityها نیز در جدول مستقل `user_activity_logs` ذخیره می‌شوند.

Repository فقط Data Access را انجام می‌دهد و تصمیم Recommendation در `RecommendationService` است.

## Migration جدید

`database/migrations/20261015_002_create_user_profiles_and_activity.sql`

### `user_profiles`

- `user_id` — PK/FK به users
- `stage` — planning / pregnant / postpartum / child_care
- `birth_date`
- `pregnancy_due_date`
- `child_name`
- `child_birth_date`
- `child_gender` — boy / girl / unknown
- created/updated timestamps

این فیلدها فقط برای نیازهای صریح Profile/Personalization انتخاب شدند و فیلد پزشکی اضافه‌ای خارج از Scope ساخته نشد.

### `user_activity_logs`

- `id`
- `user_id`
- `action_type`
- `request_method`
- `request_path`
- `entity_type`
- `entity_id`
- `metadata_json`
- `created_at`

Index روی user/time و action/time ایجاد شده است.

## Activity Logging

فایل‌ها:

- `app/Repositories/UserActivityRepository.php`
- `app/Services/UserActivityService.php`
- `config/config.php`

بعد از شروع Session در Bootstrap، اگر کاربر Login باشد Request ثبت می‌شود.

برای حفظ حداقل داده لازم، موارد زیر عمداً ذخیره نمی‌شوند:

- body خام Request
- OTP
- query string
- password/secret

برای GET مقدار `page_view` و برای Requestهای تغییردهنده مقدار عمومی `request` ثبت می‌شود. Actionهای دامنه‌ای مثل `profile_updated` می‌توانند جداگانه ثبت شوند.

Logging fail-safe است؛ اگر Migration هنوز اجرا نشده باشد، شکست درج log نباید Request اصلی سایت را fail کند.

## Profile

فایل‌ها:

- `app/Repositories/UserProfileRepository.php`
- `app/Services/UserProfileService.php`
- `public/profile.php`
- `public/assets/css/pages/profile.css`
- `templates/partials/header.php`
- `public/assets/css/components/header.css`

Profile شامل:

- نام و نام خانوادگی
- شماره موبایل read-only از Login OTP
- تاریخ تولد
- stage فعلی
- تاریخ تقریبی زایمان
- نام کودک
- تاریخ تولد کودک
- جنسیت کودک

Validation سمت Server انجام می‌شود.

Save پروفایل داخل Transaction انجام می‌شود تا اگر `user_profiles` یا Update نام fail شد، تغییر نیمه‌کاره ایجاد نشود. اگر Migration اجرا نشده باشد، پیام کنترل‌شده برمی‌گردد.

لینک «پروفایل من» به Dropdown حساب اضافه شده است.

## Recommendation / Personalization

فایل جدید:

`app/Services/RecommendationService.php`

Data Accessهای لازم به این Repositoryها اضافه شدند:

- `ArticleRepository::findRecommendedByCategorySlugs()`
- `FaqRepository::findRecommendedByCategorySlugs()`

Recommendation از taxonomy واقعی DB استفاده می‌کند، نه دسته‌های فرضی.

### Stage-based

- `planning` → باروری / پیش از بارداری
- `pregnant` → بارداری / تغذیه بارداری / مراقبت‌ها
- `postpartum` → پس از زایمان / مراقبت و تغذیه نوزاد
- `child_care` → رشد / مراقبت / تغذیه نوزاد و FAQهای کودک/واکسیناسیون/رشد/تکامل

### Activity-aware
مسیرهای اخیر کاربر می‌توانند اولویت را تغییر دهند. مثال:

- `vaccine.php` → FAQ vaccination
- `growth.php` → FAQ growth + مقاله رشد نوزاد
- `milestones.php` → FAQ development
- `child-nutrition.php` → nutrition
- صفحات pregnancy → pregnancy

هیچ مدل ML جدیدی اضافه نشده است.

### Quick Links زمانی
اگر stage = pregnant و `pregnancy_due_date` معتبر باشد، هفته فعلی بارداری محاسبه و لینک مستقیم `pregnancy-weeks.php?week=N` ساخته می‌شود.

اگر stage = postpartum/child_care و `child_birth_date` وجود داشته باشد، سن کودک به ماه محاسبه و لینک‌های مستقیم زیر ساخته می‌شوند:

- Growth
- Nutrition
- Milestones
- Vaccine

اگر تاریخ تقریبی زایمان با یک بارداری جاری سازگار نباشد Quick Link هفته ساخته نمی‌شود.

## Article cleanup در Recommendation
Excerpt مقاله پیشنهادی قبل از نمایش از `ArticleContentSanitizer` عبور می‌کند تا تبلیغات گهواره دوباره از طریق Profile نمایش داده نشوند.

## Before → After

**Before**

`Login user → نام/شماره ساده → محتوای عمومی یکسان`

**After**

`Login user → Rich Profile → Activity History → Stage/Activity Recommender → Personalized Article/FAQ/Quick Links`

## Validation

- Schema فعلی `users` از Dump واقعی بررسی شد و نبود Profile/Activity table تأیید شد.
- taxonomy واقعی `categories` برای Article و FAQ از Dump بررسی و همان slugها در Recommender استفاده شدند.
- Repositoryهای Recommendation از `main` بازخوانی شدند و Data Access از Logic جدا باقی مانده است.
- محاسبه Pregnancy Week طوری اصلاح شد که due date غیرسازگار با بارداری جاری Quick Link جعلی ایجاد نکند.
- PHP CLI در محیط موجود بود، اما runtime container دسترسی شبکه به GitHub نداشت و فایل‌های Connector به filesystem mount نبودند؛ بنابراین `php -l` روی snapshot واقعی repo در این مرحله اجرا نشد.
- Migration 002، Save/Reload Profile، Activity Insert و Recommendation هنوز باید روی MariaDB/Browser واقعی پروژه E2E تست شوند.

## Rollback

1. Migration 002 در محیطی که هنوز اجرا نشده باشد صرفاً اعمال نشود.
2. برای Rollback کد، Profile/Activity/Recommendation classes و `profile.php` حذف و Bootstrap logging برداشته شود.
3. اگر Migration روی DB اجرا شده و rollback داده‌ای لازم باشد، ابتدا Backup داده‌ها گرفته و سپس FKها/tables طبق ترتیب وابستگی حذف شوند.

## نتیجه نهایی
Foundation فاز ۶ پیاده‌سازی شده است: کاربر Profile قابل ویرایش دارد، رفتارهای اصلی Login user قابل ثبت‌اند و Article/FAQ/Quick Link بر اساس مرحله و رفتار اخیر شخصی‌سازی می‌شوند. Validation واقعی DB/Browser بعد از اجرای Migration لازم است.
