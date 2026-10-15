# مستند تغییرات سامانه Maternal Child Care

این سند مرجع اصلی ثبت تغییرات پروژه است. هدف فقط نگهداری یک فهرست کوتاه از Commitها نیست؛ هر Change باید توضیح دهد چرا تغییر لازم بوده، قبل از آن سیستم چگونه کار می‌کرده، چه چیزی به چه چیزی تبدیل شده، چگونه اجرا شده و چه مقدار از آن واقعاً Validation شده است.

> مسیر کار: Inspect → Requirement / Root Cause → Minimal Design → Implement → Validate → Document

## قواعد ثابت

1. فقط درخواست اولیه کاربر اجرا می‌شود و Feature خارج از Scope اضافه نمی‌شود.
2. Repository باید تا جای ممکن Data Access باشد و Business/Retrieval Logic در Service بماند؛ این تفکیک فقط در جاهایی انجام می‌شود که خود درخواست اولیه نیاز داشته است.
3. تغییر دیتابیس فقط با فایل Migration جدید انجام می‌شود؛ Dump تاریخی ویرایش نمی‌شود.
4. نتیجه تست واقعی از نتیجه مورد انتظار جدا نوشته می‌شود.
5. اگر تست End-to-End به credential یا محیط واقعی وابسته باشد، محدودیت صریحاً ثبت می‌شود.

---

# گزارش تغییرات

## MCC-CHANGE-000 — ایجاد سیستم مستندسازی تغییرات

### تاریخ
2026-10-15

### مسئله
پیش از شروع توسعه، مرجعی برای اینکه بعداً مشخص باشد «چه چیزی چرا تغییر کرده» وجود نداشت.

### تغییر
`docs/CHANGE_LOG.md` ایجاد شد.

### Before → After

**Before:** فقط README برای معرفی پروژه وجود داشت.

**After:** README برای معرفی پروژه باقی ماند و CHANGE_LOG به مرجع آموزشی تغییرات فنی تبدیل شد.

### Validation
ایجاد فایل روی `main` و قابلیت خواندن آن بررسی شد.

---

## MCC-CHANGE-001 — اصلاح Roadmap و قفل کردن Scope روی درخواست اولیه

### تاریخ
2026-10-15

### مسئله
نسخه اولیه Roadmap چند فعالیت عمومی مثل Audit/Hardening مستقل داشت که الزام مستقیم درخواست کاربر نبود.

### تغییر
`docs/IMPLEMENTATION_ROADMAP.md` فقط حول نیازهای اصلی بازنویسی شد:

- AI/RAG و جداسازی Data Access
- OTP/Kavenegar
- محتوای نوزاد/کودک و Crawler
- حذف تبلیغات گهواره
- Profile/Personalization/Logging
- Doctor Contact و Reminder بله
- Admin Panel

### نتیجه
Roadmap دیگر مجوز انجام کار جانبی خارج از درخواست کاربر نیست.

---

## MCC-CHANGE-002 — ایجاد مرز QueryPreprocessor و KnowledgeRetriever

### تاریخ
2026-10-15

### مسئله
مسیر AI مستقیماً از endpoint وارد `AiKnowledgeRepository` می‌شد و Repository علاوه بر SQL، preprocessing و retrieval logic هم انجام می‌داد.

### فایل‌ها

- Added: `app/Services/QueryPreprocessor.php`
- Added: `app/Services/KnowledgeRetriever.php`
- Updated: `public/ai-qa-ask.php`

### Before → After

**Before**

`Endpoint → AiKnowledgeRepository → LlmService`

**After**

`Endpoint → KnowledgeRetriever → QueryPreprocessor + AiKnowledgeRepository → LlmService`

### پیاده‌سازی

`QueryPreprocessor` در اولین گام این موارد را یکدست کرد:

- `ي / ى → ی`
- `ك → ک`
- اعداد فارسی/عربی → لاتین
- نیم‌فاصله → فاصله
- whitespace تکراری → یک فاصله

### نتیجه
endpoint دیگر مستقیم به Repository وابسته نیست و مرز Retriever مورد درخواست ایجاد شد.

---

## MCC-CHANGE-003 — تکمیل جداسازی AI Logic از Data Access

### تاریخ
2026-10-15

### مسئله
بعد از Change قبلی هنوز keyword extraction، stemming، synonym، تشخیص سن/جنسیت، scoring، snippet و Context Building داخل Repository بودند.

### تصمیم
سه مسئولیت مستقل شد:

