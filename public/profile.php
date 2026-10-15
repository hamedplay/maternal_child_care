<?php

require_once __DIR__ . '/../config/config.php';

use App\Services\ArticleContentSanitizer;
use App\Services\RecommendationService;
use App\Services\UserProfileService;

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$activePage = 'profile';
$userId = (int) $_SESSION['user_id'];
$service = new UserProfileService();
$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $service->save($userId, $_POST);
    if ($result['success']) {
        $message = 'اطلاعات پروفایل با موفقیت ذخیره شد.';
        $messageType = 'success';
    } else {
        $message = $result['error'] ?? 'ذخیره پروفایل با خطا مواجه شد.';
        $messageType = 'error';
    }
}

$data = $service->get($userId);
$user = $data['user'] ?? [];
$profile = $data['profile'] ?? [];
$recommendations = (new RecommendationService())->forUser($userId);
$articleSanitizer = new ArticleContentSanitizer();

$stage = $profile['stage'] ?? 'planning';
$childGender = $profile['child_gender'] ?? 'unknown';
$pageTitle = 'پروفایل من | مراقبت مادر و کودک';

function profile_excerpt(string $html, ArticleContentSanitizer $sanitizer, int $length = 130): string
{
    $plain = strip_tags($sanitizer->removeGahvareAds($html));
    $plain = trim(preg_replace('/\s+/u', ' ', $plain) ?? $plain);

    return mb_strlen($plain) > $length ? mb_substr($plain, 0, $length) . '…' : $plain;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components/header.css">
    <link rel="stylesheet" href="assets/css/components/footer.css">
    <link rel="stylesheet" href="assets/css/pages/profile.css">
</head>
<body>
<?php include TEMPLATES_PATH . '/partials/header.php'; ?>

<main class="profile-page">
    <section class="profile-hero">
        <h1>پروفایل من</h1>
        <p>اطلاعاتی که اینجا وارد می‌کنید برای نمایش محتوای متناسب با مرحله بارداری یا سن کودک استفاده می‌شود.</p>
    </section>

    <?php if ($message !== null): ?>
        <div class="profile-message <?= $messageType === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="profile-card">
        <form method="post">
            <div class="profile-grid">
                <div class="profile-field">
                    <label for="full_name">نام و نام خانوادگی</label>
                    <input id="full_name" name="full_name" type="text" maxlength="150" required
                        value="<?= htmlspecialchars((string) ($user['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="profile-field">
                    <label for="phone">شماره موبایل</label>
                    <input id="phone" type="text" readonly
                        value="<?= htmlspecialchars((string) ($user['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="profile-field">
                    <label for="birth_date">تاریخ تولد</label>
                    <input id="birth_date" name="birth_date" type="date"
                        value="<?= htmlspecialchars((string) ($profile['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="profile-field">
                    <label for="stage">مرحله فعلی</label>
                    <select id="stage" name="stage">
                        <option value="planning" <?= $stage === 'planning' ? 'selected' : '' ?>>قصد بارداری</option>
                        <option value="pregnant" <?= $stage === 'pregnant' ? 'selected' : '' ?>>باردار</option>
                        <option value="postpartum" <?= $stage === 'postpartum' ? 'selected' : '' ?>>پس از زایمان</option>
                        <option value="child_care" <?= $stage === 'child_care' ? 'selected' : '' ?>>مراقبت کودک</option>
                    </select>
                </div>

                <h2 class="profile-section-title">اطلاعات بارداری</h2>

                <div class="profile-field">
                    <label for="pregnancy_due_date">تاریخ تقریبی زایمان</label>
                    <input id="pregnancy_due_date" name="pregnancy_due_date" type="date"
                        value="<?= htmlspecialchars((string) ($profile['pregnancy_due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <h2 class="profile-section-title">اطلاعات کودک</h2>

                <div class="profile-field">
                    <label for="child_name">نام کودک</label>
                    <input id="child_name" name="child_name" type="text" maxlength="100"
                        value="<?= htmlspecialchars((string) ($profile['child_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="profile-field">
                    <label for="child_birth_date">تاریخ تولد کودک</label>
                    <input id="child_birth_date" name="child_birth_date" type="date"
                        value="<?= htmlspecialchars((string) ($profile['child_birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="profile-field">
                    <label for="child_gender">جنسیت کودک</label>
                    <select id="child_gender" name="child_gender">
                        <option value="unknown" <?= $childGender === 'unknown' ? 'selected' : '' ?>>مشخص نشده</option>
                        <option value="boy" <?= $childGender === 'boy' ? 'selected' : '' ?>>پسر</option>
                        <option value="girl" <?= $childGender === 'girl' ? 'selected' : '' ?>>دختر</option>
                    </select>
                </div>
            </div>

            <div class="profile-actions">
                <button type="submit" class="profile-submit">ذخیره پروفایل</button>
            </div>
        </form>

        <p class="profile-note">شماره موبایل از سیستم ورود OTP می‌آید و در این صفحه قابل تغییر نیست.</p>
    </section>

    <?php if (!empty($recommendations['quick_links'])): ?>
        <section class="profile-recommendation-section">
            <div class="profile-recommendation-heading">
                <div>
                    <h2>دسترسی سریع برای شما</h2>
                    <p><?= htmlspecialchars($recommendations['reason'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <div class="profile-quick-grid">
                <?php foreach ($recommendations['quick_links'] as $link): ?>
                    <a class="profile-quick-card" href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>">
                        <strong><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($link['description'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($recommendations['articles'])): ?>
        <section class="profile-recommendation-section">
            <div class="profile-recommendation-heading">
                <div>
                    <h2>مقالات پیشنهادی</h2>
                    <p><?= htmlspecialchars($recommendations['reason'], ENT_QUOTES, 'UTF-8') ?> و موضوعاتی که اخیراً دیده‌اید.</p>
                </div>
                <a href="articles.php">همه مقالات</a>
            </div>
            <div class="profile-recommendation-grid">
                <?php foreach ($recommendations['articles'] as $article): ?>
                    <a class="profile-recommendation-card" href="article.php?slug=<?= urlencode($article['slug']) ?>">
                        <span class="profile-recommendation-tag"><?= htmlspecialchars((string) ($article['category_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <h3><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars(profile_excerpt((string) $article['content'], $articleSanitizer), ENT_QUOTES, 'UTF-8') ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($recommendations['faqs'])): ?>
        <section class="profile-recommendation-section">
            <div class="profile-recommendation-heading">
                <div>
                    <h2>سوالات پیشنهادی</h2>
                    <p>FAQهای مرتبط با نیاز فعلی شما.</p>
                </div>
            </div>
            <div class="profile-faq-list">
                <?php foreach ($recommendations['faqs'] as $faq): ?>
                    <details class="profile-faq-item">
                        <summary><?= htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8') ?></summary>
                        <p><?= nl2br(htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8')) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include TEMPLATES_PATH . '/partials/footer.php'; ?>
<script src="assets/js/components/header.js"></script>
</body>
</html>
