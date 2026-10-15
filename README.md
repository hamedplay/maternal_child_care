# Maternal Child Care

سامانه‌ی **مراقبت مادر و کودک** یک پروژه PHP/MariaDB برای محتوای بارداری و کودک، ورود با OTP، پرسش از هوش مصنوعی، شخصی‌سازی محتوا، ارتباط با پزشک، یادآوری از طریق بله و پنل مدیریت است.

این README ساختار فعلی شاخه `main` را توضیح می‌دهد. برای تاریخچه‌ی تغییرات فنی و جزئیات هر فاز به `docs/CHANGE_LOG.md` و `docs/changes/` مراجعه کنید.

---

## معماری کلی

جریان اصلی برنامه به‌صورت زیر است:

```text
Browser / Public Pages
        ↓
public/*.php
        ↓
Services                ← منطق کسب‌وکار / orchestration
        ↓
Repositories            ← Data Access / SQL
        ↓
MariaDB
```

جریان AI:

```text
ai-qa.php
   ↓
ai-qa-ask.php
   ↓
KnowledgeRetriever
   ├── QueryPreprocessor
   └── AiKnowledgeRepository
   ↓
Context Blocks
   ↓
LlmService
   ↓
OpenRouter
   ↓
ai_conversations / ai_messages
```

جریان OTP:

```text
Auth UI
   ↓
auth-send-otp.php
   ↓
AuthService
   ↓
KavenegarService
   ↓
Kavenegar Verify Lookup
   ↓
auth-verify-otp.php
   ↓
users + otp_verifications
```

جریان Reminder بله:

```text
reminders.php
   ↓
Bale Link Code
   ↓
/start CODE در بازوی بله
   ↓
bale-webhook.php
   ↓
bale_user_channels
   ↓
Cron → tools/send_due_reminders.php
   ↓
BaleService → sendMessage
```

---

# ساختار پروژه

```text
maternal_child_care/
│
├── README.md
├── composer.json
│
├── app/
│   ├── Core/
│   │   └── Database.php
│   │
│   ├── Repositories/
│   │   ├── AdminRepository.php
│   │   ├── AiConversationRepository.php
│   │   ├── AiKnowledgeRepository.php
│   │   ├── ArticleRepository.php
│   │   ├── BaleChannelRepository.php
│   │   ├── CategoryRepository.php
│   │   ├── ChildCareRepository.php
│   │   ├── DevelopmentMilestoneRepository.php
│   │   ├── DoctorMessageRepository.php
│   │   ├── FaqRepository.php
│   │   ├── GrowthStandardRepository.php
│   │   ├── OtpRepository.php
│   │   ├── ReminderRepository.php
│   │   ├── SupportMessageRepository.php
│   │   ├── UserActivityRepository.php
│   │   ├── UserProfileRepository.php
│   │   ├── UserRepository.php
│   │   └── ...
│   │
│   └── Services/
│       ├── AdminService.php
│       ├── ArticleContentSanitizer.php
│       ├── AuthService.php
│       ├── BaleLinkService.php
│       ├── BaleService.php
│       ├── DoctorContactService.php
│       ├── KavenegarService.php
│       ├── KnowledgeRetriever.php
│       ├── LlmService.php
│       ├── QueryPreprocessor.php
│       ├── RecommendationService.php
│       ├── ReminderService.php
│       ├── UserActivityService.php
│       └── UserProfileService.php
│
├── config/
│   ├── config.php
│   ├── database.php
│   ├── ai.php
│   └── services.php
│
├── database/
│   └── migrations/
│       ├── 20261015_001_create_ai_conversations.sql
│       ├── 20261015_002_create_user_profiles_and_activity.sql
│       ├── 20261015_003_create_doctor_contact_and_reminders.sql
│       ├── 20261015_004_add_doctor_message_reply.sql
│       └── 20261015_005_create_admin_users.sql
│
├── docs/
│   ├── CHANGE_LOG.md
│   ├── IMPLEMENTATION_ROADMAP.md
│   ├── BALE_REMINDERS.md
│   └── changes/
│       ├── MCC-CHANGE-012.md
│       ├── MCC-CHANGE-013.md
│       ├── MCC-CHANGE-014.md
│       └── MCC-CHANGE-015.md
│
├── public/
│   ├── index.php
│   │
│   ├── articles.php
│   ├── article.php
│   │
│   ├── pregnancy-weeks.php
│   ├── pregnancy-nutrition.php
│   ├── pregnancy-supplements.php
│   ├── pregnancy-warning-signs.php
│   ├── pregnancy-exercises.php
│   │
│   ├── growth.php
│   ├── vaccine.php
│   ├── child-nutrition.php
│   ├── milestones.php
│   ├── conditions.php
│   │
│   ├── ai-qa.php
│   ├── ai-qa-ask.php
│   │
│   ├── auth-send-otp.php
│   ├── auth-verify-otp.php
│   ├── auth-set-name.php
│   ├── auth-logout.php
│   │
│   ├── profile.php
│   ├── doctor-contact.php
│   ├── reminders.php
│   ├── bale-webhook.php
│   ├── admin.php
│   │
│   └── assets/
│       ├── css/
│       │   ├── base.css
│       │   ├── components/
│       │   └── pages/
│       ├── js/
│       │   ├── components/
│       │   └── pages/
│       ├── fonts/
│       └── image/
│
├── templates/
│   ├── partials/
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── support-widget.php
│   └── components/
│       ├── auth-modal.php
│       └── pregnancy-week-widget.php
│
└── tools/
    ├── cleanup_gahvare_article_ads.php
    ├── grant_admin.php
    ├── send_due_reminders.php
    │
    └── crawlers/
        ├── README.md
        ├── cdc_milestones.php
        └── import_cdc_milestones.php
```

