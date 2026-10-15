<?php

namespace App\Services;

use App\Repositories\AdminRepository;
use Throwable;

class AdminService
{
    private const SUPPORT_STATUSES = ['pending', 'in_progress', 'resolved'];

    private AdminRepository $repository;
    private UserActivityService $activityService;

    public function __construct()
    {
        $this->repository = new AdminRepository();
        $this->activityService = new UserActivityService();
    }

    public function isAdmin(int $userId): bool
    {
        return $this->repository->isAdmin($userId);
    }

    public function dashboard(): array
    {
        return [
            'counts' => $this->repository->dashboardCounts(),
            'users' => $this->repository->latestUsers(),
            'doctor_messages' => $this->repository->doctorMessages(),
            'support_messages' => $this->repository->supportMessages(),
            'reminders' => $this->repository->reminders(),
            'ai_messages' => $this->repository->aiMessages(),
        ];
    }

    /** @return array{success:bool,error?:string} */
    public function replyDoctor(int $adminUserId, int $messageId, string $reply): array
    {
        $reply = trim($reply);
        if ($messageId <= 0 || mb_strlen($reply) < 2 || mb_strlen($reply) > 5000) {
            return ['success' => false, 'error' => 'متن پاسخ معتبر نیست.'];
        }

        try {
            if (!$this->repository->replyDoctorMessage($messageId, $reply)) {
                return ['success' => false, 'error' => 'پیام پزشک پیدا نشد یا به‌روزرسانی نشد.'];
            }
            $this->activityService->record($adminUserId, 'admin_doctor_reply', 'doctor_message', (string) $messageId);
            return ['success' => true];
        } catch (Throwable $e) {
            error_log('AdminService::replyDoctor خطا: ' . $e->getMessage());
            return ['success' => false, 'error' => 'ثبت پاسخ پزشک انجام نشد.'];
        }
    }

    /** @return array{success:bool,error?:string} */
    public function markDoctorReviewed(int $adminUserId, int $messageId): array
    {
        try {
            $changed = $messageId > 0 && $this->repository->markDoctorReviewed($messageId);
            if (!$changed) {
                return ['success' => false, 'error' => 'وضعیت پیام تغییر نکرد.'];
            }
            $this->activityService->record($adminUserId, 'admin_doctor_reviewed', 'doctor_message', (string) $messageId);
            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'تغییر وضعیت پیام انجام نشد.'];
        }
    }

    /** @return array{success:bool,error?:string} */
    public function updateSupportStatus(int $adminUserId, int $messageId, string $status): array
    {
        if ($messageId <= 0 || !in_array($status, self::SUPPORT_STATUSES, true)) {
            return ['success' => false, 'error' => 'وضعیت پشتیبانی معتبر نیست.'];
        }

        try {
            if (!$this->repository->updateSupportStatus($messageId, $status)) {
                return ['success' => false, 'error' => 'پیام پشتیبانی پیدا نشد یا تغییری نداشت.'];
            }
            $this->activityService->record(
                $adminUserId,
                'admin_support_status_changed',
                'support_message',
                (string) $messageId,
                ['status' => $status]
            );
            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'تغییر وضعیت پشتیبانی انجام نشد.'];
        }
    }
}
