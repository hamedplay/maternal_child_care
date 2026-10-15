<?php

namespace App\Services;

use App\Repositories\BaleChannelRepository;

class BaleLinkService
{
    private const TOKEN_TTL_SECONDS = 900;

    private BaleChannelRepository $repository;
    private BaleService $bale;

    public function __construct()
    {
        $this->repository = new BaleChannelRepository();
        $this->bale = new BaleService();
    }

    /** @return array{code:string,expires_at:string} */
    public function createCode(int $userId): array
    {
        $code = strtoupper(bin2hex(random_bytes(4)));
        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_TTL_SECONDS);
        $this->repository->createLinkToken($userId, hash('sha256', $code), $expiresAt);

        return ['code' => $code, 'expires_at' => $expiresAt];
    }

    /**
     * پردازش update استاندارد Bot API برای دستور /start CODE.
     */
    public function processWebhookUpdate(array $update): bool
    {
        $message = $update['message'] ?? null;
        if (!is_array($message)) {
            return false;
        }

        $text = trim((string) ($message['text'] ?? ''));
        $chatId = $message['chat']['id'] ?? null;
        if ($text === '' || $chatId === null) {
            return false;
        }

        if (!preg_match('/^\/start\s+([A-F0-9]{8})$/i', $text, $matches)) {
            return false;
        }

        $userId = $this->repository->consumeLinkToken(hash('sha256', strtoupper($matches[1])));
        if ($userId === null) {
            $this->bale->sendMessage((string) $chatId, 'کد اتصال نامعتبر یا منقضی شده است. از سایت یک کد جدید بگیرید.');
            return false;
        }

        $this->repository->connect($userId, (string) $chatId);
        $this->bale->sendMessage((string) $chatId, 'حساب بله با موفقیت به سامانه مراقبت مادر و کودک متصل شد. یادآوری‌های فعال از اینجا ارسال می‌شوند.');

        return true;
    }

    public function channelForUser(int $userId): ?array
    {
        return $this->repository->findByUserId($userId);
    }
}
