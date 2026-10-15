<?php

namespace App\Services;

/**
 * ارتباط حداقلی با Bot API بله برای Reminder متنی.
 */
class BaleService
{
    private string $botToken;

    public function __construct()
    {
        $config = require BASE_PATH . '/config/services.php';
        $this->botToken = (string) ($config['bale']['bot_token'] ?? '');
    }

    public function isConfigured(): bool
    {
        return $this->botToken !== '';
    }

    /** @return array{success:bool,error?:string,response?:array} */
    public function sendMessage(string $chatId, string $text): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'BALE_BOT_TOKEN تنظیم نشده است.'];
        }

        $url = 'https://tapi.bale.ai/bot' . rawurlencode($this->botToken) . '/sendMessage';
        $payload = json_encode([
            'chat_id' => $chatId,
            'text' => $text,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            error_log('[BaleService] transport error: ' . $curlError);
            return ['success' => false, 'error' => 'ارتباط با بله برقرار نشد.'];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            error_log('[BaleService] invalid JSON response. HTTP=' . $httpCode);
            return ['success' => false, 'error' => 'پاسخ نامعتبر از بله دریافت شد.'];
        }

        if ($httpCode !== 200 || empty($data['ok'])) {
            $description = trim((string) ($data['description'] ?? ''));
            error_log(sprintf(
                '[BaleService] sendMessage failed. HTTP=%d DESCRIPTION=%s',
                $httpCode,
                $description !== '' ? $description : '-'
            ));

            return [
                'success' => false,
                'error' => $description !== '' ? $description : 'ارسال پیام بله ناموفق بود.',
                'response' => $data,
            ];
        }

        return ['success' => true, 'response' => $data];
    }
}
