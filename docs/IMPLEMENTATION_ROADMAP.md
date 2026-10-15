# نقشه راه اجرای درخواست‌های Maternal Child Care

این سند فقط درخواست‌های اولیه کاربر را فازبندی می‌کند. هیچ Feature، Refactor یا Hardening خارج از این Scope نباید صرفاً به دلیل «بهتر بودن معماری» انجام شود.

> اصل اجرایی: Inspect → Requirement / Root Cause → Minimal Design → Implement → Validate → Document

## منابع مرجع

- کد واقعی شاخه `main`
- Dump واقعی MariaDB پروژه
- `docs/CHANGE_LOG.md` و `docs/changes/`

## قواعد Scope

1. فقط نیازهای درخواست اولیه اجرا می‌شوند.
2. Refactor فقط وقتی انجام می‌شود که مستقیماً برای همان نیازها لازم باشد.
3. تغییر دیتابیس فقط با Migration جدید انجام می‌شود؛ Dump تاریخی ویرایش نمی‌شود.
4. هر تغییر با Before → After، دلیل و Validation مستند می‌شود.

---

# فاز ۱ — AI/RAG و جداسازی Data Access

**وضعیت: انجام‌شده**

- جداسازی Data Access از Logic.
- ایجاد `KnowledgeRetriever`.
- انتقال Keyword Processing به `QueryPreprocessor`.
- نرمال‌سازی فارسی/عربی و اعداد.
- انتقال scoring/ranking/context building از Repository به Service.

ساختار:

`Endpoint → KnowledgeRetriever → QueryPreprocessor + AiKnowledgeRepository → LlmService`

جزئیات: `MCC-CHANGE-002`, `MCC-CHANGE-003`, `MCC-CHANGE-007`.

---

# فاز ۲ — Login/Register و OTP با Kavenegar

**وضعیت: کد انجام‌شده؛ E2E واقعی وابسته به credential محیط اجرا**

انجام‌شده:

- UI → endpoint → AuthService → Kavenegar → verify.
- OTP hashed server-side.
- اصلاح ترتیب send/persist.
- Verify Lookup با `type=sms`.
- Error Handling رسمی Kavenegar.

باقی‌مانده محیط اجرا:

- `KAVENEGAR_API_KEY`
- `KAVENEGAR_OTP_TEMPLATE`
- `OTP_PEPPER`
- تست send / wrong-code / expiry / resend

جزئیات: `MCC-CHANGE-004`, `MCC-CHANGE-009`.

---

# فاز ۳ — تکمیل AI/RAG

**وضعیت: کد اصلی انجام‌شده؛ Migration و E2E واقعی باقی مانده است**

- رفع duplicate session start.
- Persian normalization/numbers/domain synonyms.
- Domain Detection.
- Child data sources in RAG.
- هماهنگ‌سازی Prompt.
- Persistence گفتگوهای AI.

باقی‌مانده:

- Migration 001.
- Retriever → OpenRouter → DB E2E.

جزئیات: `MCC-CHANGE-005`, `MCC-CHANGE-006`, `MCC-CHANGE-007`.

---

# فاز ۴ — Infant/Child Content و Crawler

**وضعیت: کد انجام‌شده؛ Crawl/Import/Browser validation واقعی باقی مانده است**

- CDC crawler.
- Raw JSON provenance.
- Reviewed Persian importer.
- ChildCareRepository.
- صفحات growth/vaccine/child-nutrition/milestones/conditions.

مسیر داده:

`CDC → Raw JSON → ترجمه/بازبینی → Reviewed JSON → Import → development_milestones`

جزئیات: `MCC-CHANGE-008`, `MCC-CHANGE-010`.

---

# فاز ۵ — حذف تبلیغات گهواره

**وضعیت: کد انجام‌شده؛ DB cleanup واقعی باقی مانده است**

- شناسایی الگوها از Dump واقعی.
- `ArticleContentSanitizer`.
- Sanitization هنگام Render.
- Cleanup CLI با dry-run/apply/transaction.

باقی‌مانده:

- Dry Run روی DB اصلی.
- بررسی نمونه‌ها و Apply.
- Browser validation.

جزئیات: `MCC-CHANGE-011`.