- `QueryPreprocessor` → تحلیل Query
- `AiKnowledgeRepository` → فقط Query و Row خام
- `KnowledgeRetriever` → انتخاب منابع، scoring/ranking و ساخت Context

### QueryPreprocessor
موارد زیر از Repository خارج شدند:

- STOPWORDS
- light stemming
- synonym expansion
- تشخیص سن بر حسب ماه
- تشخیص جنسیت
- نرمال‌سازی اعداد فارسی/عربی

WORD_NUMBERS از ۱ تا ۲۰ تکمیل شد.

### AiKnowledgeRepository
متدهای Data Access ایجاد شدند، از جمله:

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

Repository دیگر Context Block تولید نمی‌کند.

### KnowledgeRetriever
این لایه اکنون مسئول است:

- source selection
- scoring/ranking
- محدود کردن نتایج هر source
- HTML → plain text برای article retrieval
- انتخاب snippet مرتبط
- ساخت Context Block نهایی
- سقف کلی ۱۲ block

### Validation
ساختار نهایی روی `main` بازخوانی شد. Syntax `QueryPreprocessor` با `php -l` بدون خطا بود. تست runtime کامل در محیط Validation به دلیل نبود extension `mbstring` ممکن نبود؛ این وابستگی از قبل در پروژه وجود داشته است.

---

## MCC-CHANGE-004 — اصلاح Workflow OTP و اتصال صریح Kavenegar SMS

### تاریخ
2026-10-15

### وضعیت واقعی قبل از تغییر
پروژه از قبل این اجزا را داشت:

- `AuthService`
- `OtpRepository`
- `KavenegarService`
- `auth-send-otp.php`
- `auth-verify-otp.php`
- UI کامل OTP در `auth-modal.js`
- جدول `otp_verifications`

پس OTP از صفر بازنویسی نشد.

### Root Cause پیدا شده
ترتیب قبلی `requestOtp()` چنین بود:

`generate → invalidate previous → persist new OTP → send SMS`

اگر Kavenegar شکست می‌خورد:

1. OTP قبلی از قبل باطل شده بود.
2. OTP جدیدی که هرگز به کاربر نرسیده بود در DB باقی می‌ماند.
3. همان رکورد می‌توانست cooldown/rate limit را فعال کند.

### اصلاح
ترتیب جدید:

`generate → send SMS → invalidate previous → persist new OTP`

فقط OTPای که send آن موفق بوده وارد چرخه Verify می‌شود.

### Kavenegar
در `KavenegarService` پارامتر `type=sms` به Verify Lookup اضافه شد تا channel به‌صورت صریح SMS باشد.

### Server-side verification
OTP خام در DB ذخیره نمی‌شود. `code_hash` با HMAC-SHA256 نگهداری و با `hash_equals()` مقایسه می‌شود. این همان نیاز «سمت سرور باید بتواند کد را بررسی کند» را بدون نگهداری plaintext برآورده می‌کند.

### Configuration

- `KAVENEGAR_API_KEY`
- `KAVENEGAR_OTP_TEMPLATE`
- `OTP_PEPPER`

### Validation
Schema واقعی `otp_verifications`، endpointها و UI بررسی شدند. تست SMS واقعی به API Key و Template واقعی محیط اجرا وابسته است و در این مرحله ادعا نشده است.

---

## MCC-CHANGE-005 — حذف session_start تکراری از AI JSON endpoint

### تاریخ
2026-10-15

### مسئله
کاربر گزارش کرده بود اولین درخواست AI کار نمی‌کند.

### Root Cause واقعی پیدا شده
`config/config.php` از قبل Session را start می‌کند. با این حال `public/ai-qa-ask.php` دوباره `session_start()` اجرا می‌کرد.

در محیطی که `display_errors` روشن باشد، PHP می‌تواند Notice مربوط به session فعال را قبل از JSON چاپ کند. Frontend با `res.json()` پاسخ را parse می‌کند؛ بنابراین متن اضافی قبل از JSON می‌تواند درخواست را ظاهراً fail کند.

### تغییر
`session_start()` اضافی از `ai-qa-ask.php` حذف شد و Bootstrap مرکزی تنها محل start session باقی ماند.

### Validation
رفتار duplicate `session_start()` در PHP بازتولید شد و Notice مربوط به session فعال مشاهده شد. Endpoint نهایی از `main` بازخوانی شد.

