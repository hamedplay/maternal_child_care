<?php

namespace App\Services;

use App\Repositories\ReminderRepository;
use Throwable;

class ReminderService
{
    private const TYPES = ['appointment', 'medication'];

    private ReminderRepository $repository;
    private UserActivityService $activityService;

    public function __construct()
    {
        $this->repository = new ReminderRepository();
        $this->activityService = new UserActivityService();
    }

    /** @return array{success:bool,error?:string,id?:int} */
    public function create(int $userId, array $input): array
    {
        $type = (string) ($input['reminder_type'] ?? '');
        $title = trim((string) ($input['title'] ?? ''));
        $notes = trim((string) ($input['notes'] ?? ''));
        $eventAt = $this->parseLocalDateTime($input['event_at'] ?? null);
        $remindAt = $this->parseLocalDateTime($input['remind_at'] ?? null);

        if (!in_array($type, self::TYPES, true)) {
            return ['success' => false, 'error' => 'نوع یادآوری معتبر نیست.'];
        }
        if (mb_strlen($title) < 2 || mb_strlen($title) > 180) {
            return ['success' => false, 'error' => 'عنوان یادآوری معتبر نیست.'];
        }
        if ($notes !== '' && mb_strlen($notes) > 3000) {
            return ['success' => false, 'error' => 'یادداشت بیش از حد طولانی است.'];
        }
        if ($eventAt === null || $remindAt === null) {
            return ['success' => false, 'error' => 'تاریخ و زمان رویداد و یادآوری را کامل وارد کنید.'];
        }
        if ($remindAt > $eventAt) {
            return ['success' => false, 'error' => 'زمان یادآوری نمی‌تواند بعد از زمان رویداد باشد.'];
        }
        if ($eventAt <= new \DateTimeImmutable('now')) {
            return ['success' => false, 'error' => 'زمان رویداد باید در آینده باشد.'];
        }

        try {
            $id = $this->repository->create($userId, [
                'reminder_type' => $type,
                'title' => $title,
                'notes' => $notes !== '' ? $notes : null,
                'event_at' => $eventAt->format('Y-m-d H:i:s'),
                'remind_at' => $remindAt->format('Y-m-d H:i:s'),
            ]);
            $this->activityService->record($userId, 'reminder_created', 'reminder', (string) $id, ['type' => $type]);
            return ['success' => true, 'id' => $id];
        } catch (Throwable $e) {
            error_log('ReminderService::create خطا: ' . $e->getMessage());
            return ['success' => false, 'error' => 'ثبت یادآوری انجام نشد. Migration یادآوری باید روی دیتابیس اجرا شده باشد.'];
        }
    }

    public function history(int $userId): array
    {
        return $this->repository->recentForUser($userId);
    }

    public function cancel(int $userId, int $reminderId): bool
    {
        $cancelled = $this->repository->cancelForUser($reminderId, $userId);
        if ($cancelled) {
            $this->activityService->record($userId, 'reminder_cancelled', 'reminder', (string) $reminderId);
        }
        return $cancelled;
    }

    private function parseLocalDateTime(mixed $value): ?\DateTimeImmutable
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        $valid = $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));

        return $valid ? $date : null;
    }
}
