<?php

namespace App\Services;

use App\Repositories\OtpRepository;
use App\Repositories\UserRepository;

class AuthService
{
    private const CODE_LENGTH = 5;
    private const CODE_TTL_SECONDS = 300;          // اعتبار هر کد: ۵ دقیقه
    private const RESEND_COOLDOWN_SECONDS = 120;   // حداقل فاصله بین دو درخواست کد: ۲ دقیقه
    private const MAX_REQUESTS_PER_HOUR = 5;       // سقف درخواست کد برای هر شماره در هر ساعت
    private const MAX_VERIFY_ATTEMPTS = 5;         // سقف تلاش برای وارد کردن کد درست

    private OtpRepository $otpRepository;
    private UserRepository $userRepository;
    private KavenegarService $kavenegar;
    private string $pepper;

    public function __construct()
    {
        $this->otpRepository = new OtpRepository();
        $this->userRepository = new UserRepository();
        $this->kavenegar = new KavenegarService();

        $config = require BASE_PATH . '/config/services.php';
        $this->pepper = $config['otp_pepper'];
    }

    /**
     * درخواست ارسال کد تایید برای یک شماره.
     * @return array{success: bool, error?: string}
     */
    public function requestOtp(string $phone): array
    {
        if (!$this->isValidPhone($phone)) {
            return ['success' => false, 'error' => 'شماره موبایل معتبر نیست.'];
        }

        $recentCount = $this->otpRepository->countRecentForPhone($phone, 3600);
        if ($recentCount >= self::MAX_REQUESTS_PER_HOUR) {
            return ['success' => false, 'error' => 'تعداد درخواست‌های شما زیاد بوده. یک ساعت دیگه دوباره امتحان کنید.'];
        }

        $lastCreatedAt = $this->otpRepository->findLastCreatedAtForPhone($phone);
        if ($lastCreatedAt !== null && (time() - strtotime($lastCreatedAt)) < self::RESEND_COOLDOWN_SECONDS) {
            return ['success' => false, 'error' => 'لطفاً کمی صبر کنید و دوباره درخواست بدید.'];
        }

        $code = (string) random_int(10 ** (self::CODE_LENGTH - 1), (10 ** self::CODE_LENGTH) - 1);
        $codeHash = $this->hashCode($phone, $code);
        $expiresAt = date('Y-m-d H:i:s', time() + self::CODE_TTL_SECONDS);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        // کدهای فعال قبلیِ همین شماره رو باطل می‌کنیم تا فقط آخرین کد معتبر باشه
        $this->otpRepository->invalidateActiveForPhone($phone);
        $this->otpRepository->create($phone, $codeHash, $expiresAt, $ip);

        if (!$this->kavenegar->sendOtp($phone, $code)) {
            return ['success' => false, 'error' => 'ارسال پیامک با خطا مواجه شد. لطفاً دوباره تلاش کنید.'];
        }

        $result = ['success' => true];

        // فقط وقتی کاوه‌نگار هنوز وصل نیست، کد رو مستقیم برمی‌گردونیم تا بتونی تست کنی.
        // به‌محض تنظیم کلید API واقعی، این بخش خودش غیرفعال می‌شه.
        if (!$this->kavenegar->isConfigured()) {
            $result['debug_code'] = $code;
        }

        return $result;
    }

    /**
     * بررسی کد وارد‌شده و در صورت درست بودن، ورود/ثبت‌نام خودکار کاربر.
     * @return array{success: bool, error?: string, user?: array}
     */
    public function verifyOtp(string $phone, string $code): array
    {
        if (!$this->isValidPhone($phone) || !preg_match('/^\d{' . self::CODE_LENGTH . '}$/', $code)) {
            return ['success' => false, 'error' => 'اطلاعات ارسالی معتبر نیست.'];
        }

        $otpRow = $this->otpRepository->findLatestActiveForPhone($phone);
        if ($otpRow === null) {
            return ['success' => false, 'error' => 'کد منقضی شده یا نامعتبره. لطفاً دوباره درخواست بدید.'];
        }

        if ((int) $otpRow['attempts'] >= (int) $otpRow['max_attempts']) {
            return ['success' => false, 'error' => 'تعداد تلاش‌های مجاز تموم شده. یک کد جدید بگیرید.'];
        }

        $expectedHash = $this->hashCode($phone, $code);
        if (!hash_equals($otpRow['code_hash'], $expectedHash)) {
            $this->otpRepository->incrementAttempts((int) $otpRow['id']);
            return ['success' => false, 'error' => 'کد وارد‌شده صحیح نیست.'];
        }

        $this->otpRepository->markVerified((int) $otpRow['id']);

        $existingUser = $this->userRepository->findByPhone($phone);
        $isNew = ($existingUser === null);
        $user = $existingUser ?? $this->userRepository->create($phone);
        $this->userRepository->updateLastLogin((int) $user['id']);

        return ['success' => true, 'user' => $user, 'is_new' => $isNew];
    }

    /**
     * تکمیل ثبت‌نام: ذخیره‌ی اسم کاربری که تازه برای اولین بار وارد شده.
     * @return array{success: bool, error?: string, user?: array}
     */
    public function setName(int $userId, string $name): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            return ['success' => false, 'error' => 'لطفاً یه اسم معتبر وارد کنید.'];
        }

        if (!preg_match('/^[\p{L}\s]+$/u', $name)) {
            return ['success' => false, 'error' => 'اسم فقط باید شامل حروف باشه.'];
        }

        $this->userRepository->updateName($userId, $name);
        $user = $this->userRepository->findById($userId);

        return ['success' => true, 'user' => $user];
    }

    private function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^09\d{9}$/', $phone);
    }

    private function hashCode(string $phone, string $code): string
    {
        return hash_hmac('sha256', $phone . ':' . $code, $this->pepper);
    }
}