# نقشه راه توسعه سامانه Maternal Child Care

این سند نقشه راه اجرایی درخواست‌های توسعه جدید سامانه است. جزئیات هر تغییر واقعی بعد از پیاده‌سازی در `docs/CHANGE_LOG.md` ثبت می‌شود. هدف این Roadmap این است که کارهای متعدد پروژه به فازهای کوچک، قابل تست و وابسته به هم تقسیم شوند تا هر مرحله قبل از رفتن به مرحله بعد قابل Validation باشد.

> اصل اجرایی: Inspect → Root Cause / Requirement → Design → Implement → Validate → Document

---

## خلاصه معماری فعلی که در بررسی اولیه مشاهده شد

- پروژه PHP است و لایه‌های `Repositories` و `Services` دارد.
- اتصال دیتابیس در `app/Core/Database.php` متمرکز شده است.
- `AuthService`، `OtpRepository` و `KavenegarService` از قبل وجود دارند و OTP به صورت پایه پیاده‌سازی شده است.
- `AiKnowledgeRepository` هم‌زمان مسئول preprocessing، استخراج keyword، تشخیص سن/جنسیت، retrieval از چند جدول، رتبه‌بندی نتایج و تولید context block است؛ بنابراین Business Logic و Data Access در این کلاس مخلوط شده‌اند.
- `LlmService` سوال کاربر، history و context را با System Prompt ترکیب و به OpenRouter ارسال می‌کند.
- محتوای مقالات HTML است و هنگام استفاده در AI باید sanitize/normalize یا به متن مناسب retrieval تبدیل شود.
- در وضعیت فعلی schema/migration/export دیتابیس داخل Repository پیدا نشد و باید برای reproducibility پروژه اضافه شود.

---

# فاز 0 — Audit، امنیت، مستندسازی و Baseline

## هدف

قبل از توسعه Featureهای جدید، وضعیت واقعی پروژه و نقاط پرریسک تثبیت شود.

## کارها

1. Inventory کامل فایل‌ها، صفحات، جداول و dependencyها.
2. شناسایی endpointهای ورود، OTP، AI و Support.
3. تهیه فهرست جداول و ارتباط‌ها از دیتابیس واقعی یا export موجود.
4. ایجاد استاندارد migration/schema/export برای دیتابیس.
5. شناسایی secretهای Hard-coded و انتقال آن‌ها به Environment Variable.
6. بررسی logging فعلی و تعیین اینکه چه eventهایی باید persist شوند.
7. تعریف قرارداد معماری لایه‌ها:
   - Repository = Data Access
   - Service = Business Logic
   - Controller/API = HTTP orchestration
   - DTO/Value Object در صورت نیاز
8. تعریف روش Validation برای هر فاز.

## نکته امنیتی فوری

در بررسی اولیه یک OpenRouter API Key داخل `config/ai.php` مشاهده شد. چون secret وارد Git شده، فقط حذف آن از فایل کافی نیست؛ کلید موجود باید در OpenRouter revoke/rotate شود و کلید جدید فقط از Environment Variable خوانده شود.

## خروجی

- Baseline معماری
- Database inventory
- Secret management امن
- مسیر مشخص برای migration/export

---

# فاز 1 — Refactor معماری Repository / Service و دیتابیس

## هدف

جدا کردن Business Logic از Data Access قبل از توسعه RAG، Personalization و Admin.

## مسئله فعلی

بعضی Repositoryها علاوه بر SQL، منطق پردازش و تصمیم‌گیری نیز دارند. نمونه اصلی `AiKnowledgeRepository` است.

## ساختار هدف

### Repository
فقط Query، Insert، Update، Delete و mapping داده.

### Service
منطق کسب‌وکار، normalization، ranking، تصمیم‌گیری و orchestration.

### نمونه تقسیم پیشنهادی

- `KnowledgeRepository` → خواندن داده مرجع از دیتابیس
- `KeywordExtractor` یا `QueryPreprocessor` → normalize، stopword، stemming، synonym، اعداد فارسی
- `KnowledgeRetriever` → انتخاب منابع، scoring و ranking
- `RagService` → ساخت context و هماهنگی Retriever + LLM
- `LlmService` → فقط ارتباط با provider مدل زبانی

## دیتابیس

در همین فاز باید فایل‌های reproducible برای ساختار دیتابیس ایجاد شوند:

- schema export
- migrationهای جدید از این نقطه به بعد
- data seed فقط برای داده‌هایی که مجاز و لازم هستند
- crawler/import scripts جدا از runtime سایت

---

# فاز 2 — Authentication و OTP واقعی با Kavenegar

## هدف

تکمیل Login/Registration با OTP واقعی و قابل audit.

## وضعیت فعلی

`AuthService` و `KavenegarService` وجود دارند. OTP تولید، hash و verify می‌شود و محدودیت زمان، resend و تعداد تلاش نیز در کد وجود دارد.

## کارها

