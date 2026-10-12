<?php
$activePage = $activePage ?? '';

// هر آیتم زیرمنو یک 'key' داره که با $activePage صفحات مقایسه می‌شه
// تا هم خود لینک active بشه هم آیتم والد (بارداری/کودک) تو منوی بالا هایلایت بمونه
$pregnancyPages = ['pregnancy-weeks', 'pregnancy-nutrition', 'pregnancy-supplements', 'pregnancy-warning-signs', 'pregnancy-exercises'];
$childPages = ['growth', 'vaccine', 'child-nutrition', 'milestones', 'conditions'];

$isPregnancyActive = in_array($activePage, $pregnancyPages, true);
$isChildActive = in_array($activePage, $childPages, true);

// وضعیت ورود کاربر (session رو خودِ config.php از قبل استارت کرده)
$isLoggedIn = isset($_SESSION['user_id']);
$userFullName = $_SESSION['user_full_name'] ?? null;
$userPhoneDisplay = '';
if ($isLoggedIn && !empty($_SESSION['user_phone'])) {
    $userPhoneDisplay = strtr($_SESSION['user_phone'], ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}
?>
<header class="header-wrapper" id="headerWrapper">
    <div class="header" id="header">

        <!-- لوگو + اسم -->
        <a href="index.php" class="logo-area">
            <img src="assets/image/logo.png" alt="مراقبت مادر و کودک" class="logo-image" />
            <span class="logo-text">مراقبت مادر و کودک</span>
        </a>

        <!-- منو -->
        <ul class="nav-menu">
            <li><a href="index.php" class="<?= $activePage === 'home' ? 'active' : '' ?>">خانه</a></li>

            <!-- بارداری (dropdown) -->
            <li class="has-dropdown">
                <button type="button" class="nav-parent-link <?= $isPregnancyActive ? 'active' : '' ?>"
                    aria-expanded="false">
                    بارداری
                    <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="pregnancy-weeks.php"
                            class="<?= $activePage === 'pregnancy-weeks' ? 'active' : '' ?>">هفته به هفته بارداری</a>
                    </li>
                    <li><a href="pregnancy-nutrition.php"
                            class="<?= $activePage === 'pregnancy-nutrition' ? 'active' : '' ?>">تغذیه دوران بارداری</a>
                    </li>
                    <li><a href="pregnancy-supplements.php"
                            class="<?= $activePage === 'pregnancy-supplements' ? 'active' : '' ?>">مکمل‌های ضروری</a>
                    </li>
                    <li><a href="pregnancy-warning-signs.php"
                            class="<?= $activePage === 'pregnancy-warning-signs' ? 'active' : '' ?>">علائم خطر</a></li>
                    <li><a href="pregnancy-exercises.php"
                            class="<?= $activePage === 'pregnancy-exercises' ? 'active' : '' ?>">ورزش‌های مناسب</a></li>
                </ul>
            </li>

            <!-- کودک (dropdown) -->
            <li class="has-dropdown">
                <button type="button" class="nav-parent-link <?= $isChildActive ? 'active' : '' ?>"
                    aria-expanded="false">
                    کودک
                    <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="growth.php" class="<?= $activePage === 'growth' ? 'active' : '' ?>">رشد و پیشرفت</a>
                    </li>
                    <li><a href="vaccine.php" class="<?= $activePage === 'vaccine' ? 'active' : '' ?>">زمان‌بندی
                            واکسن</a></li>
                    <li><a href="child-nutrition.php"
                            class="<?= $activePage === 'child-nutrition' ? 'active' : '' ?>">تغذیه کودک</a></li>
                    <li><a href="milestones.php" class="<?= $activePage === 'milestones' ? 'active' : '' ?>">مراحل
                            تکامل</a></li>
                    <li><a href="conditions.php" class="<?= $activePage === 'conditions' ? 'active' : '' ?>">مشکلات
                            شایع</a></li>
                </ul>
            </li>

            <li><a href="articles.php" class="<?= $activePage === 'articles' ? 'active' : '' ?>">مقالات</a></li>
            <li><a href="ai-qa.php" class="<?= $activePage === 'ai-qa' ? 'active' : '' ?>">پرسش از هوش مصنوعی</a></li>
        </ul>

        <!-- آیکون پروفایل -->
        <?php if ($isLoggedIn): ?>
            <div class="profile-menu" id="profileMenu">
                <button type="button" class="profile-icon" id="profileMenuTrigger" aria-label="حساب کاربری"
                    aria-expanded="false">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    <span class="profile-online-dot"></span>
                </button>
                <div class="profile-dropdown" id="profileDropdown" hidden>
                    <?php if ($userFullName): ?>
                        <div class="profile-dropdown-name">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            <span><?= htmlspecialchars($userFullName, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="profile-dropdown-phone">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="6" y="2" width="12" height="20" rx="2" />
                            <path d="M11 18h2" />
                        </svg>
                        <bdi dir="ltr"><?= htmlspecialchars($userPhoneDisplay, ENT_QUOTES, 'UTF-8') ?></bdi>
                    </div>
                    <a href="auth-logout.php" class="profile-dropdown-logout">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <path d="m16 17 5-5-5-5" />
                            <path d="M21 12H9" />
                        </svg>
                        خروج از حساب
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="#" class="profile-icon" id="authModalTrigger" aria-label="ورود یا ثبت‌نام">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
            </a>
        <?php endif; ?>

    </div>
</header>

<?php include TEMPLATES_PATH . '/components/auth-modal.php'; ?>
<link rel="stylesheet" href="assets/css/components/auth-modal.css">
<script src="assets/js/components/auth-modal.js" defer></script>