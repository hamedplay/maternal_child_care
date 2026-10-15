# نقشه راه اجرای درخواست‌های Maternal Child Care

این سند فقط درخواست‌های اولیه کاربر را فازبندی می‌کند. هیچ Feature، Refactor یا Hardening خارج از این Scope نباید صرفاً به دلیل «بهتر بودن معماری» انجام شود.

> اصل اجرایی: Inspect → Requirement / Root Cause → Minimal Design → Implement → Validate → Document

## منابع مرجع

- کد واقعی شاخه `main`
- Dump واقعی MariaDB پروژه
- `docs/CHANGE_LOG.md`

## قواعد Scope

1. فقط نیازهای درخواست اولیه اجرا می‌شوند.
2. Refactor فقط وقتی انجام می‌شود که مستقیماً برای همان نیازها لازم باشد.
3. تغییر دیتابیس فقط بر اساس Schema واقعی انجام می‌شود.
4. هر تغییر با Before → After، دلیل و Validation مستند می‌شود.

---

# فاز ۱ — AI/RAG و جداسازی Data Access

**وضعیت: انجام‌شده**

- جداسازی Data Access از Logic.
- ایجاد `KnowledgeRetriever`.
- انتقال Keyword Processing به `QueryPreprocessor`.
- نرمال‌سازی فارسی/عربی و اعداد.
- انتقال scoring/ranking/context building از Repository به Service.

ساختار نهایی:

`Endpoint → KnowledgeRetriever → QueryPreprocessor + AiKnowledgeRepository → LlmService`

جزئیات: `MCC-CHANGE-002`, `MCC-CHANGE-003`, `MCC-CHANGE-007`.

---

# فاز ۲ — Login/Register و OTP با Kavenegar

**وضعیت: پیاده‌سازی کد انجام‌شده؛ تست SMS واقعی وابسته به credential محیط اجرا**

انجام‌شده:

- UI → send endpoint → AuthService → Kavenegar → verify endpoint بررسی و تکمیل شد.
- OTP فقط به‌صورت hash در DB نگهداری می‌شود.
- ترتیب Workflow اصلاح شد تا SMS شکست‌خورده OTP قبلی را invalidate نکند.
- Verify Lookup با `type=sms` استفاده می‌شود.
- Error Handling رسمی Kavenegar با status/message اضافه شد.

باقی‌مانده محیط اجرا:

- `KAVENEGAR_API_KEY`
- `KAVENEGAR_OTP_TEMPLATE`
- `OTP_PEPPER`
- تست واقعی send / wrong-code / expiry / resend

جزئیات: `MCC-CHANGE-004`, `MCC-CHANGE-009`.

---

# فاز ۳ — تکمیل AI/RAG

**وضعیت: بخش اصلی انجام‌شده؛ Migration و E2E باید روی محیط واقعی اجرا شوند**

انجام‌شده:

- رفع duplicate `session_start()` در endpoint AI.
- Persian normalization، numbers و domain synonyms.
- Domain Detection برای pregnancy / child / general.
- منابع کودک در RAG: child_nutrition، child_supplements، common_conditions، vaccination_schedule، development_milestones، growth_standards.
- هماهنگ‌سازی System Prompt.
- ایجاد persistence برای AI conversations/messages.
- ذخیره سؤال/پاسخ AI.

باقی‌مانده محیط اجرا:

- اجرای `database/migrations/20261015_001_create_ai_conversations.sql`.
- تست End-to-End Retriever → OpenRouter → DB.

جزئیات: `MCC-CHANGE-005`, `MCC-CHANGE-006`, `MCC-CHANGE-007`.

---

# فاز ۴ — Infant/Child Content و Crawler

**وضعیت: پیاده‌سازی اصلی انجام‌شده؛ اجرای Crawl/Import واقعی وابسته به محیط است**

انجام‌شده:

- بررسی Schema واقعی Child tables.
- انتخاب CDC Developmental Milestones به‌عنوان منبع معتبر اولیه.
- ایجاد `tools/crawlers/cdc_milestones.php`.
- Raw JSON export با provenance و attribution.
- ایجاد `tools/crawlers/import_cdc_milestones.php` برای Reviewed Persian JSON.
- Importer دارای validation، `--dry-run`، Transaction و duplicate check است.
- ایجاد `ChildCareRepository` برای Data Access صفحات کودک.
- تکمیل پنج لینک از قبل موجود منوی کودک:
  - `growth.php`
  - `vaccine.php`
  - `child-nutrition.php`
  - `milestones.php`
  - `conditions.php`
- CSS مشترک Child pages ایجاد شد.
- مکمل‌های کودک در صفحه تغذیه بر اساس سن نمایش داده می‌شوند.

مسیر داده Crawler:

`CDC → Raw JSON → ترجمه/بازبینی → Reviewed JSON → Import → development_milestones`

باقی‌مانده محیط اجرا:

- اجرای crawler روی سرور دارای اینترنت.
- بازبینی/ترجمه محتوای Raw.
- اجرای Importer روی DB واقعی.
- تست Browser صفحات Child علیه DB واقعی.

جزئیات: `MCC-CHANGE-008`, `MCC-CHANGE-010`.

---

# فاز ۵ — حذف تبلیغات گهواره از Articles

**وضعیت: پیاده‌سازی کد انجام‌شده؛ Cleanup دائمی DB باید در محیط واقعی اجرا شود**

انجام‌شده:

- بررسی HTML واقعی Dump و شناسایی `install-inline`, `install-screenshot`, landing/install URLs و app screenshots.
- ایجاد `ArticleContentSanitizer`.
- حذف فقط CTA/بلوک/تصویر تبلیغاتی؛ لینک‌های محتوایی عادی `gahvare.net/articles/...` کورکورانه حذف نمی‌شوند.
- استفاده از Sanitizer قبل از نمایش مقاله و excerpt مقالات مرتبط.
- افزودن Data Access لازم برای cleanup به `ArticleRepository`.
- ایجاد `tools/cleanup_gahvare_article_ads.php` با `--dry-run` و `--apply` و Transaction.

باقی‌مانده محیط اجرا:

1. اجرای `php tools/cleanup_gahvare_article_ads.php --dry-run`.
2. بررسی تعداد/نمونه رکوردهای تغییرکرده.
3. اجرای `php tools/cleanup_gahvare_article_ads.php --apply` روی DB واقعی.
4. تست چند Article واقعی در Browser.

جزئیات: `MCC-CHANGE-011`.

---

# فاز ۶ — Profile، User Data، Logging و Personalization

**وضعیت: مرحله بعد**

Scope:

- کامل‌تر شدن پروفایل.
- ورود داده‌های شخصی موردنیاز بعد از Login.
- ذخیره تعاملات در DB در محدوده درخواست اولیه.
- Personalization.
- پیشنهاد Article/FAQ بر اساس نیاز کاربر.

نکته: ذخیره AI chat در فاز ۳ انجام شد چون بخشی از همان Pipeline بود.

---

# فاز ۷ — Doctor Contact و Reminder با Bale

**وضعیت: برنامه‌ریزی‌شده**

- ارتباط با پزشک مشابه Support با Workflow مخصوص.
- appointment/medication reminder.
- ارسال Reminder با API بله.

---

# فاز ۸ — Admin Panel

**وضعیت: برنامه‌ریزی‌شده**

پنل فقط برای Featureهای همین Scope ایجاد می‌شود.

---

# Definition of Done

یک Task فقط زمانی Done است که:

1. وضعیت واقعی قبل از تغییر بررسی شده باشد.
2. تغییر فقط در Scope کاربر باشد.
3. پیاده‌سازی انجام شده باشد.
4. Validation واقعی متناسب با دسترسی موجود انجام شود.
5. محدودیت Validation صریح نوشته شود.
6. تغییر مستند شود.
