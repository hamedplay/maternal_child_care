<?php

require_once __DIR__ . '/../config/config.php';

use App\Services\AuthService;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'روش درخواست مجاز نیست.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$phone = trim((string) ($input['phone'] ?? ''));
$code  = trim((string) ($input['code'] ?? ''));

$authService = new AuthService();
$result = $authService->verifyOtp($phone, $code);

if (!$result['success']) {
    http_response_code(422);
    echo json_encode(['error' => $result['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

// ساخت session ورود
$_SESSION['user_id']        = $result['user']['id'];
$_SESSION['user_phone']     = $result['user']['phone'];
$_SESSION['user_full_name'] = $result['user']['full_name'] ?? null;

echo json_encode([
    'success' => true,
    'is_new'  => $result['is_new'],
    'user' => [
        'phone'     => $result['user']['phone'],
        'full_name' => $result['user']['full_name'] ?? null,
    ],
], JSON_UNESCAPED_UNICODE);