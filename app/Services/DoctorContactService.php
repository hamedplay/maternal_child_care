<?php

namespace App\Services;

use App\Repositories\DoctorMessageRepository;
use Throwable;

class DoctorContactService
{
    private DoctorMessageRepository $repository;
    private UserActivityService $activityService;

    public function __construct()
    {
        $this->repository = new DoctorMessageRepository();
        $this->activityService = new UserActivityService();
    }

    /** @return array{success:bool,error?:string,id?:int} */
    public function submit(int $userId, array $input): array
    {
        $subject = trim((string) ($input['subject'] ?? ''));
        $message = trim((string) ($input['message'] ?? ''));

        if (mb_strlen($subject) < 3 || mb_strlen($subject) > 150) {
            return ['success' => false, 'error' => 'موضوع پیام باید بین ۳ تا ۱۵۰ کاراکتر باشد.'];
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
            return ['success' => false, 'error' => 'متن پیام باید بین ۱۰ تا ۵۰۰۰ کاراکتر باشد.'];
        }

        try {
            $id = $this->repository->create($userId, $subject, $message);
            $this->activityService->record($userId, 'doctor_message_created', 'doctor_message', (string) $id);
            return ['success' => true, 'id' => $id];
        } catch (Throwable $e) {
            error_log('DoctorContactService::submit خطا: ' . $e->getMessage());
            return ['success' => false, 'error' => 'ثبت پیام انجام نشد. Migration مربوط به ارتباط با پزشک باید روی دیتابیس اجرا شده باشد.'];
        }
    }

    public function history(int $userId): array
    {
        return $this->repository->recentForUser($userId);
    }
}
