# مستند تغییرات سامانه Maternal Child Care

این سند مرجع اصلی ثبت تغییراتی است که از این تاریخ به بعد روی سامانه `maternal_child_care` انجام می‌شود.

هدف فقط ثبت یک Changelog کوتاه نیست؛ هر تغییر باید به شکلی نوشته شود که بعداً مشخص باشد مسئله چه بوده، ساختار قبل چگونه کار می‌کرده، چرا تغییر انجام شده، چه فایل‌هایی تغییر کرده‌اند، چگونه پیاده‌سازی شده و نتیجه چگونه Validation شده است.

> اصل اجرایی: Inspect → Requirement / Root Cause → Design → Implement → Validate → Document

---

## قواعد مستندسازی

1. هیچ تغییر برنامه‌ریزی‌شده‌ای بدون ثبت در این سند نهایی نمی‌شود.
2. تغییر فقط در Scope درخواست کاربر انجام می‌شود.
3. در Bug Fix، Symptom و Root Cause از هم جدا نوشته می‌شوند.
4. نتیجه تست واقعی با نتیجه مورد انتظار اشتباه گرفته نمی‌شود.
5. اگر Validation کامل ممکن نباشد، محدودیت آن صریح نوشته می‌شود.
6. نام فایل‌ها و Componentهای تغییرکرده صریحاً ذکر می‌شوند.
7. تغییرات دیتابیس فقط در صورت نیاز همان Feature انجام می‌شوند.

---

# گزارش تغییرات

## MCC-CHANGE-000 — ایجاد سیستم مستندسازی تغییرات

### تاریخ
2026-10-15

### درخواست / مسئله
پیش از شروع توسعه لازم بود یک مرجع دائمی برای ثبت دلیل، روش اجرا، Before/After، Validation و Rollback تمام تغییرات ایجاد شود.

### وضعیت قبل
پروژه README داشت، اما سند اختصاصی تاریخچه و منطق تغییرات توسعه‌ای نداشت.

### تصمیم فنی
فایل مستقل `docs/CHANGE_LOG.md` ایجاد شد تا README روی معرفی پروژه باقی بماند و مستند تغییرات بتواند جزئیات فنی بیشتری نگهداری کند.

### نتیجه
از این Change به بعد، تغییرات توسعه‌ای باید در همین سند ثبت شوند.

---

## MCC-CHANGE-001 — اصلاح Roadmap و قفل کردن Scope روی درخواست اولیه

### تاریخ
2026-10-15

### درخواست / مسئله
کاربر تصریح کرد هیچ Feature یا Refactor خارج از درخواست اولیه انجام نشود. Roadmap اولیه چند فعالیت عمومی مثل Audit گسترده و Hardening مستقل را به‌عنوان فاز اجرایی آورده بود که مستقیماً جزء نیازهای کاربر نبودند.

### وضعیت قبل
`docs/IMPLEMENTATION_ROADMAP.md` شامل Phase عمومی Audit/Security/Baseline و چند کار جانبی بود.

### هدف
Roadmap فقط شامل همان قابلیت‌هایی باشد که کاربر در درخواست اولیه خواسته است.

### Before → After
**Before:** فازهای عمومی و فعالیت‌های جانبی نیز جزو برنامه بودند.

**After:** Roadmap فقط روی AI/RAG و جداسازی Data Access، OTP/Kavenegar، محتوای نوزاد/کودک و Crawler، حذف تبلیغات گهواره، Profile/Personalization، Doctor Contact/Reminder و Admin Panel متمرکز شد.

### Validation
Roadmap بعد از Update از `main` بازخوانی و با درخواست اولیه و Dump واقعی دیتابیس تطبیق داده شد.

### نتیجه
Scope توسعه قفل شد و هر تغییر باید مستقیماً به یکی از نیازهای اولیه کاربر متصل باشد.

---

## MCC-CHANGE-002 — شروع فاز ۱: ایجاد مرز Query Preprocessing و Knowledge Retrieval

### تاریخ
2026-10-15

### درخواست / مسئله
در درخواست اولیه، جداسازی منطق AI از دسترسی دیتابیس، تغییر Keyword Processing و ایجاد Retriever مستقل خواسته شده بود.

