<!-- ============================================================
پنجره‌ی ورود/ثبت‌نام با شماره موبایل + کد تایید
فعلاً فقط UI هست؛ اتصال واقعی به کاوه‌نگار توی فاز بعد اضافه می‌شه.
============================================================ -->
<div class="auth-modal-overlay" id="authModalOverlay" hidden>
    <div class="auth-modal" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">

        <button type="button" class="auth-modal-close" id="authModalClose" aria-label="بستن">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
        </button>

        <div class="auth-modal-brand">
            <span class="auth-modal-brand-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
            </span>
        </div>

        <!-- ============================================================
        مرحله‌ی ۱: وارد کردن شماره موبایل
        ============================================================ -->
        <div class="auth-step" id="authStepPhone">
            <h2 class="auth-title" id="authModalTitle">ورود یا ثبت‌نام</h2>
            <p class="auth-subtitle">شماره موبایل‌تون رو وارد کنید تا کد تایید براتون پیامک بشه.</p>

            <form class="auth-form" id="authPhoneForm">
                <label class="auth-field">
                    <span class="auth-field-label">شماره موبایل</span>
                    <div class="auth-phone-input-wrapper">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2" /><path d="M11 18h2" /></svg>
                        <input
                            type="tel"
                            id="authPhoneInput"
                            class="auth-phone-input"
                            placeholder="09121234567"
                            dir="ltr"
                            inputmode="numeric"
                            maxlength="11"
                            required />
                    </div>
                    <span class="auth-field-error" id="authPhoneError" hidden>شماره موبایل رو درست وارد کنید (مثلاً 09121234567)</span>
                </label>

                <button type="submit" class="auth-submit-btn" id="authPhoneSubmit">
                    <span>دریافت کد تایید</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 14 0" /><path d="m13 6 6 6-6 6" /></svg>
                </button>
            </form>

            <p class="auth-terms">با ورود، <a href="#">شرایط استفاده</a> و <a href="#">حریم خصوصی</a> رو می‌پذیرید.</p>
        </div>

        <!-- ============================================================
        مرحله‌ی ۲: وارد کردن کد تایید
        ============================================================ -->
        <div class="auth-step" id="authStepOtp" hidden>
            <button type="button" class="auth-back-btn" id="authBackBtn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7" /><path d="M19 12H5" /></svg>
                تغییر شماره
            </button>

            <h2 class="auth-title">کد تایید رو وارد کنید</h2>
            <p class="auth-subtitle">کد ۵ رقمی ارسال‌شده به شماره <bdi class="auth-phone-display" id="authPhoneDisplay" dir="ltr"></bdi> رو وارد کنید.</p>

            <p class="auth-dev-note" id="authDevNote" hidden></p>

            <form class="auth-form" id="authOtpForm">
                <div class="auth-otp-boxes" id="authOtpBoxes" dir="ltr">
                    <input type="text" inputmode="numeric" maxlength="1" class="auth-otp-box" data-otp-index="0" />
                    <input type="text" inputmode="numeric" maxlength="1" class="auth-otp-box" data-otp-index="1" />
                    <input type="text" inputmode="numeric" maxlength="1" class="auth-otp-box" data-otp-index="2" />
                    <input type="text" inputmode="numeric" maxlength="1" class="auth-otp-box" data-otp-index="3" />
                    <input type="text" inputmode="numeric" maxlength="1" class="auth-otp-box" data-otp-index="4" />
                </div>
                <span class="auth-field-error" id="authOtpError" hidden>کد وارد‌شده صحیح نیست.</span>

                <button type="submit" class="auth-submit-btn" id="authOtpSubmit">
                    <span>تایید و ورود</span>
                </button>
            </form>

            <div class="auth-resend-row">
                <span class="auth-resend-timer" id="authResendTimer">ارسال مجدد کد تا ۰۱:۵۹</span>
                <button type="button" class="auth-resend-btn" id="authResendBtn" hidden>ارسال مجدد کد</button>
            </div>
        </div>

        <!-- ============================================================
        مرحله‌ی ۳: گرفتن اسم (فقط برای کاربر جدید — اولین ورود)
        ============================================================ -->
        <div class="auth-step" id="authStepName" hidden>
            <span class="auth-modal-brand-icon auth-step-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.5 3.5L7 8l3.5 1.5L12 13l1.5-3.5L17 8l-3.5-1.5z" /><path d="M5 17l-.7 1.6L3 19l1.6.7.7 1.6.7-1.6 1.6-.7-1.6-.7z" /></svg>
            </span>
            <h2 class="auth-title">خوش اومدید! اسمتون چیه؟</h2>
            <p class="auth-subtitle">این اولین ورودتونه؛ اسم‌تون رو بگید تا بقیه‌ی سایت رو باهاتون شخصی‌سازی‌شده جلو ببریم.</p>

            <form class="auth-form" id="authNameForm">
                <label class="auth-field">
                    <span class="auth-field-label">نام</span>
                    <div class="auth-phone-input-wrapper">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
                        <input
                            type="text"
                            id="authNameInput"
                            class="auth-phone-input"
                            placeholder="مثلاً سارا محمدی"
                            maxlength="100"
                            style="text-align: right;"
                            required />
                    </div>
                    <span class="auth-field-error" id="authNameError" hidden></span>
                </label>

                <button type="submit" class="auth-submit-btn" id="authNameSubmit">
                    <span>تکمیل ثبت‌نام</span>
                </button>
            </form>
        </div>

        <!-- ============================================================
        مرحله‌ی ۴: پیام موفقیت
        ============================================================ -->
        <div class="auth-step auth-step-success" id="authStepSuccess" hidden>
            <span class="auth-success-icon">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
            </span>
            <h2 class="auth-title" id="authSuccessTitle">خوش اومدید!</h2>
            <p class="auth-subtitle" id="authSuccessSubtitle">ورود شما با موفقیت انجام شد.</p>
        </div>

    </div>
</div>