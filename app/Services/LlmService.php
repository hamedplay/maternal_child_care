<?php

namespace App\Services;

/**
 * مسئول ارتباط با مدل زبانی از طریق OpenRouter.
 * سؤال کاربر، context بازیابی‌شده از دیتابیس و history را به مدل می‌فرستد.
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

        $this->apiKey = $config['openrouter_api_key'];
        $this->model = $config['model'];
        $this->siteUrl = $config['site_url'];
        $this->siteName = $config['site_name'];
    }

    /**
     * @param string $question سؤال فعلی کاربر
     * @param string[] $contextBlocks اطلاعات مرجع بازیابی‌شده توسط KnowledgeRetriever
     * @param array<int, array{role:string, content:string}> $history پیام‌های قبلی گفتگو
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
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.4,
            'max_tokens' => 700,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'HTTP-Referer: ' . $this->siteUrl,
                'X-Title: ' . $this->siteName,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
            . "وظیفه‌ات پاسخ‌دادن به سوالات کاربران درباره‌ی بارداری، نوزاد و کودک، رشد و تکامل، تغذیه، واکسیناسیون، مکمل‌ها، بیماری‌های شایع و علائم هشدار است.\n\n"
            . "قوانین مهم:\n"
            . "۱. همیشه به زبان فارسی و با لحنی ساده و قابل‌فهم پاسخ بده.\n"
            . "۲. اگر «اطلاعات مرجع» زیر به سوال کاربر مرتبط بود، پاسخ را در درجه اول بر پایه همان اطلاعات بساز.\n"
            . "۳. بین اطلاعات مربوط به بارداری و اطلاعات مربوط به نوزاد/کودک تفکیک قائل شو و منبع نامرتبط را وارد پاسخ نکن.\n"
            . "۴. تو جایگزین پزشک نیستی؛ برای هر علامت نگران‌کننده یا اورژانسی، کاربر را به مراجعه فوری به پزشک یا بیمارستان ارجاع بده.\n"
            . "۵. هرگز دوز دقیق دارو یا مکمل را شخصاً تجویز نکن؛ فقط اطلاعات عمومی مرجع را بازگو کن و دوز دقیق را به پزشک واگذار کن.\n"
            . "۶. اگر اطلاعات مرجع کافی نبود یا مطمئن نبودی، صریح بگو اطلاعات کافی نداری و چیزی از خودت نساز.\n"
            . "۷. اگر استاندارد رشد یا تکامل کودک ارائه شده، تاکید کن این داده‌ها مرجع عمومی/آماری هستند و ارزیابی نهایی با پزشک کودک است.\n"
            . "۸. پاسخ‌ها را کوتاه و متمرکز نگه دار (حداکثر چند پاراگراف کوتاه).\n";

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
