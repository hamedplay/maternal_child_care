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
`AiKnowledgeRepository` مسئول extraction، stemming، synonym expansion، تشخیص سن/جنسیت، scoring/ranking، تبدیل HTML به متن، snippet، context building و SQL بود.

### هدف
Repository فقط Query اجرا کند و Row خام برگرداند. تمام تحلیل سؤال، ranking و ساخت context به Service layer منتقل شود.

### تصمیم فنی
سه مسئولیت مستقل تعریف شد:

1. `QueryPreprocessor` → تحلیل و نرمال‌سازی Query.
2. `AiKnowledgeRepository` → فقط Data Access.
3. `KnowledgeRetriever` → orchestration، scoring/ranking و Context Building.

### فایل‌های تغییرکرده

#### Updated — `app/Services/QueryPreprocessor.php`
- normalize حروف فارسی/عربی
- تبدیل اعداد فارسی/عربی
- stopword filtering
- stemming سبک
- synonym expansion
- استخراج سن بر حسب ماه
- استخراج جنسیت

`analyze()` خروجی `question`, `keywords`, `age_months`, `gender` تولید می‌کند.

#### Updated — `app/Repositories/AiKnowledgeRepository.php`
preprocessing، scoring و context formatting حذف شد. Repository فقط متدهای Data Access دارد.

#### Updated — `app/Services/KnowledgeRetriever.php`
source selection، scoring/ranking، HTML-to-text، snippet و context building به Retriever منتقل شد.

### Before → After
**Before:** `Repository = SQL + Preprocessing + Ranking + Context Builder`

**After:**
- `QueryPreprocessor = Query Analysis`
- `AiKnowledgeRepository = Database Access Only`
- `KnowledgeRetriever = Retrieval + Ranking + Context Building`

### Keyword Processing
- اعداد فارسی و عربی مرکزی normalize می‌شوند.
- WORD_NUMBERS از ۱ تا ۲۰ تکمیل شد.
- synonym و stemming از Repository خارج شدند.

### Validation / Test
- فایل‌های نهایی از `main` بازخوانی شدند.
- `QueryPreprocessor.php` با `php -l` بدون خطای Syntax بود.
- functional test محلی به دلیل نبود extension `mbstring` در PHP محیط ابزار متوقف شد؛ پروژه قبلاً نیز به `mb_*` وابسته بود.

### نتیجه نهایی
Database Access مربوط به RAG از preprocessing، ranking و Context Building جدا شد.

---

## MCC-CHANGE-004 — شروع فاز ۲: اصلاح چرخه OTP و اتصال صریح Kavenegar SMS

### تاریخ
2026-10-15

### درخواست / مسئله
Login/Register با OTP کاوه‌نگار و verify سمت سرور جزء درخواست اولیه بود.

### وضعیت قبل
زیرساخت اصلی از قبل وجود داشت: `AuthService`، `OtpRepository`، `KavenegarService`، endpointهای send/verify، UI OTP و جدول `otp_verifications`.

چرخه قبلی:

`generate → invalidate old → persist new → send SMS`

اگر Kavenegar خطا می‌داد، کد قبلی باطل و یک OTP ارسال‌نشده در DB فعال می‌ماند.

### Root Cause
Persistence و invalidate قبل از مشخص‌شدن نتیجه Provider انجام می‌شد.

### فایل‌های تغییرکرده

#### `app/Services/AuthService.php`
ترتیب به این شکل تغییر کرد:

`generate → send SMS → invalidate old → persist new`

#### `app/Services/KavenegarService.php`
`type=sms` به Verify Lookup اضافه شد.

### Verification Model
OTP plaintext در DB ذخیره نمی‌شود. `code_hash` با HMAC-SHA256 ذخیره می‌شود و verify با `hash_equals()` انجام می‌شود.

### Configuration
- `KAVENEGAR_API_KEY`
- `KAVENEGAR_OTP_TEMPLATE`

### Validation
- Schema واقعی OTP بررسی شد.
- UI و endpointها بررسی شدند.
- Kavenegar Verify Lookup با مستندات فعلی تطبیق داده شد.
- منطق اصلاح‌شده AuthService با `php -l` بدون خطای Syntax بود.
- تست SMS واقعی به credential محیط اجرا وابسته است.

### نتیجه
Workflow کد آماده است؛ End-to-End SMS بعد از تنظیم credential واقعی باید Validation شود.

---

## MCC-CHANGE-005 — رفع آلودگی JSON در endpoint هوش مصنوعی ناشی از session_start تکراری

### تاریخ
2026-10-15

### درخواست / مسئله
در درخواست اولیه گفته شده بود اولین درخواست AI گاهی/در شروع کار نمی‌کند و باید Root Cause بررسی شود.

### بررسی
`config/config.php` در ابتدای هر Request اگر session فعال نباشد `session_start()` را اجرا می‌کند. با این حال `public/ai-qa-ask.php` بعد از require کردن همین config دوباره `session_start()` اجرا می‌کرد.

در PHP، فراخوانی `session_start()` روی session فعال Notice تولید می‌کند. اگر `display_errors` در محیط روشن باشد، این Notice می‌تواند قبل از JSON در Response چاپ شود. Frontend از `res.json()` استفاده می‌کند؛ در نتیجه Response آلوده به Notice دیگر JSON معتبر نیست و مسیر `.catch()` اجرا می‌شود.

