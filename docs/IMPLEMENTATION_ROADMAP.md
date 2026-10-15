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
4. هر تغییر با Before → After، دلیل و Validation در `CHANGE_LOG.md` ثبت می‌شود.

---

# فاز ۱ — AI/RAG و جداسازی Data Access

**وضعیت: انجام‌شده**

## انجام‌شده

- جدا کردن Database Access از Logic.
- ایجاد `KnowledgeRetriever` مستقل.
- انتقال Keyword Processing به `QueryPreprocessor`.
- نرمال‌سازی حروف و اعداد فارسی/عربی.
- انتقال scoring/ranking/context building از Repository به Service.

## ساختار نهایی

`Endpoint → KnowledgeRetriever → QueryPreprocessor + AiKnowledgeRepository → LlmService`

- `QueryPreprocessor` = normalize + keyword/entity/domain analysis
- `AiKnowledgeRepository` = Data Access only
- `KnowledgeRetriever` = source selection + ranking + context building

جزئیات: `MCC-CHANGE-002`, `MCC-CHANGE-003`, `MCC-CHANGE-007`.

---

# فاز ۲ — Login/Register و OTP با Kavenegar

**وضعیت: پیاده‌سازی کد انجام‌شده؛ فقط تست SMS واقعی وابسته به credential محیط اجرا است**

## انجام‌شده

- بررسی کامل UI → send endpoint → AuthService → Kavenegar → verify endpoint.
- حفظ OTP به‌صورت hash برای server-side verification.
- اصلاح ترتیب ارسال/ثبت OTP تا SMS شکست‌خورده OTP قبلی را خراب نکند.
- اتصال Verify Lookup رسمی Kavenegar با `receptor/token/template/type=sms`.
- بررسی مستقیم مستند رسمی `kavenegar.com/rest.html`.
- تبدیل نتیجه Kavenegar از boolean ساده به نتیجه ساختاریافته شامل `success/status/message`.
- تفکیک Transport Error از Provider/API Error.
- پوشش خطاهای مهم رسمی مثل `401/403/409/418/422/424/426/429/431/432/451`.
- حذف log کردن کل Response Provider و نگهداری فقط HTTP/status/message برای جلوگیری از ورود receptor/token/message به log.
- حفظ این اصل که فقط بعد از Send موفق، OTP قبلی invalidate و OTP جدید persist شود.

## باقی‌مانده محیط اجرا

- تنظیم `KAVENEGAR_API_KEY`.
- تنظیم `KAVENEGAR_OTP_TEMPLATE` با Template تاییدشده که `%token` دارد.
- اطمینان از فعال بودن سرویس موردنیاز Verify Lookup روی حساب.
- تست واقعی send / wrong-code / expiry / resend.

جزئیات: `MCC-CHANGE-004`, `MCC-CHANGE-009`.

---

# فاز ۳ — تکمیل AI/RAG

**وضعیت: بخش اصلی پیاده‌سازی انجام‌شده؛ Migration و E2E واقعی هنوز باید روی محیط پروژه اجرا شوند**

## انجام‌شده

- رفع defect قطعی duplicate `session_start()` در JSON endpoint.
- تکمیل Persian normalization، numbers و بخشی از domain synonyms.
- Domain Detection برای تفکیک pregnancy / child / general.
- اضافه شدن منابع واقعی کودک به RAG:
  - child_nutrition
  - child_supplements
  - common_conditions
  - vaccination_schedule
  - development_milestones
  - growth_standards
- هماهنگ‌سازی System Prompt با Pregnancy + Infant/Child context.
- ایجاد persistence برای AI conversations/messages با Migration جدید.
- ذخیره سؤال و پاسخ AI از endpoint.

## باقی‌مانده محیط اجرا

- اجرای `database/migrations/20261015_001_create_ai_conversations.sql` روی MariaDB پروژه.
- تست End-to-End Retriever → OpenRouter → DB persistence روی محیط واقعی.