1. بررسی UI و endpointهای Login/Register.
2. اتصال API Key واقعی Kavenegar از ENV، نه source code.
3. تنظیم Verify Lookup Template.
4. ذخیره lifecycle کامل OTP در دیتابیس:
   - requested
   - sent / failed
   - verified / expired
   - attempts
   - IP / User Agent در حد لازم
5. هیچ OTP خامی نباید به صورت دائمی در دیتابیس ذخیره شود؛ برای verification، hash سمت سرور کافی است.
6. تست ارسال واقعی، resend، expiry، wrong code و rate limiting.
7. ایجاد audit event برای login/register موفق و ناموفق.

## توضیح امنیتی

نیاز «کدی که برای کاربر ارسال می‌شود سمت ما هم باشد تا بتوانیم بررسی کنیم» با ذخیره `hash` امن حل می‌شود. لازم نیست OTP به صورت plaintext ذخیره شود. کد ورودی کاربر hash شده و با مقدار ذخیره‌شده مقایسه می‌شود.

---

# فاز 3 — AI / RAG پایدار و قابل توسعه

## هدف

رفع باگ اولین درخواست AI و تبدیل RAG سبک فعلی به pipeline روشن و تست‌پذیر.

## Pipeline هدف

User Question
→ Normalize
→ Keyword / Entity Extraction
→ Retriever
→ Ranked Documents
→ Context Builder
→ Prompt Integration
→ LLM
→ Response
→ Conversation Persistence

## کارها

1. بازتولید و Root Cause باگ «بار اول کار نمی‌کند».
2. تکمیل normalization فارسی:
   - ی/ي
   - ک/ك
   - نیم‌فاصله
   - اعداد فارسی، عربی و لاتین
3. تکمیل stopwords و synonyms بر اساس domain مادر/بارداری/نوزاد/کودک.
4. جدا کردن `extractKeywords()` از Repository.
5. نوشتن متد/کلاس `Retriever` مستقل.
6. retrieval از FAQ، Article، Pregnancy، Child، Growth، Nutrition، Warning Signs و سایر منابع.
7. scoring/ranking قابل توضیح.
8. حذف HTML noise از مقالات قبل از retrieval و context generation.
9. Context Builder با محدودیت طول و deduplication.
10. اتصال امن OpenRouter با API Key از ENV.
11. انتخاب مدل رایگان/مناسب OpenRouter به صورت config، نه hard-code دائمی.
12. ذخیره Question، Response، context metadata، model و timestamp در دیتابیس.
13. logging خطاهای provider بدون ذخیره secret.
14. تست سوال‌های فارسی، عدد فارسی، سن کودک، بارداری و سوال‌های خارج از knowledge base.

---

# فاز 4 — محتوا، Crawler و تکمیل بخش نوزادان/کودکان

## هدف

تکمیل محتوای علمی بخش نوزادان و ایجاد بخش کودکان مشابه Pregnancy با منبع معتبر.

## اصول قبل از Crawl

منبع باید از نظر اعتبار پزشکی، شرایط استفاده، robots.txt و امکان بازنشر بررسی شود. هدف crawler نباید کپی کورکورانه محتوا باشد؛ metadata منبع و provenance باید حفظ شود.

## کارها

1. انتخاب source معتبر.
2. طراحی crawler مستقل از runtime سایت.
3. ذخیره source URL، crawl time و source identifier.
4. normalize و sanitize HTML.
5. duplicate detection.
6. crawler export/import script.
7. تکمیل Infant content.
8. ساخت Child section مشابه Pregnancy.
9. اتصال داده‌های کودک به RAG.
10. ثبت منبع داده در UI در صورت لزوم.

---

# فاز 5 — پاک‌سازی مقالات Crawled و حذف تبلیغات گهواره

## هدف

محتوای مقاله حفظ شود اما تبلیغات، CTAها و تصاویر تبلیغاتی مربوط به نصب اپلیکیشن/برند گهواره حذف شوند.

## روش

به جای حذف رشته‌ای ساده، یک Content Sanitizer ایجاد می‌شود که روی DOM/HTML کار کند و selector/patternهای تبلیغاتی را حذف کند.

## کارها

1. نمونه‌برداری از HTMLهای موجود.
2. شناسایی الگوهای تبلیغ گهواره.
3. حذف banner، app install CTA، tracking/linkهای غیرضروری و عناصر تبلیغاتی.
4. حفظ heading، paragraph، list، table و تصاویر محتوایی مجاز.
5. sanitize امنیتی HTML برای جلوگیری از script/event handler ناخواسته.
6. script قابل تکرار برای پاک‌سازی رکوردهای موجود.
7. اعمال sanitizer در importهای بعدی crawler.

---

# فاز 6 — User Data، Logging، Profile و Personalization

## هدف

ایجاد مدل کاربر واقعی به جای login ساده با شماره تلفن.

## داده‌های پیشنهادی پروفایل

فقط داده موردنیاز و با حداقل‌گرایی داده جمع‌آوری شود، به‌ویژه چون حوزه سلامت حساس است.