> فهرست بالا روی اجزای اصلی معماری متمرکز است؛ بعضی Repositoryها، فایل‌های CSS/JS و assetهای جزئی برای خوانایی به‌صورت `...` خلاصه شده‌اند.

---

# مسئولیت پوشه‌ها

## `public/`

تنها بخش Web-facing پروژه است و **Document Root وب‌سرور باید روی همین پوشه تنظیم شود**.

صفحات اصلی شامل:

- خانه و مقالات
- بخش بارداری
- بخش کودک/نوزاد
- AI Q&A
- Login/Register با OTP
- پروفایل و Personalization
- ارتباط با پزشک
- Reminderها
- Webhook بله
- Admin Panel

هیچ‌کدام از پوشه‌های `app/`, `config/`, `database/`, `docs/`, `templates/` و `tools/` نباید مستقیماً از وب قابل دسترسی باشند.

---

## `app/Core/`

زیرساخت پایه برنامه.

### `Database.php`

اتصال مشترک PDO با MariaDB را فراهم می‌کند.

ویژگی‌های مهم:

- `PDO::ERRMODE_EXCEPTION`
- `PDO::FETCH_ASSOC`
- `PDO::ATTR_EMULATE_PREPARES = false`

بنابراین Queryهای جدید باید با Native Prepared Statements سازگار باشند و یک named placeholder تکراری نباید چند بار در یک Statement استفاده شود.

---

## `app/Repositories/`

لایه Data Access است.

Repositoryها مسئول مواردی مثل این هستند:

- اجرای SQL
- خواندن/نوشتن داده
- Prepared Statement
- Pagination و Queryهای دامنه‌ای

Business Logic، Ranking، Recommendation و Workflowهای خارجی نباید داخل Repository قرار گیرند.

نمونه‌ها:

- `AiKnowledgeRepository` → داده خام موردنیاز RAG
- `AiConversationRepository` → ذخیره گفتگوهای AI
- `ChildCareRepository` → داده صفحات کودک
- `OtpRepository` → OTP persistence
- `ReminderRepository` → Reminder persistence
- `AdminRepository` → داده موردنیاز پنل مدیریت

---

## `app/Services/`

منطق برنامه و orchestration در این لایه قرار دارد.

### AI / RAG

- `QueryPreprocessor`
  - نرمال‌سازی فارسی/عربی
  - اعداد فارسی/عربی
  - keyword extraction
  - synonym expansion
  - تشخیص domain
  - استخراج سن/جنسیت

- `KnowledgeRetriever`
  - انتخاب منابع
  - ranking/scoring
  - context building

- `LlmService`
  - ساخت prompt
  - ارسال Context + History + Question به OpenRouter

### Authentication

- `AuthService`
- `KavenegarService`

### Personalization

- `UserProfileService`
- `UserActivityService`
- `RecommendationService`

### Doctor / Reminder

- `DoctorContactService`
- `ReminderService`
- `BaleLinkService`
- `BaleService`

### Content Processing

- `ArticleContentSanitizer`

برای حذف CTAها و تصاویر تبلیغاتی گهواره بدون حذف کورکورانه‌ی لینک‌های محتوایی مقاله استفاده می‌شود.

---

# بخش AI و RAG

RAG فعلی از منابع داخلی دیتابیس استفاده می‌کند، از جمله:

- FAQ
- Articles
- Pregnancy Weeks
- Pregnancy Nutrition
- Pregnancy Supplements
- Pregnancy Warning Signs
- Pregnancy Exercises
- Child Nutrition
- Child Supplements
- Common Conditions
- Vaccination Schedule
- Development Milestones
- Growth Standards

Pipeline:

```text
Question
   ↓
QueryPreprocessor
   ↓
KnowledgeRetriever
   ↓
AiKnowledgeRepository
   ↓
Ranked Context Blocks
   ↓
LlmService
   ↓
OpenRouter
```

گفتگوها در `ai_conversations` و `ai_messages` ذخیره می‌شوند.

---

# بخش کودک / نوزاد

صفحات فعلی:

```text
growth.php
vaccine.php
child-nutrition.php
milestones.php
conditions.php
```

مسیرهای واقعی UI:

- `growth.php` → رشد
- `vaccine.php` → واکسیناسیون
- `child-nutrition.php` → تغذیه و مکمل
- `milestones.php` → تکامل
- `conditions.php` → بیماری‌ها/مشکلات شایع

داده‌ها از جدول‌های موجود کودک در MariaDB خوانده می‌شوند.

---

# Crawler محتوای کودک

Crawler فعلی از CDC Developmental Milestones استفاده می‌کند.

```text
CDC
 ↓
crawl
 ↓
Raw JSON
 ↓
ترجمه / بازبینی انسانی
 ↓
Reviewed JSON
 ↓
Importer
 ↓
development_milestones
```

فایل‌ها:

```text
tools/crawlers/cdc_milestones.php
tools/crawlers/import_cdc_milestones.php
tools/crawlers/README.md
```

اجرای Crawler:

```bash
php tools/crawlers/cdc_milestones.php
```

قبل از Import واقعی ابتدا Dry Run انجام شود.

جزئیات کامل در:

```text
tools/crawlers/README.md
```

---

# Login / OTP با Kavenegar

ورود و ثبت‌نام با شماره موبایل و OTP انجام می‌شود.

API مورد استفاده:

```text
Kavenegar Verify Lookup
```

OTP خام داخل دیتابیس ذخیره نمی‌شود و سمت سرور به‌صورت Hash نگهداری می‌شود.

متغیرهای Environment:

```bash
KAVENEGAR_API_KEY=...
KAVENEGAR_OTP_TEMPLATE=...
OTP_PEPPER=...
```

---

# OpenRouter / AI

تنظیمات AI در:

```text
config/ai.php
```

قرار دارند.

کلید OpenRouter باید در محیط Deployment به‌صورت امن تنظیم شود و Secret واقعی نباید در مستندات یا Commitهای جدید منتشر شود.

---

# Profile / Personalization

بعد از Login، کاربر می‌تواند اطلاعات موردنیاز برای شخصی‌سازی را در:

```text
profile.php
```

ثبت کند.

داده‌های فعلی شامل:

- نام کاربر
- مرحله فعلی (`planning`, `pregnant`, `postpartum`, `child_care`)
- تاریخ تولد
- تاریخ تقریبی زایمان
- نام کودک
- تاریخ تولد کودک
- جنسیت کودک

Recommendation براساس:

```text
Profile + Recent Activity + Existing Content Taxonomy
```

انجام می‌شود.

Activity Log برای کاربر لاگین‌شده ثبت می‌شود، ولی payload فرم، OTP و query string در log عمومی Request ذخیره نمی‌شوند.

---

# ارتباط با پزشک

صفحه:

```text
doctor-contact.php
```

Flow:

```text
User Message
   ↓
doctor_messages
   ↓
Admin / Doctor Review
   ↓
Reply
   ↓
User sees reply in doctor-contact.php
```

این بخش جایگزین اورژانس یا مراقبت پزشکی فوری نیست.

---

# Reminder با Bale

صفحه کاربر:

```text
reminders.php
```

Webhook:

```text
bale-webhook.php
```

Worker:

```text
tools/send_due_reminders.php
```

Environment:

```bash
BALE_BOT_TOKEN=...
BALE_BOT_USERNAME=...
BALE_WEBHOOK_SECRET=...
```

راهنمای کامل:

```text
docs/BALE_REMINDERS.md
```

Cron نمونه:

```cron
* * * * * cd /path/to/maternal_child_care && /usr/bin/php tools/send_due_reminders.php >> /var/log/maternal-reminders.log 2>&1
```

Worker با MariaDB `GET_LOCK` از اجرای همزمان چند worker جلوگیری می‌کند.

---

# Admin Panel

صفحه:

```text
admin.php
```

Admin از همان Login OTP کاربران استفاده می‌کند و Authorization از جدول مستقل زیر انجام می‌شود:

```text
admin_users
```

تعریف اولین Admin:

```bash
php tools/grant_admin.php 0912XXXXXXXX
```

پنل فعلی برای Scope همین پروژه این بخش‌ها را پوشش می‌دهد:

- Users / Profiles
- Doctor Messages / Replies
- Support Messages
- Reminder Status
- AI Message History

---

# Database Migrations

Migrationهای جدید باید **به ترتیب** اجرا شوند:

```text
001 → AI Conversations
002 → User Profiles / Activity
003 → Doctor Contact / Bale / Reminders
004 → Doctor Reply Fields
005 → Admin Users
```

مثال:

```bash
mysql -u USER -p DATABASE < database/migrations/20261015_001_create_ai_conversations.sql
mysql -u USER -p DATABASE < database/migrations/20261015_002_create_user_profiles_and_activity.sql
mysql -u USER -p DATABASE < database/migrations/20261015_003_create_doctor_contact_and_reminders.sql
mysql -u USER -p DATABASE < database/migrations/20261015_004_add_doctor_message_reply.sql
mysql -u USER -p DATABASE < database/migrations/20261015_005_create_admin_users.sql
```

Migration قبلی نباید برای Feature جدید ویرایش شود؛ تغییر بعدی Schema باید Migration جدید داشته باشد.

---

# ابزارهای CLI

## حذف تبلیغات گهواره

Dry Run:

```bash
php tools/cleanup_gahvare_article_ads.php --dry-run
```

Apply:

```bash
php tools/cleanup_gahvare_article_ads.php --apply
```

## ساخت Admin

```bash
php tools/grant_admin.php 0912XXXXXXXX
```

## ارسال Reminderهای Due

```bash
php tools/send_due_reminders.php
```

## Crawler کودک

```bash
php tools/crawlers/cdc_milestones.php
```

Importer طبق دستورالعمل `tools/crawlers/README.md` اجرا شود.

---

# تنظیم Web Server

Document Root باید روی:

```text
/path/to/maternal_child_care/public
```

باشد.

این تنظیم برای جلوگیری از دسترسی مستقیم مرورگر به موارد زیر ضروری است:

```text
app/
config/
database/
docs/
templates/
tools/
```

---

# Composer / Autoload

Namespace اصلی:

```text
App\
```

اگر `vendor/autoload.php` وجود داشته باشد، پروژه از Composer autoload استفاده می‌کند.

در غیر این صورت `config/config.php` یک autoloader داخلی برای Namespace `App\` دارد.

نصب Composer dependencies:

```bash
composer install
```

---

# Timezone

Timezone مرکزی PHP:

```text
Asia/Tehran
```

Reminder Worker زمان جاری را از PHP می‌گیرد و برای due check به SQL پاس می‌دهد تا رفتار ارسال به timezone Session خود MariaDB وابسته نباشد.

---

# مستندات مهم

```text
docs/CHANGE_LOG.md
```

تاریخچه و توضیح تغییرات اصلی پروژه.

```text
docs/IMPLEMENTATION_ROADMAP.md
```

فازبندی نیازهای اولیه و وضعیت اجرای هر بخش.

```text
docs/changes/
```

شرح تفصیلی Changeهای جدیدتر.

```text
docs/BALE_REMINDERS.md
```

راهنمای راه‌اندازی Webhook و Cron بله.

```text
tools/crawlers/README.md
```

راهنمای Crawler و Import محتوای CDC.

---

# وضعیت فعلی پروژه

پیاده‌سازی اصلی Featureهای Scope اولیه روی `main` انجام شده است، اما **Production Ready بودن هنوز منوط به Validation محیط واقعی است**.

قبل از بهره‌برداری نهایی باید حداقل این موارد روی سرور واقعی تأیید شوند:

1. اجرای Migrationها `001 → 005`.
2. صحت تنظیم Database و Environment variables.
3. تست واقعی Kavenegar OTP.
4. تست Retriever → OpenRouter → AI persistence.
5. اجرای Crawler / Import روی محیط واقعی.
6. Dry Run و سپس Apply پاک‌سازی تبلیغات گهواره.
7. تست Profile / Personalization.
8. تست Doctor Contact / Reply.
9. تست Bale Link / Webhook / Cron / Reminder.
10. ساخت اولین Admin و تست Authorization پنل.
11. Browser Regression Test صفحات اصلی.

تا زمان عبور موفق این Validationها، پروژه باید **Implemented / Ready for deployment validation** در نظر گرفته شود، نه Production Ready.

---

# اصول توسعه این Repository

1. فقط `public/` Web-facing است.
2. Repository = Data Access.
3. Service = Business Logic / Workflow.
4. تغییر DB فقط با Migration جدید.
5. Secret واقعی در Git Commit نشود.
6. External APIها از Environment پیکربندی شوند.
7. تغییرات مهم در `docs/CHANGE_LOG.md` یا `docs/changes/` ثبت شوند.
8. Feature جدید نباید Migration تاریخی را بازنویسی کند.
9. قبل از Apply ابزارهای داده‌ای، در صورت وجود ابتدا `--dry-run` اجرا شود.
10. هر تغییر باید با Scope پروژه و ساختار موجود هماهنگ بماند.
