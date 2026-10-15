# Crawlerهای محتوای Maternal Child Care

این پوشه برای crawler/importهایی است که مستقیماً در درخواست توسعه سامانه لازم هستند.

## CDC Developmental Milestones

Crawler:

`tools/crawlers/cdc_milestones.php`

Importer:

`tools/crawlers/import_cdc_milestones.php`

### چرا CDC؟

CDC یک منبع رسمی سلامت عمومی است و صفحات Developmental Milestones آن برای سنین ۲ ماه تا ۵ سال به‌صورت ساختاریافته منتشر می‌شوند.

Crawler فقط **متن milestoneها** را دریافت می‌کند و تصویر/ویدئو دانلود نمی‌کند؛ چون وضعیت copyright تصاویر می‌تواند با متن متفاوت باشد.

## مرحله ۱ — Crawl و Raw Export

Crawler به‌صورت پیش‌فرض فایل JSON زیر را می‌سازد:

`storage/crawlers/cdc_milestones_YYYYmmdd_His.json`

هر رکورد Raw شامل:

- `source_name`
- `source_url`
- `age_months`
- `source_category`
- `milestone_text_en`

Metadata فایل نیز زمان crawl، source URLها، attribution و non-endorsement notice را نگه می‌دارد.

اجرا:

```bash
php tools/crawlers/cdc_milestones.php
```

یا:

```bash
php tools/crawlers/cdc_milestones.php /tmp/cdc_milestones.json
```

نیازمندی PHP برای Crawler:

- `curl`
- `dom`

## چرا Raw Export مستقیم INSERT نمی‌شود؟

دیتابیس سامانه فارسی است اما منبع CDC انگلیسی است. واردکردن خودکار متن انگلیسی به `development_milestones` باعث مخلوط شدن محتوای خام Source با محتوای محصول می‌شود.

Workflow:

`CDC → Raw JSON → ترجمه/بازبینی → Reviewed JSON → Import → development_milestones`

## مرحله ۲ — Reviewed JSON

قبل از Import، هر milestone باید ترجمه و بازبینی شود و رکورد نهایی حداقل این ساختار را داشته باشد:

```json
{
  "milestone_name_fa": "نام کوتاه مرحله تکامل",
  "description_fa": "توضیح فارسی بازبینی‌شده",
  "category": "motor",
  "milestone_type": "gross_motor",
  "age_months_start": 2,
  "age_months_end": 2,
  "warning_signs_fa": "علامت هشدار در صورت نیاز",
  "source_url": "https://www.cdc.gov/act-early/milestones/2-months.html"
}
```

مقادیر مجاز `category` مطابق Schema واقعی دیتابیس:

- `motor`
- `language`
- `cognitive`
- `social_emotional`

مقادیر مجاز `milestone_type`:

- `gross_motor`
- `fine_motor`
- `speech`
- `social`
- `cognitive`

فایل Reviewed باید یک کلید `records` شامل آرایه رکوردها داشته باشد.

## مرحله ۳ — Validation بدون تغییر DB

```bash
php tools/crawlers/import_cdc_milestones.php reviewed.json --dry-run
```

Dry Run موارد زیر را بررسی می‌کند:

- وجود متن فارسی نام/توضیح
- معتبر بودن category و milestone_type
- معتبر بودن بازه سن
- CDC بودن source URL
- ساختار JSON

در `--dry-run` هیچ Query تغییردهنده‌ای روی DB اجرا نمی‌شود.

## مرحله ۴ — Import واقعی

```bash
php tools/crawlers/import_cdc_milestones.php reviewed.json
```

Importer:

1. قبل از Insert کل فایل را Validate می‌کند.
2. Import را داخل Transaction انجام می‌دهد.
3. رکورد موجود با همان نام، category، بازه سن و milestone type را دوباره Insert نمی‌کند.
4. در صورت خطا Transaction را Rollback می‌کند.
5. Raw English را مستقیماً قبول نمی‌کند؛ فیلدهای فارسی Reviewed الزامی‌اند.

## Attribution

هنگام استفاده از داده CDC باید Source مشخص باشد. Raw Export این متن را همراه خود نگه می‌دارد:

`Source: Centers for Disease Control and Prevention (CDC).`

استفاده از محتوای CDC به معنی endorsement سامانه توسط CDC/HHS نیست.

## نکته درباره تصاویر

Crawler عمداً تصویر و ویدئو ذخیره نمی‌کند. متن بسیاری از صفحات CDC public domain است، اما بعضی assets می‌توانند محدودیت متفاوت داشته باشند؛ بنابراین هر asset تصویری باید جداگانه از نظر وضعیت استفاده بررسی شود.