جزئیات: `MCC-CHANGE-005`, `MCC-CHANGE-006`, `MCC-CHANGE-007`.

---

# فاز ۴ — Infant/Child Content و Crawler

**وضعیت: شروع‌شده**

## انجام‌شده

- بررسی ساختار واقعی Child tables در Dump.
- بررسی وضعیت UI: صفحات Pregnancy وجود دارند ولی Child section هم‌سطح آن هنوز در `public/` کامل نیست.
- انتخاب CDC Developmental Milestones به‌عنوان منبع اولیه معتبر.
- بررسی robots.txt مسیرهای CDC Developmental Milestones.
- بررسی شرایط reuse متن CDC و الزام Attribution/Non-endorsement.
- ایجاد Crawler مستقل:
  - `tools/crawlers/cdc_milestones.php`
- ایجاد مستند اجرای crawler:
  - `tools/crawlers/README.md`
- خروجی crawler به‌صورت JSON با provenance منبع، سن، دسته و متن milestone.

## تصمیم اجرایی

Crawler مستقیماً داده انگلیسی CDC را وارد DB فارسی نمی‌کند. مسیر داده:

`CDC → Raw JSON Export → ترجمه/بازبینی → Import → DB`

این جداسازی برای جلوگیری از آلوده‌شدن دیتابیس فارسی با Raw English Source است و مستقیماً به نیاز crawler/export مربوط می‌شود.

## باقی‌مانده

- اجرای crawler در محیط دارای اینترنت و بررسی Export واقعی.
- آماده‌سازی Import محتوای بازبینی‌شده.
- تکمیل صفحات Child/Infant مشابه ساختار Pregnancy با استفاده از جداول موجود.

---

# فاز ۵ — حذف تبلیغات گهواره از Articles

**وضعیت: برنامه‌ریزی‌شده**

## Scope

- حذف CTA نصب اپلیکیشن گهواره.
- حذف بلوک‌ها و تصاویر تبلیغاتی گهواره.
- حفظ HTML محتوایی مقاله.
- اعمال پاک‌سازی روی داده‌های موجود و ورودی‌های بعدی Crawler.

---

# فاز ۶ — Profile، User Data، Logging و Personalization

**وضعیت: برنامه‌ریزی‌شده**

## Scope

- کامل‌تر شدن پروفایل.
- ورود داده‌های شخصی موردنیاز بعد از Login.
- ذخیره تعاملات در DB در محدوده درخواست اولیه.
- Personalization.
- پیشنهاد Article/FAQ بر اساس نیاز کاربر.

نکته: ذخیره AI chat از این Scope زودتر در فاز ۳ انجام شد چون مستقیماً بخشی از pipeline AI بود.

---

# فاز ۷ — Doctor Contact و Reminder با Bale

**وضعیت: برنامه‌ریزی‌شده**

## Scope

- گزینه ارتباط با پزشک مشابه Support اما با workflow مخصوص خودش.
- ثبت appointment/medication reminder.
- ارسال Reminder با API بله طبق درخواست.

---

# فاز ۸ — Admin Panel

**وضعیت: برنامه‌ریزی‌شده**

## Scope

پنل مدیریتی فقط برای مدیریت قابلیت‌های همین پروژه و Featureهای درخواست‌شده ایجاد می‌شود؛ Feature مدیریتی خارج از این Scope اضافه نمی‌شود.

---

# Definition of Done

یک Task فقط زمانی Done است که:

1. وضعیت واقعی قبل از تغییر بررسی شده باشد.
2. تغییر فقط در Scope کاربر باشد.
3. پیاده‌سازی انجام شده باشد.
4. Validation واقعی متناسب با دسترسی موجود انجام شود.
5. محدودیت Validation صریح نوشته شود.
6. تغییر در `docs/CHANGE_LOG.md` مستند شود.
