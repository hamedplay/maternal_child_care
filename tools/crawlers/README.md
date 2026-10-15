# Crawlerهای محتوای Maternal Child Care

این پوشه برای crawler/importهایی است که مستقیماً در درخواست توسعه سامانه لازم هستند.

## CDC Developmental Milestones

فایل:

`tools/crawlers/cdc_milestones.php`

### چرا CDC؟

CDC یک منبع رسمی سلامت عمومی است و صفحات Developmental Milestones آن برای سنین ۲ ماه تا ۵ سال به‌صورت ساختاریافته منتشر می‌شوند.

این crawler فقط **متن milestoneها** را دریافت می‌کند و تصویر/ویدئو دانلود نمی‌کند؛ چون وضعیت copyright تصاویر می‌تواند با متن متفاوت باشد.

### خروجی

Crawler به‌صورت پیش‌فرض فایل JSON زیر را می‌سازد:

`storage/crawlers/cdc_milestones_YYYYmmdd_His.json`

هر رکورد شامل این داده‌ها است:

- `source_name`
- `source_url`
- `age_months`
- `source_category`
- `milestone_text_en`

Metadata فایل نیز زمان crawl، همه source URLها، attribution و non-endorsement notice را نگه می‌دارد.

### اجرا

```bash
php tools/crawlers/cdc_milestones.php
```

یا با مسیر خروجی مشخص:

```bash
php tools/crawlers/cdc_milestones.php /tmp/cdc_milestones.json
```

نیازمندی PHP:

- `curl`
- `dom`

### چرا مستقیم INSERT نمی‌کند؟

دیتابیس سامانه فارسی است اما منبع CDC انگلیسی است. واردکردن خودکار متن انگلیسی به `development_milestones` باعث مخلوط شدن محتوای خام source با محتوای محصول می‌شود.

Workflow این فاز عمداً دو مرحله‌ای است:

`CDC → crawler export JSON → ترجمه/بازبینی → import به دیتابیس`

این روش چند مزیت دارد:

1. مشخص است هر داده دقیقاً از کدام URL آمده است.
2. Raw source از متن فارسی ویرایش‌شده جدا می‌ماند.
3. اگر CDC صفحه‌ای را تغییر دهد، export جدید را می‌توان با قبلی مقایسه کرد.
4. Import ناقص یا ترجمه‌ی خودکار بدون بازبینی، دیتابیس اصلی را خراب نمی‌کند.

### Attribution

هنگام استفاده از داده CDC باید Source مشخص باشد. Export این متن را همراه خود نگه می‌دارد:

`Source: Centers for Disease Control and Prevention (CDC).`

استفاده از محتوای CDC به معنی endorsement سامانه توسط CDC/HHS نیست.

### نکته مهم درباره تصاویر

Crawler عمداً تصویر و ویدئو ذخیره نمی‌کند. متن بسیاری از صفحات CDC public domain است، اما بعضی assets می‌توانند محدودیت متفاوت داشته باشند؛ بنابراین هر asset تصویری باید جداگانه از نظر وضعیت استفاده بررسی شود.
