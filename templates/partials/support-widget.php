<?php
// ============================================================
// ویجت شناور تماس با پشتیبانی
// این partial کاملاً مستقله: هم HTML، هم CSS، هم JS خودش رو داره.
// کافیه هرجا خواستی، همین یک خط include بشه:
//   include TEMPLATES_PATH . '/partials/support-widget.php';
// ============================================================
$supportSent  = isset($_GET['support_sent']) && $_GET['support_sent'] === '1';
$supportError = isset($_GET['support_error']) && $_GET['support_error'] === '1';
$supportOpen  = $supportSent || $supportError;
?>

<link rel="stylesheet" href="assets/css/components/support-widget.css">

<div class="support-widget <?= $supportOpen ? 'is-open' : '' ?>" id="supportWidget">

    <!-- دکمه‌ی شناور -->
    <button type="button" class="support-fab" id="supportFab" aria-expanded="<?= $supportOpen ? 'true' : 'false' ?>" aria-controls="supportPanel">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.5 0 10-4.5 10-10S17.5 2 12 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5-1.3c1.5.8 3.2 1.3 5 1.3Z" /><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 1.5-2.5 2-2.5 3.5" /><path d="M12 16.5h.01" /></svg>
        <span class="support-fab-label">تماس با پشتیبانی</span>
    </button>

    <!-- پنل -->
    <div class="support-panel" id="supportPanel">

        <div class="support-panel-header">
            <h3>تماس با پشتیبانی</h3>
            <button type="button" class="support-close" id="supportClose" aria-label="بستن">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
            </button>
        </div>

        <?php if ($supportSent): ?>
            <div class="support-success">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="m9 12 2 2 4-4" /></svg>
                <p>پیام شما با موفقیت ثبت شد.<br>در اسرع وقت بررسی و پاسخ داده می‌شه.</p>
            </div>
        <?php else: ?>
            <p class="support-subtitle">باگ دیدید، سوالی دارید یا پیشنهادی؟ همین‌جا بنویسید.</p>

            <?php if ($supportError): ?>
                <p class="support-error">لطفاً حداقل یکی از ایمیل یا شماره تلفن رو معتبر وارد کنید و متن پیام رو خالی نذارید.</p>
            <?php endif; ?>

            <form method="post" action="support-message.php" class="support-form">
                <label for="support-name">نام و نام خانوادگی</label>
                <input type="text" name="full_name" id="support-name" placeholder="اختیاری">

                <label for="support-email">ایمیل</label>
                <input type="email" name="email" id="support-email" placeholder="اختیاری">

                <label for="support-phone">شماره تلفن</label>
                <input type="tel" name="phone_number" id="support-phone" placeholder="اختیاری - ۰۹۱۲۳۴۵۶۷۸۹" inputmode="numeric">

                <p class="support-hint">حداقل یکی از ایمیل یا شماره تلفن رو پر کنید.</p>

                <label for="support-type">نوع پیام</label>
                <select name="message_type" id="support-type">
                    <option value="bug">گزارش باگ</option>
                    <option value="question">سوال</option>
                    <option value="suggestion">پیشنهاد</option>
                </select>

                <label for="support-message">متن پیام *</label>
                <textarea name="message" id="support-message" rows="4" placeholder="توضیح بدید..." required></textarea>

                <button type="submit" class="btn-primary support-submit">ارسال پیام</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/components/support-widget.js"></script>