### محدودیت
بدون اجرای Browser/Server واقعی ادعا نمی‌شود این تنها علت تمام موارد «اولین درخواست» بوده است؛ اما یک defect قطعی از مسیر JSON حذف شد.

---

## MCC-CHANGE-006 — ذخیره پایدار AI Conversation و Message در دیتابیس

### تاریخ
2026-10-15

### مسئله
قبل از این Change، history فقط در `$_SESSION['ai_chat_history']` بود و هیچ جدول persistent برای سؤال/پاسخ AI در Dump واقعی وجود نداشت.

### Migration جدید

`database/migrations/20261015_001_create_ai_conversations.sql`

### جدول `ai_conversations`

- `id`
- `user_id` nullable
- `session_key` unique
- `created_at`
- `updated_at`

`user_id` به `users.id` وصل است. nullable بودن باعث می‌شود مهمان نیز conversation داشته باشد.

### جدول `ai_messages`

- `id`
- `conversation_id`
- `role` = user / assistant
- `content`
- `model`
- `created_at`

### Repository جدید

`app/Repositories/AiConversationRepository.php`

متدها:

- `getOrCreateConversation()`
- `addMessage()`

اگر conversation در حالت guest ساخته شده و همان session بعداً Login شود، `user_id` به conversation موجود متصل می‌شود.

### Endpoint
`public/ai-qa-ask.php` اکنون سؤال user و پاسخ assistant را در DB ذخیره می‌کند و history session را نیز برای Context کوتاه‌مدت حفظ می‌کند.

### رفتار Fail-safe
اگر Migration هنوز روی محیطی اجرا نشده باشد، خطای DB log می‌شود ولی پاسخ AI متوقف نمی‌شود.

### Validation
Schema migration با MariaDB 10.4 و نوع `users.id` تطبیق داده شد. Syntax Repository و endpoint بررسی شد. Migration روی دیتابیس واقعی پروژه هنوز از این محیط اجرا نشده است.

---

## MCC-CHANGE-007 — تکمیل Child RAG، Domain Detection و هماهنگ‌سازی LLM Prompt

### تاریخ
2026-10-15

### درخواست / مسئله
در درخواست اولیه علاوه بر Pregnancy، بخش کودک/نوزاد و اتصال آن به RAG خواسته شده بود. Dump واقعی نشان داد چند جدول کودک وجود دارند اما Retriever فقط `development_milestones` و `growth_standards` را استفاده می‌کرد.

### منابع واقعی اضافه‌شده به RAG

چهار جدول موجود دیتابیس به Retrieval اضافه شدند:

1. `child_nutrition`
2. `child_supplements`
3. `common_conditions`
4. `vaccination_schedule`

این جداول قبلاً داده داشتند ولی AI از آن‌ها استفاده نمی‌کرد.

### تغییر در Repository
`AiKnowledgeRepository` فقط Data Access جدید اضافه کرد:

- `findChildNutritionByAge()`
- `findChildNutritionByKeywords()`
- `findChildSupplementsByAge()`
- `findChildSupplementsByKeywords()`
- `findCommonConditions()`
- `findVaccinationsByAge()`
- `findVaccinationsByKeywords()`

تفکیک Phase 1 حفظ شد؛ formatting و ranking وارد Repository نشد.

### Age-aware Retrieval
اگر سؤال سن کودک را مشخص کند، برای nutrition/supplement/vaccination/milestone ابتدا Query سن‌محور اجرا می‌شود. اگر سن وجود نداشته باشد، keyword search fallback است.

### اصلاح Native PDO Placeholder
`Database.php` مقدار زیر را دارد:

`PDO::ATTR_EMULATE_PREPARES => false`

در native prepared statements نباید یک named placeholder چند بار reuse شود. چند Query سن‌محور مثل این الگو داشتند:

`age_start <= :age AND age_end >= :age`

این ساختار به placeholderهای مستقل تبدیل شد، مانند:

`age_start <= :age_start AND age_end >= :age_end`

همین اصلاح برای nutrition، child supplements، vaccination و milestones اعمال شد.

### Domain Detection
بعد از اضافه‌شدن منابع کودک، سقف ۱۲ Context Block می‌توانست باعث شود منابع کودک برای سؤال بارداری بی‌جهت جای منابع مرتبط را بگیرند.

به `QueryPreprocessor::analyze()` فیلد `domain` اضافه شد:

- `pregnancy`
- `child`
- `general`

Markerهای ساده و قابل توضیح برای بارداری و کودک استفاده می‌شوند؛ Vector/Classifer جدیدی اضافه نشد.

