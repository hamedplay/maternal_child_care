<?php

namespace App\Services;

use App\Repositories\UserActivityRepository;

/**
 * ثبت سبک Requestهای کاربر لاگین‌شده برای Personalization/History.
 * محتوای فرم، OTP، query string و payload خام عمداً ذخیره نمی‌شوند.
 */
class UserActivityService
{
    private UserActivityRepository $repository;

    public function __construct(?UserActivityRepository $repository = null)
    {
        $this->repository = $repository ?? new UserActivityRepository();
    }

    public function captureCurrentRequest(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : null;

        $actionType = $method === 'GET' ? 'page_view' : 'request';

        $this->repository->create(
            $userId,
            $actionType,
            $method,
            $path
        );
    }

    public function record(
        int $userId,
        string $actionType,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $metadata = null
    ): void {
        $this->repository->create(
            $userId,
            $actionType,
            isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : null,
            isset($_SERVER['REQUEST_URI']) ? (string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) : null,
            $entityType,
            $entityId,
            $metadata
        );
    }
}
