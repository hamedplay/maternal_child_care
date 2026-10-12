document.addEventListener('DOMContentLoaded', function () {
    const headerWrapper = document.getElementById('headerWrapper');
    if (!headerWrapper) return;

    // هر صفحه‌ای اولین سکشن بعد از هدر رو با data-header-offset مشخص می‌کنه
    // تا محتواش زیر هدر ثابت پنهان نشه (پیش‌فرض: banner-section)
    const pushedEl = document.querySelector('[data-header-offset]') || document.querySelector('.banner-section');

    function handleScroll() {
        if (window.scrollY > 50) {
            headerWrapper.classList.add('scrolled');
        } else {
            headerWrapper.classList.remove('scrolled');
        }
    }

    // چون هدر fixed هست و از جریان صفحه خارجه، ارتفاعش رو اندازه می‌گیریم
    // و به‌عنوان فاصله‌ی بالای اولین سکشن می‌ذاریم تا چیزی زیرش پنهان نشه
    function adjustHeaderOffset() {
        if (!pushedEl) return;
        const headerHeight = headerWrapper.offsetHeight;
        const topGap = parseFloat(getComputedStyle(headerWrapper).top) || 0;
        pushedEl.style.paddingTop = (headerHeight + topGap + 20) + 'px';
    }

    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', adjustHeaderOffset);

    handleScroll();
    adjustHeaderOffset();

    // ============================================================
    // منوهای کشویی (بارداری / کودک)
    // در دسکتاپ با هاور (فقط CSS) باز می‌شن؛ این بخش برای موبایل/تبلت
    // (که هاور معنی نداره) با کلیک باز/بسته می‌شن.
    // ============================================================
    const dropdownItems = document.querySelectorAll('.has-dropdown');

    function closeAllDropdowns(except) {
        dropdownItems.forEach(function (item) {
            if (item !== except) {
                item.classList.remove('open');
                const btn = item.querySelector('.nav-parent-link');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    dropdownItems.forEach(function (item) {
        const trigger = item.querySelector('.nav-parent-link');
        if (!trigger) return;

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = item.classList.contains('open');
            closeAllDropdowns(item);
            item.classList.toggle('open', !isOpen);
            trigger.setAttribute('aria-expanded', String(!isOpen));
        });
    });

    // کلیک بیرون از منو → بستن همه‌ی dropdown ها
    document.addEventListener('click', function () {
        closeAllDropdowns();
    });

    // کلید Escape → بستن همه‌ی dropdown ها
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllDropdowns();
        }
    });
});