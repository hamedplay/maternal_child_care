document.addEventListener('DOMContentLoaded', function () {
    initAuthModal();
    initProfileMenu();
});

/**
 * درخواست POST با بدنه‌ی JSON؛ همیشه {ok, data} برمی‌گردونه
 * (حتی برای پاسخ‌های خطا) تا بشه پیام سرور رو نشون داد.
 */
function postJson(url, payload) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).then(function (res) {
        return res.json().then(function (data) {
            return { ok: res.ok, data: data };
        });
    });
}

function initAuthModal() {
    var overlay  = document.getElementById('authModalOverlay');
    var trigger  = document.getElementById('authModalTrigger');
    var closeBtn = document.getElementById('authModalClose');

    // اگه trigger نباشه یعنی کاربر لاگین کرده و اصلاً نیازی به این ماژول نیست
    if (!overlay || !trigger) {
        return;
    }

    var stepPhone   = document.getElementById('authStepPhone');
    var stepOtp     = document.getElementById('authStepOtp');
    var stepName    = document.getElementById('authStepName');
    var stepSuccess = document.getElementById('authStepSuccess');

    var successTitle    = document.getElementById('authSuccessTitle');
    var successSubtitle = document.getElementById('authSuccessSubtitle');

    var phoneForm      = document.getElementById('authPhoneForm');
    var phoneInput     = document.getElementById('authPhoneInput');
    var phoneError     = document.getElementById('authPhoneError');
    var phoneSubmitBtn = document.getElementById('authPhoneSubmit');

    var backBtn      = document.getElementById('authBackBtn');
    var phoneDisplay = document.getElementById('authPhoneDisplay');
    var devNote      = document.getElementById('authDevNote');

    var otpForm         = document.getElementById('authOtpForm');
    var otpBoxesWrapper = document.getElementById('authOtpBoxes');
    var otpBoxes        = otpBoxesWrapper ? Array.prototype.slice.call(otpBoxesWrapper.querySelectorAll('.auth-otp-box')) : [];
    var otpError        = document.getElementById('authOtpError');
    var otpSubmitBtn    = document.getElementById('authOtpSubmit');

    var nameForm      = document.getElementById('authNameForm');
    var nameInput     = document.getElementById('authNameInput');
    var nameError     = document.getElementById('authNameError');
    var nameSubmitBtn = document.getElementById('authNameSubmit');

    var resendTimerEl = document.getElementById('authResendTimer');
    var resendBtn     = document.getElementById('authResendBtn');

    var PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    var RESEND_SECONDS = 119; // ۰۱:۵۹ — باید با AuthService::RESEND_COOLDOWN_SECONDS سمت سرور هماهنگ بمونه
    var countdownInterval = null;
    var currentPhone = '';

    function toPersianDigits(str) {
        return String(str).replace(/[0-9]/g, function (d) {
            return PERSIAN_DIGITS[Number(d)];
        });
    }

    function showStep(step) {
        [stepPhone, stepOtp, stepName, stepSuccess].forEach(function (el) {
            if (el) el.hidden = (el !== step);
        });
    }

    function showError(el, message) {
        el.textContent = message;
        el.hidden = false;
    }

    function openModal() {
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        showStep(stepPhone);
        setTimeout(function () { phoneInput.focus(); }, 50);
    }

    function closeModal() {
        overlay.hidden = true;
        document.body.style.overflow = '';
        resetModal();
    }

    function showDevNoteIfPresent(data) {
        if (data.debug_code) {
            devNote.textContent = '🔧 حالت تست (بدون اتصال به کاوه‌نگار) — کد: ' + toPersianDigits(data.debug_code);
            devNote.hidden = false;
        } else {
            devNote.hidden = true;
        }
    }

    function resetModal() {
        stopCountdown();
        phoneForm.reset();
        otpForm.reset();
        nameForm.reset();
        otpBoxes.forEach(function (box) { box.value = ''; });
        phoneError.hidden = true;
        otpError.hidden = true;
        nameError.hidden = true;
        devNote.hidden = true;
        otpBoxesWrapper.classList.remove('auth-otp-error');
        phoneSubmitBtn.disabled = false;
        otpSubmitBtn.disabled = false;
        nameSubmitBtn.disabled = false;
        currentPhone = '';
        showStep(stepPhone);
    }

    trigger.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
    });

    closeBtn.addEventListener('click', closeModal);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !overlay.hidden) {
            closeModal();
        }
    });

    // ============================================================
    // مرحله‌ی ۱: شماره موبایل → درخواست ارسال کد
    // ============================================================
    function isValidIranianMobile(value) {
        return /^09\d{9}$/.test(value);
    }

    function requestOtpCode(phone) {
        return postJson('auth-send-otp.php', { phone: phone });
    }

    phoneForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var value = phoneInput.value.trim();

        if (!isValidIranianMobile(value)) {
            showError(phoneError, 'شماره موبایل رو درست وارد کنید (مثلاً 09121234567)');
            phoneInput.focus();
            return;
        }

        phoneError.hidden = true;
        phoneSubmitBtn.disabled = true;

        requestOtpCode(value)
            .then(function (result) {
                if (!result.ok) {
                    showError(phoneError, result.data.error || 'ارسال کد با خطا مواجه شد.');
                    return;
                }

                currentPhone = value;
                phoneDisplay.textContent = toPersianDigits(value);
                showDevNoteIfPresent(result.data);
                showStep(stepOtp);
                startCountdown();
                setTimeout(function () { otpBoxes[0].focus(); }, 50);
            })
            .catch(function () {
                showError(phoneError, 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.');
            })
            .finally(function () {
                phoneSubmitBtn.disabled = false;
            });
    });

    backBtn.addEventListener('click', function () {
        stopCountdown();
        showStep(stepPhone);
        setTimeout(function () { phoneInput.focus(); }, 50);
    });

    // ============================================================
    // مرحله‌ی ۲: کد تایید — جعبه‌های خودکار
    // ============================================================
    otpBoxes.forEach(function (box, index) {
        box.addEventListener('input', function () {
            box.value = box.value.replace(/[^0-9]/g, '').slice(0, 1);
            otpBoxesWrapper.classList.remove('auth-otp-error');
            otpError.hidden = true;

            if (box.value && index < otpBoxes.length - 1) {
                otpBoxes[index + 1].focus();
            }
        });

        box.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !box.value && index > 0) {
                otpBoxes[index - 1].focus();
            }
        });

        box.addEventListener('paste', function (e) {
            var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
            if (!pasted) {
                return;
            }
            e.preventDefault();
            pasted.slice(0, otpBoxes.length).split('').forEach(function (digit, i) {
                otpBoxes[i].value = digit;
            });
            var nextIndex = Math.min(pasted.length, otpBoxes.length - 1);
            otpBoxes[nextIndex].focus();
        });
    });

    function getOtpValue() {
        return otpBoxes.map(function (box) { return box.value; }).join('');
    }

    otpForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var code = getOtpValue();

        if (code.length !== otpBoxes.length) {
            otpBoxesWrapper.classList.add('auth-otp-error');
            showError(otpError, 'لطفاً همه‌ی خانه‌های کد رو پر کنید.');
            return;
        }

        otpSubmitBtn.disabled = true;

        postJson('auth-verify-otp.php', { phone: currentPhone, code: code })
            .then(function (result) {
                if (!result.ok) {
                    otpBoxesWrapper.classList.add('auth-otp-error');
                    showError(otpError, result.data.error || 'کد وارد‌شده صحیح نیست.');
                    return;
                }

                if (result.data.is_new) {
                    // کاربر جدید — قبل از پیام موفقیت، اسمش رو می‌گیریم
                    showStep(stepName);
                    setTimeout(function () { nameInput.focus(); }, 50);
                    return;
                }

                // کاربر قبلی — مستقیم پیام خوش‌آمد شخصی‌سازی‌شده
                showSuccess(result.data.user && result.data.user.full_name);
            })
            .catch(function () {
                showError(otpError, 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.');
            })
            .finally(function () {
                otpSubmitBtn.disabled = false;
            });
    });

    function showSuccess(fullName) {
        if (fullName) {
            successTitle.textContent = 'خوش اومدی، ' + fullName + '! 👋';
            successSubtitle.textContent = 'ورود شما با موفقیت انجام شد.';
        } else {
            successTitle.textContent = 'خوش اومدید!';
            successSubtitle.textContent = 'ورود شما با موفقیت انجام شد.';
        }

        showStep(stepSuccess);
        // یه رفرش کوچیک تا هدر (وضعیت ورود/اسم کاربر) از سرور دوباره رندر بشه
        setTimeout(function () {
            window.location.reload();
        }, 1400);
    }

    // ============================================================
    // مرحله‌ی ۳: گرفتن اسم (فقط کاربر جدید)
    // ============================================================
    nameForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var name = nameInput.value.trim();

        if (name.length < 2) {
            showError(nameError, 'لطفاً یه اسم معتبر وارد کنید.');
            nameInput.focus();
            return;
        }

        nameError.hidden = true;
        nameSubmitBtn.disabled = true;

        postJson('auth-set-name.php', { name: name })
            .then(function (result) {
                if (!result.ok) {
                    showError(nameError, result.data.error || 'ثبت اسم با خطا مواجه شد.');
                    return;
                }

                showSuccess(result.data.full_name);
            })
            .catch(function () {
                showError(nameError, 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.');
            })
            .finally(function () {
                nameSubmitBtn.disabled = false;
            });
    });

    // ============================================================
    // شمارش معکوس + ارسال مجدد کد
    // ============================================================
    function formatTime(totalSeconds) {
        var m = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
        var s = String(totalSeconds % 60).padStart(2, '0');
        return toPersianDigits(m + ':' + s);
    }

    function startCountdown() {
        stopCountdown();
        var remaining = RESEND_SECONDS;
        resendTimerEl.hidden = false;
        resendBtn.hidden = true;
        resendTimerEl.textContent = 'ارسال مجدد کد تا ' + formatTime(remaining);

        countdownInterval = setInterval(function () {
            remaining--;
            if (remaining <= 0) {
                stopCountdown();
                resendTimerEl.hidden = true;
                resendBtn.hidden = false;
                return;
            }
            resendTimerEl.textContent = 'ارسال مجدد کد تا ' + formatTime(remaining);
        }, 1000);
    }

    function stopCountdown() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
    }

    resendBtn.addEventListener('click', function () {
        if (!currentPhone) {
            return;
        }

        resendBtn.disabled = true;

        requestOtpCode(currentPhone)
            .then(function (result) {
                if (!result.ok) {
                    showError(otpError, result.data.error || 'ارسال مجدد کد با خطا مواجه شد.');
                    resendBtn.hidden = false;
                    return;
                }

                otpBoxes.forEach(function (box) { box.value = ''; });
                otpBoxesWrapper.classList.remove('auth-otp-error');
                otpError.hidden = true;
                showDevNoteIfPresent(result.data);
                startCountdown();
                otpBoxes[0].focus();
            })
            .catch(function () {
                showError(otpError, 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.');
                resendBtn.hidden = false;
            })
            .finally(function () {
                resendBtn.disabled = false;
            });
    });
}

/**
 * منوی کشویی پروفایل (فقط وقتی کاربر لاگین کرده و هدر این بخش رو رندر کرده باشه)
 */
function initProfileMenu() {
    var menuTrigger = document.getElementById('profileMenuTrigger');
    var dropdown = document.getElementById('profileDropdown');

    if (!menuTrigger || !dropdown) {
        return;
    }

    function closeDropdown() {
        dropdown.hidden = true;
        menuTrigger.setAttribute('aria-expanded', 'false');
    }

    menuTrigger.addEventListener('click', function (e) {
        e.stopPropagation();
        var isOpen = !dropdown.hidden;
        dropdown.hidden = isOpen;
        menuTrigger.setAttribute('aria-expanded', String(!isOpen));
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.hidden && !dropdown.contains(e.target) && e.target !== menuTrigger) {
            closeDropdown();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeDropdown();
        }
    });
}