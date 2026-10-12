<?php

namespace App\Services;

/**
 * مسئول ارتباط با مدل زبانی (از طریق OpenRouter API).
 * سوال کاربر + اطلاعات مرجع از دیتابیس (context) + تاریخچه‌ی گفتگو رو می‌گیره
 * و متن پاسخ رو برمی‌گردونه.
 */
class LlmService
{
    private string $apiKey;
    private string $model;
    private string $siteUrl;
    private string $siteName;

    public function __construct()
    {
        $config = require BASE_PATH . '/config/ai.php';

        $this->apiKey   = $config['openrouter_api_key'];
        $this->model    = $config['model'];
        $this->siteUrl  = $config['site_url'];
        $this->siteName = $config['site_name'];
    }

    /**
     * @param string $question سوال فعلی کاربر
     * @param string[] $contextBlocks تکه‌های متنی مرتبط از دیتابیس (خروجی AiKnowledgeRepository::search)
     * @param array<int, array{role:string, content:string}> $history پیام‌های قبلی گفتگو (از session)
     */
    public function ask(string $question, array $contextBlocks = [], array $history = []): string
    {
        if (empty($this->apiKey)) {
            error_log('LlmService: OPENROUTER_API_KEY تنظیم نشده.');
            return 'سرویس هوش مصنوعی هنوز پیکربندی نشده. لطفاً بعداً دوباره امتحان کنید.';
        }

        $messages = [['role' => 'system', 'content' => $this->buildSystemPrompt($contextBlocks)]];

        foreach ($history as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        $payload = json_encode([
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => 0.4,
            'max_tokens'  => 700,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'HTTP-Referer: ' . $this->siteUrl,
                'X-Title: ' . $this->siteName,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError) {
            error_log('LlmService خطای اتصال: ' . $curlError);
            return 'در ارتباط با سرویس هوش مصنوعی مشکلی پیش اومد. لطفاً دوباره امتحان کنید.';
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || !isset($data['choices'][0]['message']['content'])) {
            error_log('LlmService پاسخ نامعتبر (HTTP ' . $httpCode . '): ' . $response);
            return 'پاسخ نامعتبری از سرویس هوش مصنوعی دریافت شد. لطفاً دوباره تلاش کنید.';
        }

        return trim($data['choices'][0]['message']['content']);
    }

    private function buildSystemPrompt(array $contextBlocks): string
    {
        $prompt = "تو یک دستیار هوشمند در وب‌سایت «مراقبت مادر و کودک» هستی. "
            . "وظیفه‌ات پاسخ‌دادن به سوالات کاربران درباره‌ی بارداری، تغذیه، ورزش و علائم هشدار دوران بارداری است.\n\n"
            . "قوانین مهم:\n"
            . "۱. همیشه به زبان فارسی و با لحنی دلسوز، ساده و قابل‌فهم پاسخ بده.\n"
            . "۲. اگر «اطلاعات مرجع» زیر به سوال کاربر مرتبط بود، پاسخت رو اول از همه بر پایه‌ی همون بساز.\n"
            . "۳. تو جایگزین پزشک نیستی؛ برای هر علامت نگران‌کننده یا اورژانسی، کاربر رو قاطعانه به مراجعه‌ی فوری به پزشک یا بیمارستان ارجاع بده.\n"
            . "۴. هرگز دوز دقیق دارو یا مکمل رو شخصاً تجویز نکن؛ فقط اطلاعات عمومی مرجع رو بازگو کن و توصیه کن دوز دقیق رو پزشک مشخص کنه.\n"
            . "۵. اگر مطمئن نیستی یا اطلاعات مرجع کافی نبود، صادقانه بگو نمی‌دونی و مشورت با پزشک رو پیشنهاد بده؛ چیزی رو از خودت نساز.\n"
            . "۶. اگر «استاندارد رشد» یا «تکامل کودک» بهت داده شد، همیشه تاکید کن این‌ها میانگین آماری‌ان و تفاوت جزئی کاملاً طبیعیه؛ برای هرگونه نگرانی جدی، تشخیص قطعی با پزشک اطفاله.\n"
            . "۷. پاسخ‌ها رو کوتاه و متمرکز نگه دار (حداکثر چند پاراگراف کوتاه).\n";

        if (!empty($contextBlocks)) {
            $prompt .= "\n--- اطلاعات مرجع مرتبط از دیتابیس سایت ---\n";
            foreach ($contextBlocks as $block) {
                $prompt .= "- " . $block . "\n";
            }
            $prompt .= "--- پایان اطلاعات مرجع ---\n";
        }

        return $prompt;
    }
}