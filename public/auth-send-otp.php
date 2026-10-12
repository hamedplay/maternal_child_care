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

$authService = new AuthService();
$result = $authService->requestOtp($phone);

if (!$result['success']) {
    http_response_code(422);
    echo json_encode(['error' => $result['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

$response = ['success' => true];
if (isset($result['debug_code'])) {
    $response['debug_code'] = $result['debug_code'];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);