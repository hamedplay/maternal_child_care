<?php

require_once __DIR__ . '/../config/config.php';

$activePage = 'ai-qa';
$pageTitle  = 'پرسش از هوش مصنوعی - مراقبت مادر و کودک';

$chatHistory = $_SESSION['ai_chat_history'] ?? [];
$hasHistory  = !empty($chatHistory);

// موضوعات پیشنهادی توی سایدبار — کلیک روی هرکدوم سوال شروع‌کننده رو توی چت پر می‌کنه
$suggestedTopics = [
    [
        'title'  => 'بارداری',
        'desc'   => 'رشد جنین و تغییرات بدن',
        'prompt' => 'توی این هفته از بارداریم چه تغییراتی توی بدنم طبیعیه؟',
        'icon'   => 'pregnancy',
    ],
    [
        'title'  => 'رشد کودک',
        'desc'   => 'مراحل رشد و تکامل',
        'prompt' => 'مراحل مهم رشد کودک توی سال اول زندگی چیه؟',
        'icon'   => 'baby',
    ],
    [
        'title'  => 'تغذیه',
        'desc'   => 'تغذیه در بارداری و کودکان',
        'prompt' => 'برای تغذیه‌ی سالم توی بارداری چه نکاتی رو باید رعایت کنم؟',
        'icon'   => 'nutrition',
    ],
    [
        'title'  => 'واکسیناسیون',
        'desc'   => 'زمان‌بندی واکسن‌ها',
        'prompt' => 'برنامه‌ی واکسیناسیون نوزاد از بدو تولد چطوریه؟',
        'icon'   => 'vaccine',
    ],
    [
        'title'  => 'سلامت مادر',
        'desc'   => 'مراقبت‌های دوران بارداری',
        'prompt' => 'برای سلامت خودم توی دوران بارداری به چه نکاتی توجه کنم؟',
        'icon'   => 'heart-pulse',
    ],
];

// سوالات پیشنهادی روی صفحه‌ی خوش‌آمدگویی (چهارتای وسط)
$quickPrompts = [
    ['label' => 'رشد جنین', 'icon' => 'baby', 'prompt' => 'این هفته جنین چقدر رشد کرده و چه شکلیه؟'],
    ['label' => 'هفته بارداری من', 'icon' => 'calendar', 'prompt' => 'الان توی چه هفته‌ای از بارداری هستم و چه اتفاقی داره می‌افته؟'],
    ['label' => 'علائم طبیعی', 'icon' => 'heart-pulse', 'prompt' => 'کدوم علائم بارداری طبیعیه و کدوم نیاز به مراجعه‌ی فوریه؟'],
    ['label' => 'تغذیه مناسب', 'icon' => 'nutrition', 'prompt' => 'توی سه‌ماهه‌ای که هستم به چه مواد مغذی‌ای بیشتر نیاز دارم؟'],
];

/**
 * آیکون‌های SVG کوچیک برای موضوعات و چیپ‌های پیشنهادی —
 * به‌صورت رشته برگردونده می‌شن تا نیازی به فایل جدا برای هر آیکون نباشه.
 */
function aiqa_icon(string $name): string
{
    $icons = [
        'pregnancy' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4a3 3 0 1 1 3 3" /><path d="M9 7v1a5 5 0 0 0 5 5c1 3 .5 6-1 8" /><path d="M9 8c-3 1-4 4-3 8 .3 1.2 1 2 2 2" /></svg>',
        'baby' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="5" /><path d="M9 8.5c.5-1 3.5-1 4 0" /><path d="M10 11h.01" /><path d="M14 11h.01" /><path d="M8 20c0-3 2-4 4-4s4 1 4 4" /></svg>',
        'nutrition' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3v6a2 2 0 0 0 2 2h0a2 2 0 0 0 2-2V3" /><path d="M9 11v10" /><path d="M17 3c-2 1-3 3-3 6s1 3 3 3v8" /></svg>',
        'vaccine' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m18.5 2 3.5 3.5" /><path d="m13 5 6 6" /><path d="m2 22 5-1.5L18.5 9 15 5.5 3.5 17Z" /><path d="m8 12 3 3" /><path d="m5 15 3 3" /></svg>',
        'heart-pulse' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z" /><path d="M3.5 10h3l2-3 3 6 2-3h4" /></svg>',
        'calendar' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="3" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>',
    ];

    return $icons[$name] ?? '';
}

/**
 * تبدیل سبک و امن مارک‌داون به HTML، مخصوص پاسخ‌های مدل هوش مصنوعی (DeepSeek).
 * فقط **بولد** و تیترهای ### رو تبدیل می‌کنه؛ همیشه اول متن رو escape می‌کنه
 * تا هیچ HTML/اسکریپتی از پاسخ مدل مستقیم اجرا نشه.
 */
