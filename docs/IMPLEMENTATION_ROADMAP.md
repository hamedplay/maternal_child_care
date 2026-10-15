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

**وضعیت: انجام‌شده برای بخش جداسازی معماری؛ ادامه قابلیت‌های AI در فاز ۳**

## نیازهای پوشش‌داده‌شده

- جدا کردن Database Access از Logic.
- ایجاد Retriever مستقل.
- جدا کردن Keyword Processing از Repository.
- نرمال‌سازی اعداد فارسی/عربی و متن فارسی.

## ساختار نهایی این فاز

`Endpoint → KnowledgeRetriever → QueryPreprocessor + AiKnowledgeRepository → LlmService`

- `QueryPreprocessor` = normalize + keywords + age/gender extraction
- `AiKnowledgeRepository` = Data Access only
- `KnowledgeRetriever` = source selection + ranking + context building

جزئیات در `MCC-CHANGE-002` و `MCC-CHANGE-003` ثبت شده است.

---

# فاز ۲ — Login/Register و OTP با Kavenegar

**وضعیت: در حال اجرا — کد Integration آماده است؛ ارسال واقعی نیازمند credential محیط اجرا است**

## Scope

- ارسال OTP به شماره موبایل از Kavenegar Verify Lookup.
- نگهداری server-side اطلاعات لازم برای verify بدون ذخیره OTP plaintext.
- Verify کد واردشده.
- Login/Register خودکار بعد از verify.
- Resend، expiry و محدودیت تلاش.

## وضعیت واقعی فعلی

- `AuthService` وجود دارد.
- `OtpRepository` وجود دارد.
- `KavenegarService` وجود دارد.
- endpointهای send/verify وجود دارند.
- UI Login/OTP به endpointها متصل است.
- جدول `otp_verifications` در دیتابیس واقعی وجود دارد.
- API Key از `KAVENEGAR_API_KEY` و Template از `KAVENEGAR_OTP_TEMPLATE` خوانده می‌شوند.

## کار باقی‌مانده

- تنظیم API Key و Template واقعی در محیط اجرا.
- تست واقعی ارسال SMS، wrong code، expiry و resend روی سرور متصل به Kavenegar.

---

# فاز ۳ — تکمیل AI/RAG

**وضعیت: بعد از فاز ۲**

## Scope

- پیدا کردن Root Cause باگ «اولین درخواست AI کار نمی‌کند».
- تکمیل keywordها/synonymهای domain در حد نیاز واقعی.
- استفاده از Retriever مستقل ایجادشده در فاز ۱.
- اتصال context دیتابیس به Prompt و OpenRouter.
- تنظیم OpenRouter از `config/ai.php` طبق درخواست.
- ذخیره سؤال و پاسخ AI در دیتابیس.

---

# فاز ۴ — Infant/Child Content و Crawler

**وضعیت: برنامه‌ریزی‌شده**

## Scope

- انتخاب منبع معتبر برای محتوای نوزاد/کودک.
- تکمیل بخش نوزاد.
- ایجاد/تکمیل بخش کودک مشابه Pregnancy.
- Crawler و export/import موردنیاز.

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
- ذخیره AI chat.
- Personalization.
- پیشنهاد Article/FAQ بر اساس نیاز کاربر.

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
