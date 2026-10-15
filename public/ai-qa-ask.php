<?php

require_once __DIR__ . '/../config/config.php';

use App\Repositories\AiConversationRepository;
use App\Services\KnowledgeRetriever;
use App\Services\LlmService;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'روش درخواست مجاز نیست.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$question = trim((string) ($input['question'] ?? ''));

if ($question === '') {
    http_response_code(422);
    echo json_encode(['error' => 'لطفاً سوال خود را بنویسید.']);
    exit;
}

if (mb_strlen($question) > 500) {
    http_response_code(422);
    echo json_encode(['error' => 'سوال شما خیلی طولانیه. لطفاً کوتاه‌ترش کن (حداکثر ۵۰۰ کاراکتر).']);
    exit;
}

if (!isset($_SESSION['ai_chat_history']) || !is_array($_SESSION['ai_chat_history'])) {
    $_SESSION['ai_chat_history'] = [];
}

$aiConfig = require BASE_PATH . '/config/ai.php';
$maxHistory = $aiConfig['max_history_messages'];

// فقط N پیام آخر برای context مدل ارسال می‌شود.
$recentHistory = array_slice($_SESSION['ai_chat_history'], -$maxHistory);

/*
 * Persistence گفتگو مستقل از Retrieval/LLM نگه داشته می‌شود.
 * اگر migration هنوز روی یک محیط اعمال نشده باشد، Repository خطا را log می‌کند
 * و conversationId=null می‌شود؛ در این حالت پاسخ AI همچنان قطع نمی‌شود.
 */
$conversationRepository = new AiConversationRepository();
$conversationId = $conversationRepository->getOrCreateConversation(
    session_id(),
    isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null
);

if ($conversationId !== null) {
    $conversationRepository->addMessage($conversationId, 'user', $question);
}

$knowledgeRetriever = new KnowledgeRetriever();
$contextBlocks = $knowledgeRetriever->retrieve($question);

$llmService = new LlmService();
$answer = $llmService->ask($question, $contextBlocks, $recentHistory);

if ($conversationId !== null) {
    $conversationRepository->addMessage(
        $conversationId,
        'assistant',
        $answer,
        (string) $aiConfig['model']
    );
}

$_SESSION['ai_chat_history'][] = ['role' => 'user', 'content' => $question];
$_SESSION['ai_chat_history'][] = ['role' => 'assistant', 'content' => $answer];

if (count($_SESSION['ai_chat_history']) > 40) {
    $_SESSION['ai_chat_history'] = array_slice($_SESSION['ai_chat_history'], -40);
}

echo json_encode(['answer' => $answer], JSON_UNESCAPED_UNICODE);
