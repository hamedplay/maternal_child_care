<?php

require_once __DIR__ . '/../config/config.php';

use App\Services\AuthService;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'روش درخواست مجاز نیست.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'ابتدا وارد حساب خود شوید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name  = trim((string) ($input['name'] ?? ''));

$authService = new AuthService();
$result = $authService->setName((int) $_SESSION['user_id'], $name);

if (!$result['success']) {
    http_response_code(422);
    echo json_encode(['error' => $result['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

$_SESSION['user_full_name'] = $result['user']['full_name'];

echo json_encode([
    'success' => true,
    'full_name' => $result['user']['full_name'],
], JSON_UNESCAPED_UNICODE);