### Root Cause
شروع دوباره session در JSON endpoint در حالی که session قبلاً در bootstrap پروژه شروع شده بود.

### تغییر
در `public/ai-qa-ask.php` فراخوانی اضافی `session_start()` حذف شد. مدیریت session فقط در `config/config.php` باقی ماند.

### Before → After
**Before:** `config.php session_start → ai-qa-ask.php session_start دوباره → احتمال Notice قبل از JSON`

**After:** `config.php session_start → ai-qa-ask.php استفاده مستقیم از session موجود`

### Validation
- رفتار PHP در محیط local با دو بار `session_start()` تست شد و Notice `Ignoring session_start() because a session is already active` بازتولید شد.
- نسخه endpoint از `main` بازخوانی و حذف فراخوانی دوم تأیید شد.
- تست Browser/Server واقعی هنوز انجام نشده، بنابراین ادعا نمی‌شود این تنها علت همه حالت‌های «اولین درخواست» بوده است؛ اما این defect قطعی از مسیر JSON حذف شد.

### نتیجه
یک Root Cause واقعی که می‌توانست Response اولین/درخواست‌های AI را در محیط‌های نمایش‌دهنده Notice خراب کند حذف شد.

---

## MCC-CHANGE-006 — ذخیره سؤال و پاسخ AI در دیتابیس

### تاریخ
2026-10-15

### درخواست / مسئله
در درخواست اولیه خواسته شده بود سؤال‌های AI و تعاملات گفتگو در DB ذخیره شوند تا بعداً برای history و personalization قابل استفاده باشند.

### وضعیت قبل
AI history فقط در `$_SESSION['ai_chat_history']` بود. با پایان session یا تغییر مرورگر، تاریخچه پایدار در DB وجود نداشت. Dump واقعی نیز جدول `ai_conversations` یا `ai_messages` نداشت.

### هدف
ذخیره پایدار گفتگو بدون تغییر دادن Dump تاریخی موجود و بدون مخلوط‌کردن Persistence با LLM/Retrieval logic.

### تغییرات دیتابیس
فایل جدید:

`database/migrations/20261015_001_create_ai_conversations.sql`

دو جدول ایجاد می‌کند:

#### `ai_conversations`
- `id`
- `user_id` nullable
- `session_key` unique
- `created_at`
- `updated_at`

`user_id` به `users.id` متصل است. nullable بودن اجازه می‌دهد کاربر مهمان هم گفتگو داشته باشد.

#### `ai_messages`
- `id`
- `conversation_id`
- `role` (`user` / `assistant`)
- `content`
- `model`
- `created_at`

### فایل جدید
`app/Repositories/AiConversationRepository.php`

مسئول Data Access است:
- `getOrCreateConversation()`
- `addMessage()`

اگر conversation در حالت مهمان ایجاد شده باشد و کاربر بعداً در همان session Login کند، Repository در مراجعه بعدی `user_id` را به همان conversation متصل می‌کند.

### Endpoint
`public/ai-qa-ask.php` اکنون:

1. conversation را با `session_id()` پیدا/ایجاد می‌کند.
2. سؤال کاربر را قبل از ارسال به LLM ذخیره می‌کند.
3. پاسخ assistant را همراه نام model ذخیره می‌کند.
4. history session قبلی را همچنان برای context کوتاه‌مدت حفظ می‌کند.

### رفتار در صورت Migration اعمال‌نشده
Repository خطای DB را log و `null` برمی‌گرداند؛ در نتیجه AI response به‌خاطر نبود جدول جدید قطع نمی‌شود. پس از اعمال migration، persistence فعال می‌شود.

### چرا دو جدول؟
Conversation هویت یک رشته گفتگو را نگه می‌دارد و Message هر turn را. این ساختار برای درخواست صریح history/personalization لازم است و از ذخیره سؤال و پاسخ در یک فیلد تخت جلوگیری می‌کند.

### Validation
1. Schema جدید با MariaDB 10.4 و نوع `users.id` (`int unsigned`) تطبیق داده شد.
2. Foreign Key و Indexهای موردنیاز در migration تعریف شدند.
3. `AiConversationRepository.php` با `php -l` بدون خطای Syntax بود.
4. نسخه endpoint متصل به Repository با `php -l` بدون خطای Syntax بود.
5. Migration روی دیتابیس واقعی اجرا نشده، چون اتصال مستقیم به DB محیط پروژه در این گفتگو موجود نیست؛ بنابراین ایجاد فیزیکی جدول‌ها هنوز باید روی محیط اجرا انجام شود.

### Rollback
در صورت Rollback ابتدا `ai_messages` و سپس `ai_conversations` حذف می‌شوند و endpoint به نسخه بدون Persistence برمی‌گردد.

### نتیجه
کد و migration لازم برای ذخیره پایدار سؤال/پاسخ AI آماده است؛ فعال‌شدن واقعی آن نیازمند اجرای migration روی MariaDB پروژه است.

---

## قواعد ادامه کار

1. Feature بعدی فقط از Roadmap Scope-locked انتخاب می‌شود.
2. قبل از هر تغییر، وضعیت واقعی کد و دیتابیس بررسی می‌شود.
3. تغییرات کوچک و قابل Validation نگه داشته می‌شوند.
4. هر Change با دلیل، Before/After و محدودیت Validation در همین سند ثبت می‌شود.
