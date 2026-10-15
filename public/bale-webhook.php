<?php

require_once __DIR__ . '/../config/config.php';

use App\Services\BaleLinkService;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

$config = require BASE_PATH . '/config/services.php';
$expectedSecret = (string) ($config['bale']['webhook_secret'] ?? '');
$providedSecret = (string) ($_GET['secret'] ?? '');

// اگر Secret تعریف شده، URL وب‌هوک باید همان secret را در query داشته باشد.
if ($expectedSecret !== '' && !hash_equals($expectedSecret, $providedSecret)) {
    http_response_code(403);
    echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

// برای جلوگیری از retryهای غیرضروری Provider، update معتبر HTTP 200 می‌گیرد؛
// نتیجه پردازش صرفاً در body اعلام می‌شود.
$processed = (new BaleLinkService())->processWebhookUpdate($input);

echo json_encode(['ok' => true, 'processed' => $processed], JSON_UNESCAPED_UNICODE);
