<?php

namespace App\Services;

/**
 * ارسال پیامک کد تایید از طریق سرویس Verify Lookup کاوه‌نگار.
 */
class KavenegarService
{
    private string $apiKey;
    private string $template;

    public function __construct()
    {
        $config = require BASE_PATH . '/config/services.php';
        $this->apiKey = $config['kavenegar']['api_key'];
        $this->template = $config['kavenegar']['otp_template'];
    }

    /**
     * آیا کلید واقعی کاوه‌نگار تنظیم شده؟
     */
    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function sendOtp(string $phone, string $code): bool
    {
        // حالت توسعه: اگر API Key تنظیم نشده باشد پیامک واقعی ارسال نمی‌شود.
        if ($this->apiKey === '') {
            error_log("[KavenegarService] DEV MODE (بدون API key) - کد {$phone}: {$code}");
            return true;
        }

        $url = sprintf(
            'https://api.kavenegar.com/v1/%s/verify/lookup.json?%s',
            $this->apiKey,
            http_build_query([
                'receptor' => $phone,
                'token' => $code,
                'template' => $this->template,
                'type' => 'sms',
            ])
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            error_log('[KavenegarService] خطای cURL: ' . $curlError);
            return false;
        }

        $data = json_decode($response, true);
        $status = $data['return']['status'] ?? null;

        if ($httpCode !== 200 || $status !== 200) {
            error_log('[KavenegarService] ارسال ناموفق: ' . $response);
            return false;
        }

        return true;
    }
}