### وضعیت قبل
مسیر درخواست AI چنین بود:

`public/ai-qa-ask.php → AiKnowledgeRepository::search(question) → LlmService`

`AiKnowledgeRepository` علاوه بر SQL، normalize، keyword extraction، تشخیص سن/جنسیت، scoring و context formatting را نیز انجام می‌داد.

### هدف
ایجاد اولین boundary بدون بازنویسی یک‌باره و پرریسک کل Retrieval.

### فایل‌های تغییرکرده
- Added: `app/Services/QueryPreprocessor.php`
- Added: `app/Services/KnowledgeRetriever.php`
- Updated: `public/ai-qa-ask.php`

### Before → After
**Before:**
`Endpoint → AiKnowledgeRepository → LlmService`

**After:**
`Endpoint → KnowledgeRetriever → QueryPreprocessor → AiKnowledgeRepository → LlmService`

### جزئیات اجرا
- نرمال‌سازی `ي/ى/ك` به حروف فارسی.
- تبدیل اعداد فارسی/عربی به لاتین.
- تبدیل نیم‌فاصله به فاصله و یکدست‌سازی whitespace.
- حذف وابستگی مستقیم endpoint به Repository.

### Validation
- PSR-4 و fallback autoloader پروژه بررسی شد.
- فایل‌های جدید از `main` بازخوانی شدند.
- endpoint بعد از Update از `KnowledgeRetriever` استفاده می‌کند.

### نتیجه
مرز اولیه Retrieval ایجاد شد، اما در این Change هنوز منطق keyword/scoring داخل Repository باقی مانده بود و برای گام بعدی برنامه‌ریزی شد.

---

## MCC-CHANGE-003 — تکمیل فاز ۱: جداسازی کامل AI Logic از Data Access

### تاریخ
2026-10-15

### درخواست / مسئله
کاربر صریحاً خواسته بود Repository مربوط به AI همزمان Business Logic و Database Access را نگهداری نکند. بعد از MCC-CHANGE-002 هنوز بخش مهمی از منطق در `AiKnowledgeRepository` باقی مانده بود.

### وضعیت قبل از تغییر
`AiKnowledgeRepository` مسئول این موارد بود:

- استخراج keyword
- stopword filtering
- light stemming
- synonym expansion
- تشخیص سن کودک
- تشخیص جنسیت
- scoring/ranking
- تبدیل HTML مقاله به متن
- انتخاب snippet مرتبط
- ساخت context block
- اجرای SQL روی جداول مختلف

در نتیجه Repository هم Data Access بود و هم Retrieval/Business Logic.

### هدف
Repository فقط Query اجرا کند و Row خام برگرداند. تمام تحلیل سؤال، ranking و ساخت context به Service layer منتقل شود.

### تصمیم فنی
سه مسئولیت مستقل تعریف شد:

1. `QueryPreprocessor` → تحلیل و نرمال‌سازی Query.
2. `AiKnowledgeRepository` → فقط Data Access.
3. `KnowledgeRetriever` → orchestration، scoring/ranking و Context Building.

این تقسیم دقیقاً بر اساس نیاز اولیه کاربر انجام شد و هیچ لایه یا Pattern اضافه‌ای خارج از این نیاز ایجاد نشد.

### فایل‌های تغییرکرده

#### Updated — `app/Services/QueryPreprocessor.php`
مسئولیت‌های زیر به این Service منتقل شد:

- normalize حروف فارسی/عربی
- تبدیل اعداد فارسی/عربی
- stopword filtering
- stemming سبک
- synonym expansion
- استخراج سن بر حسب ماه
- استخراج جنسیت

متد `analyze()` اکنون خروجی ساختاریافته زیر را تولید می‌کند:

- `question`
- `keywords`
- `age_months`
- `gender`

#### Updated — `app/Repositories/AiKnowledgeRepository.php`
منطق preprocessing، scoring و context formatting حذف شد.

Repository اکنون فقط متدهای Data Access دارد، از جمله:

- `findFaqs()`
- `findArticles()`
- `findPregnancyNutrition()`
- `findPregnancyWeeks()`
- `findPregnancyWarningSigns()`
- `findPregnancyExercises()`
- `findPregnancySupplements()`
- `findMilestonesByAge()`
- `findMilestonesByKeywords()`
- `findGrowthByAgeAndGender()`

خروجی این متدها Row خام دیتابیس است و Repository دیگر تصمیم نمی‌گیرد چه نتیجه‌ای مهم‌تر است یا چگونه باید به LLM نمایش داده شود.

#### Updated — `app/Services/KnowledgeRetriever.php`
منطق زیر از Repository به Retriever منتقل شد:

- انتخاب sourceهای RAG
- scoring بر اساس keyword match
- ranking
- محدودکردن نتایج هر منبع
- تبدیل HTML article به متن
- ساخت snippet مرتبط
- ساخت context block نهایی برای `LlmService`
- محدودکردن context کلی به ۱۲ block مانند رفتار قبلی

### Before → After

**Before**

`Repository = SQL + Preprocessing + Ranking + Context Builder`

**After**

`QueryPreprocessor = Query Analysis`

`AiKnowledgeRepository = Database Access Only`

`KnowledgeRetriever = Retrieval + Ranking + Context Building`

### نکته مربوط به Keyword Processing
در همان Scope اولیه، تکمیل Keyword Processing و پشتیبانی بهتر از اعداد فارسی خواسته شده بود. در این Change:

- نرمال‌سازی اعداد فارسی و عربی در یک محل مرکزی انجام می‌شود.
- WORD_NUMBERS از ۱ تا ۲۰ تکمیل شد؛ در نسخه قبلی برخی اعداد بین ۱۲ و ۲۰ وجود نداشتند.
- synonym و stemming از Repository خارج شدند تا بعداً بدون دست‌زدن به SQL قابل توسعه باشند.

### اثر روی دیتابیس
هیچ Schema یا داده‌ای تغییر نکرد.

### اثر روی endpoint و LLM
قرارداد `KnowledgeRetriever::retrieve()` و خروجی `string[]` برای `LlmService` حفظ شد؛ بنابراین endpoint نیازی به تغییر جدید نداشت.

### Validation / Test

1. فایل‌های نهایی از شاخه `main` دوباره خوانده شدند و مسئولیت Repository بررسی شد؛ Repository جدید فقط Query و Row خام دارد.
2. Retriever از متدهای جدید Repository استفاده می‌کند و scoring/context building در Service layer قرار گرفته است.
3. `QueryPreprocessor.php` با `php -l` بررسی شد و نتیجه `No syntax errors detected` بود.
4. Functional test محلی QueryPreprocessor در محیط ابزار متوقف شد، چون PHP نصب‌شده در محیط Validation فاقد extension `mbstring` است و `mb_strlen()` را ندارد.
5. این محدودیت وابستگی جدیدی ایجادشده توسط Change نیست؛ نسخه قبلی پروژه نیز در AI از `mb_*` استفاده می‌کرد. بنابراین Runtime واقعی پروژه باید همچنان PHP `mbstring` داشته باشد.
6. اتصال واقعی به MariaDB و OpenRouter از این محیط انجام نشده، پس نتیجه Runtime end-to-end در این Change ادعا نمی‌شود.

### Rollback
برای Rollback باید سه فایل زیر به وضعیت MCC-CHANGE-002 برگردند:

- `app/Services/QueryPreprocessor.php`
- `app/Repositories/AiKnowledgeRepository.php`
- `app/Services/KnowledgeRetriever.php`

### نتیجه نهایی
در پایان این Change، بخش اصلی فاز ۱ انجام شده است: Database Access مربوط به RAG از preprocessing، ranking و Context Building جدا شده و ساختار خواسته‌شده برای Retriever مستقل وجود دارد.

---

## قواعد ادامه کار

برای ادامه پروژه:

1. Feature بعدی فقط از Roadmap Scope-locked انتخاب می‌شود.
2. قبل از هر تغییر، وضعیت واقعی کد و دیتابیس بررسی می‌شود.
3. تغییرات کوچک و قابل Validation نگه داشته می‌شوند.
4. هر Change با دلیل، Before/After و محدودیت Validation در همین سند ثبت می‌شود.
