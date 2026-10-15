<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use Throwable;

class UserProfileService
{
    private const STAGES = ['planning', 'pregnant', 'postpartum', 'child_care'];
    private const GENDERS = ['boy', 'girl', 'unknown'];

    private UserRepository $userRepository;
    private UserProfileRepository $profileRepository;
    private UserActivityService $activityService;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->profileRepository = new UserProfileRepository();
        $this->activityService = new UserActivityService();
    }

    public function get(int $userId): array
    {
        return [
            'user' => $this->userRepository->findById($userId),
            'profile' => $this->profileRepository->findByUserId($userId),
        ];
    }

    /** @return array{success:bool,error?:string,user?:array,profile?:array} */
    public function save(int $userId, array $input): array
    {
        $name = trim((string) ($input['full_name'] ?? ''));
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 150 || !preg_match('/^[\p{L}\s]+$/u', $name)) {
            return ['success' => false, 'error' => 'نام و نام خانوادگی معتبر وارد کنید.'];
        }

        $stage = (string) ($input['stage'] ?? 'planning');
        if (!in_array($stage, self::STAGES, true)) {
            return ['success' => false, 'error' => 'مرحله انتخاب‌شده معتبر نیست.'];
        }

        $gender = (string) ($input['child_gender'] ?? 'unknown');
        if (!in_array($gender, self::GENDERS, true)) {
            return ['success' => false, 'error' => 'جنسیت کودک معتبر نیست.'];
        }

        $birthDate = $this->nullableDate($input['birth_date'] ?? null);
        $dueDate = $this->nullableDate($input['pregnancy_due_date'] ?? null);
        $childBirthDate = $this->nullableDate($input['child_birth_date'] ?? null);

        if ($birthDate === false || $dueDate === false || $childBirthDate === false) {
            return ['success' => false, 'error' => 'یکی از تاریخ‌های واردشده معتبر نیست.'];
        }

        $childName = trim((string) ($input['child_name'] ?? ''));
        $childName = $childName === '' ? null : (preg_replace('/\s+/u', ' ', $childName) ?? $childName);
        if ($childName !== null && (mb_strlen($childName) > 100 || !preg_match('/^[\p{L}\s]+$/u', $childName))) {
            return ['success' => false, 'error' => 'نام کودک معتبر نیست.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $this->userRepository->updateName($userId, $name);
            $this->profileRepository->upsert($userId, [
                'stage' => $stage,
                'birth_date' => $birthDate,
                'pregnancy_due_date' => $dueDate,
                'child_name' => $childName,
                'child_birth_date' => $childBirthDate,
                'child_gender' => $gender,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('UserProfileService::save خطا: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'ذخیره پروفایل انجام نشد. اگر سامانه تازه به‌روزرسانی شده، Migration پروفایل باید روی دیتابیس اجرا شود.',
            ];
        }

        $_SESSION['user_full_name'] = $name;
        $this->activityService->record($userId, 'profile_updated', 'user_profile', (string) $userId);

        return [
            'success' => true,
            'user' => $this->userRepository->findById($userId),
            'profile' => $this->profileRepository->findByUserId($userId),
        ];
    }

    /** @return string|false|null */
    private function nullableDate(mixed $value): string|false|null
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        $valid = $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value;

        return $valid ? $value : false;
    }
}