نمونه:
- نام
- اطلاعات پایه موردنیاز
- وضعیت بارداری یا اطلاعات کودک در صورت انتخاب کاربر
- تاریخ‌های مرتبط
- preferences
- consent/privacy flags

## Event Logging

تمام «لاگ»ها یکسان نیستند. دو دسته جدا می‌شوند:

### Technical Logs
برای خطا، performance و debugging؛ الزاماً همه داخل DB اصلی محصول ذخیره نمی‌شوند.

### Product/Audit Events
رویدادهای معنادار کاربر که برای personalization/audit لازم‌اند، مانند:
- login
- article view
- FAQ view
- AI question
- support message
- doctor contact request
- reminder create/update/delete

## Conversation Persistence

- conversation
- message
- role
- content
- created_at
- model metadata
- retrieval metadata در حد لازم

## Personalization

پس از وجود داده کافی:
- پیشنهاد مقاله مرتبط
- FAQ مرتبط
- ادامه گفتگو با context کاربر
- recommendation براساس مرحله بارداری/سن کودک/تعاملات قبلی

---

# فاز 7 — Recommender، ارتباط با پزشک و Reminder

## Recommender

MVP ابتدا rule/content-based خواهد بود، نه ML پیچیده:

User context + recent activity + pregnancy/child stage
→ candidate articles/FAQs
→ score
→ recommendations

بعداً در صورت وجود داده کافی می‌توان مدل پیچیده‌تر اضافه کرد.

## ارتباط با پزشک

گزینه جدید مشابه Support ایجاد می‌شود اما entity و workflow مستقل خواهد داشت تا پیام پشتیبانی با درخواست پزشکی مخلوط نشود.

موارد لازم:
- doctor/contact request
- status
- user message
- timestamps
- admin handling

## Reminder

کاربر بتواند appointment یا medication reminder ثبت کند.

ساختار:
- reminder type
- title
- scheduled_at
- recurrence در صورت نیاز
- delivery channel
- status

اتصال به «بله» یا provider دیگر باید پشت Interface/Notification Service باشد تا provider قابل تعویض باشد.

---

# فاز 8 — Admin Panel

## هدف

مدیریت عملیاتی سامانه بدون دستکاری مستقیم دیتابیس.

## MVP پنل ادمین

1. احراز هویت Admin و Role/Permission.
2. Dashboard پایه.
3. مدیریت کاربران.
4. مدیریت مقالات، FAQ و محتوای کودک/بارداری.
5. مشاهده وضعیت crawler/import.
6. مدیریت پیام‌های Support.
7. مدیریت درخواست ارتباط با پزشک.
8. مشاهده AI conversations در چارچوب مجاز و privacy-aware.
9. مشاهده audit events.
10. فعال/غیرفعال کردن محتوا.

## امنیت

- Admin session جدا و امن
- authorization در backend، نه فقط مخفی‌کردن UI
- CSRF protection
- audit log برای عملیات حساس Admin

---

# ترتیب اجرای پیشنهادی

1. **Phase 0 — Audit/Security/Baseline**
2. **Phase 1 — Architecture + DB separation**
3. **Phase 2 — Authentication + Kavenegar OTP**
4. **Phase 3 — AI/RAG + Conversation persistence**
5. **Phase 4 — Infant/Child content + Crawler**
6. **Phase 5 — Article sanitizer / Ghevareh ad removal**
7. **Phase 6 — Profile + Logging + Personalization**
8. **Phase 7 — Recommender + Doctor Contact + Reminder**
9. **Phase 8 — Admin Panel**

این ترتیب به این دلیل انتخاب شده که Featureهای مراحل بعدی به لایه‌های داده، هویت کاربر و معماری AI مراحل قبل وابسته هستند.

---

# Definition of Done برای هر Task

یک Task فقط زمانی Done محسوب می‌شود که:

1. وضعیت قبل بررسی شده باشد.
2. Scope دقیق مشخص باشد.
3. پیاده‌سازی انجام شده باشد.
4. Validation واقعی انجام شده باشد.
5. خطاهای مهم بررسی شده باشند.
6. تغییر در `docs/CHANGE_LOG.md` با Before → After، دلیل و روش اجرا ثبت شده باشد.
7. secret یا credential وارد Git نشده باشد.
8. در تغییر دیتابیس، migration/export لازم نیز وجود داشته باشد.

---

# مواردی که فعلاً نباید با هم ادغام شوند

برای جلوگیری از coupling زیاد، این موضوعات مستقل نگه داشته می‌شوند:

- Support Message و Doctor Contact
- Technical Log و Product/Audit Event
- Repository و Business Service
- Retriever و LLM Provider
- Crawler و Runtime Web Application
- Reminder Domain و Notification Provider
- User Profile و AI Conversation

این تفکیک باعث می‌شود هر بخش جداگانه تست، توسعه و در آینده تعویض شود.
