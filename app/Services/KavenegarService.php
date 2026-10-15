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

    /**
     * ارسال OTP و برگرداندن نتیجه ساختاریافته برای AuthService.
     *
     * @return array{success:bool, status:?int, message:string, transport_error?:bool}
     */
    public function sendOtp(string $phone, string $code): array
    {
        // حالت توسعه: اگر API Key تنظیم نشده باشد پیامک واقعی ارسال نمی‌شود.
        if ($this->apiKey === '') {
            error_log("[KavenegarService] DEV MODE (بدون API key) - کد {$phone}: {$code}");
            return [
                'success' => true,
                'status' => 200,
                'message' => 'dev-mode',
            ];
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
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            error_log('[KavenegarService] خطای ارتباط با API: ' . $curlError);
            return [
                'success' => false,
                'status' => null,
                'message' => 'خطا در ارتباط با سرویس پیامک.',
                'transport_error' => true,
            ];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            error_log('[KavenegarService] پاسخ JSON نامعتبر از API. HTTP=' . $httpCode);
            return [
                'success' => false,
                'status' => null,
                'message' => 'پاسخ نامعتبر از سرویس پیامک.',
            ];
        }

        $status = isset($data['return']['status']) ? (int) $data['return']['status'] : null;
        $message = trim((string) ($data['return']['message'] ?? ''));

        if ($httpCode !== 200 || $status !== 200) {
            // عمداً کل response لاگ نمی‌شود تا receptor/token/message وارد لاگ نشوند.
            error_log(sprintf(
                '[KavenegarService] ارسال OTP ناموفق. HTTP=%d STATUS=%s MESSAGE=%s',
                $httpCode,
                $status === null ? 'null' : (string) $status,
                $message !== '' ? $message : '-'
            ));

            return [
                'success' => false,
                'status' => $status,
                'message' => $message !== '' ? $message : 'ارسال پیامک ناموفق بود.',
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'message' => $message !== '' ? $message : 'تایید شد',
        ];
    }
}