---

# فاز ۶ — Profile، User Data، Logging و Personalization

**وضعیت: کد انجام‌شده؛ Migration و Browser/DB validation باقی مانده است**

- Migration 002.
- `user_profiles`.
- `user_activity_logs`.
- Rich Profile.
- Request activity logging بدون raw payload/OTP/query string.
- RecommendationService بر اساس Profile + recent activity.
- Article/FAQ recommendations.
- pregnancy-week / child-age quick links.

جزئیات: `docs/changes/MCC-CHANGE-012.md`.

---

# فاز ۷ — Doctor Contact و Reminder با Bale

**وضعیت: کد انجام‌شده؛ Bale/Webhook/Cron E2E باقی مانده است**

انجام‌شده:

- Migration 003 برای `doctor_messages`, `bale_user_channels`, `bale_link_tokens`, `reminders`.
- Migration 004 برای `reply_text/replied_at`.
- Doctor Contact صفحه و history.
- پاسخ پزشک قابل نمایش به کاربر.
- `BaleService` با `sendMessage`.
- اتصال حساب بله با Token یک‌بارمصرف ۱۶-Hex و hash در DB.
- اتصال Token + chat_id به صورت Transactional.
- Webhook endpoint.
- appointment/medication Reminder.
- worker CLI با MariaDB `GET_LOCK`.
- سه retry و status sent/failed.
- مستند `docs/BALE_REMINDERS.md`.

باقی‌مانده محیط اجرا:

- Migrationهای 003/004.
- `BALE_BOT_TOKEN`, `BALE_BOT_USERNAME`, `BALE_WEBHOOK_SECRET`.
- HTTPS webhook registration.
- Cron worker.
- تست `/start CODE` و sendMessage واقعی.

جزئیات: `docs/changes/MCC-CHANGE-013.md`.

---

# فاز ۸ — Admin Panel

**وضعیت: کد انجام‌شده؛ Migration/Admin bootstrap/Browser E2E باقی مانده است**

انجام‌شده:

- Migration 005 برای `admin_users`.
- Login جداگانه Admin ساخته نشد؛ OTP موجود + authorization مستقل استفاده می‌شود.
- `AdminRepository` و `AdminService`.
- `public/admin.php`.
- dashboard counts.
- User/Profile overview.
- Doctor messages + reviewed/reply.
- Support status management با enum واقعی DB.
- Reminder overview.
- AI message history.
- Admin action logging.
- `tools/grant_admin.php` برای معرفی اولین Admin.
- لینک Admin فقط برای Admin واقعی نمایش داده می‌شود.

باقی‌مانده محیط اجرا:

1. اجرای Migrationهای 001 تا 005 به ترتیب.
2. Login/Register شماره Admin.
3. `php tools/grant_admin.php 09XXXXXXXXX`
4. Browser test `admin.php`.
5. تست end-to-end پاسخ پزشک و Support status.

جزئیات: `docs/changes/MCC-CHANGE-014.md`.

---

# وضعیت کلی

تمام فازهای درخواست اولیه از نظر کد اصلی روی `main` پیاده‌سازی شده‌اند. موارد باقی‌مانده عمدتاً **Deployment/Environment Validation** هستند و بدون دسترسی به MariaDB واقعی، credentials Kavenegar/Bale و Browser سرور نباید به‌عنوان Done عملی ادعا شوند.

## ترتیب Migrationهای جدید

```text
20261015_001_create_ai_conversations.sql
20261015_002_create_user_profiles_and_activity.sql
20261015_003_create_doctor_contact_and_reminders.sql
20261015_004_add_doctor_message_reply.sql
20261015_005_create_admin_users.sql
```

---

# Definition of Done

یک Task فقط زمانی Done عملی است که:

1. وضعیت واقعی قبل از تغییر بررسی شده باشد.
2. تغییر فقط در Scope باشد.
3. کد پیاده‌سازی شده باشد.
4. Migration/credential مرتبط در محیط واقعی تنظیم شده باشد.
5. Validation واقعی متناسب با Feature انجام شده باشد.
6. محدودیت Validation صریح ثبت شده باشد.
7. تغییر مستند شده باشد.