### رفع ابهام سن
عبارتی مثل «سه ماهه بارداری» قبلاً ممکن بود توسط age parser به «کودک ۳ ماهه» تعبیر شود. اکنون اگر Domain سؤال pregnancy باشد، `age_months` کودک تولید نمی‌شود.

### Keyword/Synonym تکمیلی
در حد نیاز همین Domain، synonymهای زیر اضافه/تکمیل شدند:

- نوزاد ↔ شیرخوار
- کودک ↔ بچه
- واکسن ↔ واکسیناسیون
- چند spelling رایج برای علائم موجود

### Prioritization منابع

- سؤال `child` → منابع کودک، سپس FAQ/Article
- سؤال `pregnancy` → منابع بارداری، سپس FAQ/Article
- سؤال `general` → FAQ/Article، سپس child و pregnancy

سقف ۱۲ block حفظ شد.

### LLM Prompt
`LlmService` قبلاً خود را عمدتاً دستیار بارداری معرفی می‌کرد. Prompt با منابع واقعی RAG هماهنگ شد تا حوزه‌های زیر را نیز پوشش دهد:

- نوزاد و کودک
- رشد و تکامل
- تغذیه کودک
- واکسیناسیون
- مکمل‌ها
- بیماری‌های شایع کودک

همچنین به مدل گفته شد منابع pregnancy و child را با هم مخلوط نکند و Context نامرتبط را وارد پاسخ نکند.

### فایل‌های تغییرکرده

- `app/Repositories/AiKnowledgeRepository.php`
- `app/Services/KnowledgeRetriever.php`
- `app/Services/QueryPreprocessor.php`
- `app/Services/LlmService.php`

### دیتابیس
در این Change هیچ Schema جدیدی ساخته نشد؛ فقط از جدول‌هایی که از قبل در Dump واقعی وجود داشتند استفاده شد.

### Validation

- ستون‌های چهار جدول از Dump واقعی بررسی شدند و Queryها دقیقاً با همان Schema نوشته شدند.
- native PDO placeholderهای تکراری شناسایی و به نام‌های مستقل تبدیل شدند.
- مرز Repository/Service دوباره بررسی شد و Repository همچنان Data Access-only باقی مانده است.
- تست End-to-End Query علیه MariaDB واقعی از این محیط قابل اجرا نیست؛ این محدودیت ثبت می‌شود و موفقیت runtime DB در این Change ادعا نمی‌شود.

### نتیجه نهایی
RAG اکنون علاوه بر Pregnancy به داده‌های واقعی کودک/نوزاد موجود در دیتابیس دسترسی دارد، Queryها بر اساس Domain اولویت‌بندی می‌شوند و Prompt مدل نیز با همین دامنه هماهنگ است.

---

## MCC-CHANGE-008 — شروع فاز محتوا: انتخاب CDC و ایجاد Crawler واقعی Developmental Milestones

### تاریخ
2026-10-15

### درخواست / مسئله
در درخواست اولیه لازم بود بخش نوزاد/کودک با استفاده از یک منبع معتبر تکمیل شود و داده منبع واقعاً از طریق crawler دریافت شود. همچنین crawler code/export باید در پروژه وجود داشته باشد.

### وضعیت قبل
پروژه داده‌های کودک در DB داشت اما Crawler قابل اجرا برای منبع معتبر و Export قابل ردیابی داخل Repository وجود نداشت. صفحات Pregnancy در `public/` وجود دارند، اما Child section هم‌سطح آن هنوز کامل نشده است.

### انتخاب منبع
منبع اولیه برای Developmental Milestones:

`Centers for Disease Control and Prevention (CDC) — Learn the Signs. Act Early.`

دلیل انتخاب:

1. منبع رسمی سلامت عمومی است.
2. milestoneهای کودک را به تفکیک سن از ۲ ماه تا ۵ سال منتشر می‌کند.
3. صفحات source تاریخ update/review دارند.
4. robots.txt مسیر `/act-early/milestones/` را منع نکرده است.
5. CDC اعلام می‌کند بیشتر محتوای متنی سایت public domain است، ولی Attribution و Non-endorsement باید رعایت شوند.

### محدودیت استفاده از Assets
Crawler فقط متن milestone را می‌گیرد. تصویر و ویدئو عمداً دانلود نمی‌شوند؛ زیرا CDC تصریح می‌کند بعضی تصاویر/assetها ممکن است copyright مستقل داشته باشند.

### فایل جدید