function aiqa_markdown_lite(string $text): string
{
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $lines = explode("\n", $escaped);

    $html = '';
    $prevWasText = false;

    foreach ($lines as $line) {
        if (preg_match('/^\s*#{1,6}\s*(.+)$/', $line, $m)) {
            $headingText = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $m[1]);
            $html .= '<div class="aiqa-md-heading">' . $headingText . '</div>';
            $prevWasText = false;
            continue;
        }

        $formatted = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
        if ($prevWasText) {
            $html .= '<br>';
        }
        $html .= $formatted;
        $prevWasText = true;
    }

    return $html;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components/header.css">
    <link rel="stylesheet" href="assets/css/components/button.css">
    <link rel="stylesheet" href="assets/css/components/footer.css">
    <link rel="stylesheet" href="assets/css/pages/ai-qa.css">
</head>

<body>

    <section class="banner-section" data-header-offset>
        <?php include TEMPLATES_PATH . '/partials/header.php'; ?>
    </section>

    <main class="aiqa-page">
        <div class="aiqa-layout">

            <!-- ============================================================
            سایدبار: موضوعات پیشنهادی
            ============================================================ -->
            <aside class="aiqa-sidebar">
                <h2 class="aiqa-sidebar-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.5 3.5L7 8l3.5 1.5L12 13l1.5-3.5L17 8l-3.5-1.5z" /><path d="M5 17l-.7 1.6L3 19l1.6.7.7 1.6.7-1.6 1.6-.7-1.6-.7z" /><path d="M19 15l-.6 1.3-1.3.6 1.3.6.6 1.3.6-1.3 1.3-.6-1.3-.6z" /></svg>
                    موضوعات پیشنهادی
                </h2>

                <div class="aiqa-topic-list">
                    <?php foreach ($suggestedTopics as $topic): ?>
                        <button type="button" class="aiqa-topic-card" data-prompt="<?= htmlspecialchars($topic['prompt'], ENT_QUOTES, 'UTF-8') ?>">
                            <span class="aiqa-topic-icon aiqa-icon-<?= $topic['icon'] ?>">
                                <?= aiqa_icon($topic['icon']) ?>
                            </span>
                            <span class="aiqa-topic-text">
                                <strong><?= htmlspecialchars($topic['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($topic['desc'], ENT_QUOTES, 'UTF-8') ?></small>
                            </span>
                            <svg class="aiqa-topic-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- پترن تزئینی پایین سایدبار (هماهنگ با فوتر سایت) -->
                <svg class="aiqa-sidebar-decor" viewBox="0 0 260 220" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M130 40c-45 0-80 35-80 78 0 30 15 55 40 68 8 4 15-2 12-10-10-25-8-52 8-73 14-19 34-30 20-63Z" fill="var(--color-teal)" opacity="0.08" />
                    <path d="M130 60c30 30 30 70 5 95" stroke="var(--color-teal)" stroke-width="2" fill="none" opacity="0.2" />
                    <path d="M85 150c-6 14-4 30 8 40" stroke="var(--color-navy)" stroke-width="2" fill="none" opacity="0.12" />
                </svg>
            </aside>

            <!-- ============================================================
            پنل اصلی گفتگو
            ============================================================ -->
            <section class="aiqa-main">

                <div class="aiqa-main-header">
                    <div class="aiqa-assistant-info">
                        <span class="aiqa-assistant-avatar">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="8" width="16" height="12" rx="4" /><path d="M12 8V4" /><circle cx="12" cy="3" r="1" /><circle cx="9" cy="14" r="1" /><circle cx="15" cy="14" r="1" /><path d="M9 17h6" /></svg>
                        </span>
                        <div>
                            <h3>دستیار هوشمند</h3>
                            <p>مراقبت مادر و کودک</p>
                        </div>
                    </div>
                    <span class="aiqa-status"><span class="aiqa-status-dot"></span> آنلاین</span>
                </div>

                <div class="aiqa-chat" id="aiQaChat">

                    <!-- صفحه‌ی خوش‌آمدگویی (فقط وقتی تاریخچه‌ی چت خالیه نمایش داده می‌شه) -->
                    <div class="aiqa-welcome" id="aiQaWelcome" <?= $hasHistory ? 'hidden' : '' ?>>
                        <div class="aiqa-mascot">
                            <svg viewBox="0 0 220 200" class="aiqa-mascot-svg" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <defs>
                                    <radialGradient id="aiqaGlow" cx="50%" cy="50%" r="50%">
                                        <stop offset="0%" stop-color="var(--color-teal)" stop-opacity="0.22" />
                                        <stop offset="100%" stop-color="var(--color-teal)" stop-opacity="0" />
                                    </radialGradient>
                                    <linearGradient id="aiqaBotFill" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#ffffff" />
                                        <stop offset="100%" stop-color="#eef9fb" />
                                    </linearGradient>
                                </defs>

                                <circle cx="110" cy="105" r="95" fill="url(#aiqaGlow)" />

                                <!-- برگ‌های تزئینی -->
                                <path d="M55 70c-18 6-28 22-24 40 2 8 12 8 15 0 6-16 6-28 9-40Z" fill="var(--color-teal)" opacity="0.18" />
                                <path d="M165 70c18 6 28 22 24 40-2 8-12 8-15 0-6-16-6-28-9-40Z" fill="var(--color-navy)" opacity="0.14" />

                                <!-- قلب شناور -->
                                <path d="M70 55c-4-6-13-6-16 1-3 6 1 12 8 17l8 6 8-6c7-5 11-11 8-17-3-7-12-7-16-1Z" fill="var(--color-teal)" opacity="0.55" />

                                <!-- بدنه‌ی ربات -->
                                <rect x="60" y="80" width="100" height="80" rx="26" fill="url(#aiqaBotFill)" stroke="var(--color-teal)" stroke-width="2.5" />

                                <!-- آنتن -->
                                <line x1="110" y1="80" x2="110" y2="58" stroke="var(--color-navy)" stroke-width="3" stroke-linecap="round" />
                                <circle cx="110" cy="52" r="7" fill="var(--color-teal)" />

                                <!-- گوش‌ها -->
                                <rect x="45" y="102" width="14" height="28" rx="7" fill="var(--color-bg-soft-2)" stroke="var(--color-teal)" stroke-width="2" />
                                <rect x="161" y="102" width="14" height="28" rx="7" fill="var(--color-bg-soft-2)" stroke="var(--color-teal)" stroke-width="2" />

                                <!-- چشم‌ها -->
                                <circle cx="90" cy="115" r="6" fill="var(--color-navy)" />
                                <circle cx="130" cy="115" r="6" fill="var(--color-navy)" />

                                <!-- لبخند -->
                                <path d="M92 132c6 8 30 8 36 0" stroke="var(--color-navy)" stroke-width="3" stroke-linecap="round" fill="none" />

                                <!-- گونه‌ها -->
                                <circle cx="78" cy="126" r="5" fill="var(--color-teal)" opacity="0.35" />
                                <circle cx="142" cy="126" r="5" fill="var(--color-teal)" opacity="0.35" />
                            </svg>
                        </div>

                        <h1>سلام! چطور می‌تونم کمکتون کنم؟</h1>
                        <p>من دستیار هوشمند مراقبت مادر و کودک هستم.<br>درباره‌ی بارداری، رشد کودک، تغذیه و سلامت سوالی دارید؟</p>

                        <div class="aiqa-quick-prompts">
                            <?php foreach ($quickPrompts as $qp): ?>
                                <button type="button" class="aiqa-quick-chip" data-prompt="<?= htmlspecialchars($qp['prompt'], ENT_QUOTES, 'UTF-8') ?>">
                                    <span><?= htmlspecialchars($qp['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="aiqa-quick-chip-icon aiqa-icon-<?= $qp['icon'] ?>">
                                        <?= aiqa_icon($qp['icon']) ?>
                                    </span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- پیام‌های گفتگو -->
                    <div class="aiqa-messages" id="aiQaMessages" <?= $hasHistory ? '' : 'hidden' ?>>
                        <?php foreach ($chatHistory as $msg): ?>
                            <div class="aiqa-message <?= $msg['role'] === 'user' ? 'user' : 'assistant' ?>">
                                <?php if ($msg['role'] !== 'user'): ?>
                                    <span class="aiqa-message-avatar">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="8" width="16" height="12" rx="4" /><path d="M12 8V4" /><circle cx="12" cy="3" r="1" /><circle cx="9" cy="14" r="1" /><circle cx="15" cy="14" r="1" /><path d="M9 17h6" /></svg>
                                    </span>
                                <?php else: ?>
                                    <span class="aiqa-message-avatar aiqa-message-avatar-user">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
                                    </span>
                                <?php endif; ?>
                                <div class="bubble <?= $msg['role'] !== 'user' ? 'bubble-rich' : '' ?>">
                                    <?= $msg['role'] !== 'user'
                                        ? aiqa_markdown_lite($msg['content'])
                                        : nl2br(htmlspecialchars($msg['content'], ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>

                <form class="aiqa-form" id="aiQaForm">
                    <button type="button" class="aiqa-attach-btn" disabled title="به‌زودی">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14" /></svg>
                    </button>
                    <textarea id="aiQaInput" placeholder="سوال خود را درباره مادر و کودک بنویسید..." rows="1" maxlength="500" required></textarea>
                    <button type="submit" class="aiqa-send-btn" id="aiQaSubmit" aria-label="ارسال">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-8-8 18-2-8-8-2Z" /></svg>
                    </button>
                </form>

                <p class="aiqa-disclaimer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" /></svg>
                    توجه: پاسخ‌های این دستیار جنبه‌ی آموزشی دارند و جایگزین تشخیص یا مشاوره‌ی پزشک نیستند.
                </p>

            </section>

        </div>
    </main>



    <script src="assets/js/components/header.js"></script>
    <script src="assets/js/pages/ai-qa.js"></script>

</body>

</html>