<?php
/**
 * ویجت «این هفته از بارداری» در صفحه‌ی اصلی.
 * انتظار می‌ره قبل از include این متغیرها ست شده باشن:
 *   $weekData    : آرایه‌ی برگشتی از PregnancyWeekRepository::findByWeek() یا null
 *   $currentWeek : هفته‌ای که الان نمایش داده می‌شه (int بین ۱ تا ۴۰)
 *
 * کاربر می‌تونه بدون لاگین، هر هفته‌ای رو از فیلتر انتخاب و مرور کنه (اعتمادسازی/SEO).
 * چون این حالت "دستی" و شخصی‌سازی‌نشده‌ست، یک CTA برای ثبت‌نام نشون می‌دیم که
 * توضیح می‌ده با عضویت، این هفته خودکار و متناسب با بارداری خودشون نمایش داده می‌شه.
 *
 * TODO: وقتی سیستم لاگین/پروفایل بارداری (pregnancy_due_date) پیاده شد:
 *   - اگه کاربر لاگین بود و due_date داشت، $currentWeek باید پیش‌فرض از روی
 *     تاریخ واقعی بارداریش محاسبه بشه (نه همیشه ۲۰)
 *   - در اون حالت، بخش week-marketing-cta پایین دیگه نمایش داده نشه
 */
?>
<section class="pregnancy-week-section" id="pregnancy-week-section">
    <div class="section-header">
        <div>
            <h2>این هفته از بارداری</h2>
            <p class="section-subtitle">مروری کوتاه از تغییرات هفته‌ی جاری</p>
        </div>
    </div>

    <?php if ($weekData): ?>
        <div class="week-widget">
            <div class="week-badge">
                <span class="week-badge-number"><?= (int) $weekData['week_number'] ?></span>
                <span class="week-badge-label">هفته</span>
            </div>

            <div class="week-info">
                <?php if (!empty($weekData['fetus_size_comparison'])): ?>
                    <p class="week-size">
                        اندازه‌ی جنین تقریباً <?= htmlspecialchars($weekData['fetus_size_comparison'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($weekData['development_description'])): ?>
                    <p class="week-description">
                        <?= htmlspecialchars(mb_substr($weekData['development_description'], 0, 150), ENT_QUOTES, 'UTF-8') ?>…
                    </p>
                <?php endif; ?>

                <div class="week-meta">
                    <?php if (!empty($weekData['fetus_length_cm'])): ?>
                        <span>قد تقریبی: <?= htmlspecialchars((string) $weekData['fetus_length_cm'], ENT_QUOTES, 'UTF-8') ?> سانتی‌متر</span>
                    <?php endif; ?>
                    <?php if (!empty($weekData['fetus_weight_g'])): ?>
                        <span>وزن تقریبی: <?= htmlspecialchars((string) $weekData['fetus_weight_g'], ENT_QUOTES, 'UTF-8') ?> گرم</span>
                    <?php endif; ?>
                </div>

                <div class="week-actions">
                    <a href="pregnancy-weeks.php?week=<?= (int) $weekData['week_number'] ?>" class="btn-primary week-cta">
                        مشاهده کامل هفته
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
                    </a>

                    <!-- فیلتر انتخاب هفته - بدون لاگین قابل استفاده‌ست -->
                    <form method="get" action="index.php#pregnancy-week-section" class="week-selector-form">
                        <label for="week-select" class="week-selector-label">مشاهده‌ی هفته‌ی دیگر:</label>
                        <select name="week" id="week-select" class="week-selector-select">
                            <?php for ($weekOption = 1; $weekOption <= 40; $weekOption++): ?>
                                <option value="<?= $weekOption ?>" <?= $weekOption === $currentWeek ? 'selected' : '' ?>>
                                    هفته <?= $weekOption ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="week-selector-submit">نمایش</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- CTA بازاریابی: تشویق به ثبت‌نام برای شخصی‌سازی خودکار -->
        <div class="week-marketing-cta">
            <p>
                الان داری هفته‌ای رو که خودت انتخاب کردی می‌بینی.
                <strong>ثبت‌نام کن</strong> تا هر بار خودکار هفته‌ی واقعی بارداریت رو برات نشون بدیم
                و یادآوری‌ها، مکمل‌ها و ورزش‌های پیشنهادی هم متناسب با شرایط خودت شخصی‌سازی بشن.
            </p>
            <a href="register.php" class="btn-primary week-marketing-cta-btn">ثبت‌نام رایگان</a>
        </div>
    <?php else: ?>
        <div class="week-widget week-widget-empty">
            <p>اطلاعات این هفته موقتاً در دسترس نیست؛ لطفاً بعداً دوباره سر بزنید.</p>
        </div>
    <?php endif; ?>
</section>