`tools/crawlers/cdc_milestones.php`

### سن‌های Crawl

- 2 ماه
- 4 ماه
- 6 ماه
- 9 ماه
- 12 ماه
- 15 ماه
- 18 ماه
- 24 ماه
- 30 ماه
- 36 ماه
- 48 ماه
- 60 ماه

### دسته‌بندی‌های Source
Crawler headingهای CDC را به دسته خام تبدیل می‌کند:

- `social_emotional`
- `language_communication`
- `cognitive`
- `movement_physical`

در این مرحله این categoryها مستقیم به enum دیتابیس تبدیل نمی‌شوند، چون mapping نهایی به `milestone_type` نیازمند بازبینی محتوایی است؛ برای مثال یک milestone حرکتی ممکن است fine motor یا gross motor باشد.

### Export JSON
به‌صورت پیش‌فرض فایل زیر ایجاد می‌شود:

`storage/crawlers/cdc_milestones_YYYYmmdd_His.json`

Metadata Export شامل:

- source name
- source pages
- crawled_at
- attribution
- non-endorsement notice
- records_count

هر record شامل:

- `source_name`
- `source_url`
- `age_months`
- `source_category`
- `milestone_text_en`

### چرا Crawler مستقیم INSERT نمی‌کند؟
CDC انگلیسی است و دیتابیس/رابط پروژه فارسی است. اگر Crawler مستقیماً INSERT کند، Raw Source انگلیسی وارد داده محصول می‌شود.

مسیر صحیح این فاز:

`CDC → Raw Crawler Export → ترجمه/بازبینی → Import → development_milestones`

این جداسازی برای خود Feature crawler لازم است و یک معماری اضافه خارج از Scope نیست؛ چون باید provenance منبع و داده نهایی فارسی از هم قابل تشخیص باشند.

### مستند اجرا
فایل زیر اضافه شد:

`tools/crawlers/README.md`

شامل:

- دلیل انتخاب CDC
- نحوه اجرا
- PHP extensionهای لازم (`curl`, `dom`)
- فرمت Export
- Attribution
- توضیح اینکه تصویر/ویدئو Crawl نمی‌شوند

### رفتار Crawler

- فقط از CLI اجرا می‌شود.
- HTTP status و cURL error را بررسی می‌کند.
- بین درخواست‌ها delay قرار می‌دهد.
- milestoneها را از H3های دسته‌بندی و List Itemها استخراج می‌کند.
- noiseهای navigation/download حذف می‌شوند.
- رکوردهای تکراری در هر Export deduplicate می‌شوند.

### Validation

- صفحات فعلی CDC Milestones برای ۲ ماه تا ۵ سال بررسی شدند و ساختار سنی منبع تأیید شد.
- صفحه نمونه ۲ ماه دسته‌های Social/Emotional، Language/Communication، Cognitive و Movement/Physical را نمایش می‌دهد و طراحی parser بر همان ساختار واقعی انجام شد.
- robots.txt فعلی CDC بررسی شد و مسیر source در Disallowهای عمومی نیست.
- سیاست رسمی CDC برای reuse بررسی شد؛ Attribution و non-endorsement در Export/README ثبت شده است.
- Crawler از داخل این محیط روی شبکه واقعی اجرا نشده است؛ بنابراین فایل Export واقعی هنوز تولید و commit نشده و موفقیت runtime crawl ادعا نمی‌شود.

### Rollback
حذف دو فایل زیر تمام اثر runtime این Change را برمی‌گرداند؛ هیچ DB schema یا داده اصلی در این Change تغییر نکرده است:

- `tools/crawlers/cdc_milestones.php`
- `tools/crawlers/README.md`

### نتیجه نهایی
پروژه اکنون Crawler مستقل و قابل اجرا برای یک منبع پزشکی معتبر دارد و خروجی Raw آن provenance کامل برای مرحله ترجمه/بازبینی و Import آینده نگه می‌دارد.

---

## وضعیت ادامه کار

بعد از MCC-CHANGE-008:

- Phase 1 انجام شده است.
- Phase 2 از نظر کد آماده و تست واقعی Kavenegar وابسته به credential محیط اجرا است.
- Phase 3 بخش اصلی AI/RAG را پیاده‌سازی کرده و Migration باید روی DB واقعی اجرا شود.
- Phase 4 شروع شده و Crawler CDC موجود است؛ مرحله بعد Import محتوای بازبینی‌شده و ساخت Child/Infant UI مشابه Pregnancy است.
