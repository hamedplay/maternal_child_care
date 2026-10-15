<?php

namespace App\Services;

use App\Repositories\ArticleRepository;
use App\Repositories\FaqRepository;
use App\Repositories\UserActivityRepository;
use App\Repositories\UserProfileRepository;

/**
 * Recommender ساده و قابل توضیح بر اساس Profile + رفتار اخیر.
 * هیچ مدل ML یا منبع داده خارج از دیتابیس پروژه استفاده نمی‌شود.
 */
class RecommendationService
{
    private UserProfileRepository $profileRepository;
    private UserActivityRepository $activityRepository;
    private ArticleRepository $articleRepository;
    private FaqRepository $faqRepository;

    public function __construct()
    {
        $this->profileRepository = new UserProfileRepository();
        $this->activityRepository = new UserActivityRepository();
        $this->articleRepository = new ArticleRepository();
        $this->faqRepository = new FaqRepository();
    }

    /**
     * @return array{articles:array,faqs:array,quick_links:array,reason:string}
     */
    public function forUser(int $userId): array
    {
        $profile = $this->profileRepository->findByUserId($userId) ?? [];
        $stage = (string) ($profile['stage'] ?? 'planning');
        $recent = $this->activityRepository->recentForUser($userId, 40);

        [$articleSlugs, $faqSlugs, $reason] = $this->categoriesForStage($stage);
        [$articleSlugs, $faqSlugs] = $this->applyRecentActivity($recent, $articleSlugs, $faqSlugs);

        return [
            'articles' => $this->articleRepository->findRecommendedByCategorySlugs($articleSlugs, 6),
            'faqs' => $this->faqRepository->findRecommendedByCategorySlugs($faqSlugs, 6),
            'quick_links' => $this->personalizedQuickLinks($profile),
            'reason' => $reason,
        ];
    }

    /** @return array{0:string[],1:string[],2:string} */
    private function categoriesForStage(string $stage): array
    {
        return match ($stage) {
            'pregnant' => [
                ['بارداری-در-سه-دوره', 'زندگی-در-دوران-بارداری', 'مراقبت-های-سلامتی', 'عوارض-بارداری', 'آمادگی-های-زایمان'],
                ['pregnancy', 'nutrition'],
                'بر اساس مرحله بارداری شما',
            ],
            'postpartum' => [
                ['مراقبت-های-پس-از-زایمان', 'تناسب-اندام-پس-از-زایمان', 'مراقبت-از-نوزاد', 'شیردهی-و-تغذیه-نوزاد'],
                ['child', 'nutrition'],
                'بر اساس مرحله پس از زایمان و مراقبت از نوزاد',
            ],
            'child_care' => [
                ['رشد-نوزاد', 'مراقبت-از-نوزاد', 'شیردهی-و-تغذیه-نوزاد', 'سبک-زندگی-با-نوزاد', 'راهنمای-سلامتی'],
                ['child', 'nutrition', 'vaccination', 'growth', 'development'],
                'بر اساس اطلاعات کودک و موضوعات مراقبتی مرتبط',
            ],
            default => [
                ['باروری', 'مراقبتهای-پیش-از-بارداری', 'مشکلات-باروری', 'پیش-از-بارداری', 'روش-های-تشخیص-بارداری'],
                ['pregnancy', 'general'],
                'بر اساس مرحله اقدام برای بارداری',
            ],
        };
    }

    /**
     * @param array<int,array<string,mixed>> $recent
     * @param string[] $articleSlugs
     * @param string[] $faqSlugs
     * @return array{0:string[],1:string[]}
     */
    private function applyRecentActivity(array $recent, array $articleSlugs, array $faqSlugs): array
    {
        foreach ($recent as $activity) {
            $path = strtolower((string) ($activity['request_path'] ?? ''));

            if (str_ends_with($path, '/vaccine.php') || str_ends_with($path, 'vaccine.php')) {
                array_unshift($faqSlugs, 'vaccination');
            } elseif (str_ends_with($path, '/growth.php') || str_ends_with($path, 'growth.php')) {
                array_unshift($faqSlugs, 'growth');
                array_unshift($articleSlugs, 'رشد-نوزاد');
            } elseif (str_ends_with($path, '/milestones.php') || str_ends_with($path, 'milestones.php')) {
                array_unshift($faqSlugs, 'development');
                array_unshift($articleSlugs, 'رشد-نوزاد');
            } elseif (str_ends_with($path, '/child-nutrition.php') || str_ends_with($path, 'child-nutrition.php')) {
                array_unshift($faqSlugs, 'nutrition');
                array_unshift($articleSlugs, 'شیردهی-و-تغذیه-نوزاد');
            } elseif (str_contains($path, 'pregnancy-')) {
                array_unshift($faqSlugs, 'pregnancy');
                array_unshift($articleSlugs, 'زندگی-در-دوران-بارداری');
            }
        }

        return [array_values(array_unique($articleSlugs)), array_values(array_unique($faqSlugs))];
    }

    /** @return array<int,array{label:string,url:string,description:string}> */
    private function personalizedQuickLinks(array $profile): array
    {
        $links = [];
        $stage = (string) ($profile['stage'] ?? 'planning');

        if ($stage === 'pregnant' && !empty($profile['pregnancy_due_date'])) {
            $week = $this->pregnancyWeek((string) $profile['pregnancy_due_date']);
            if ($week !== null) {
                $links[] = [
                    'label' => 'هفته ' . $week . ' بارداری',
                    'url' => 'pregnancy-weeks.php?week=' . $week,
                    'description' => 'رفتن مستقیم به محتوای هفته فعلی بر اساس تاریخ تقریبی زایمان.',
                ];
            }
        }

        if (in_array($stage, ['postpartum', 'child_care'], true) && !empty($profile['child_birth_date'])) {
            $ageMonths = $this->childAgeMonths((string) $profile['child_birth_date']);
            if ($ageMonths !== null) {
                $gender = (string) ($profile['child_gender'] ?? 'unknown');
                $growthUrl = 'growth.php?age=' . $ageMonths;
                if (in_array($gender, ['boy', 'girl'], true)) {
                    $growthUrl .= '&gender=' . $gender;
                }

                $links[] = [
                    'label' => 'رشد در ' . $ageMonths . ' ماهگی',
                    'url' => $growthUrl,
                    'description' => 'نمایش اطلاعات رشد متناسب با سن فعلی کودک.',
                ];
                $links[] = [
                    'label' => 'تغذیه در ' . $ageMonths . ' ماهگی',
                    'url' => 'child-nutrition.php?age=' . $ageMonths,
                    'description' => 'راهنمای تغذیه و مکمل‌های مرتبط با سن فعلی.',
                ];
                $links[] = [
                    'label' => 'تکامل در ' . $ageMonths . ' ماهگی',
                    'url' => 'milestones.php?age=' . $ageMonths,
                    'description' => 'مرور مراحل تکاملی مرتبط با سن کودک.',
                ];
                $links[] = [
                    'label' => 'واکسن در ' . $ageMonths . ' ماهگی',
                    'url' => 'vaccine.php?age=' . $ageMonths,
                    'description' => 'بررسی واکسن‌های مرتبط با سن فعلی کودک.',
                ];
            }
        }

        return $links;
    }

    private function pregnancyWeek(string $dueDate): ?int
    {
        try {
            $due = new \DateTimeImmutable($dueDate);
            $today = new \DateTimeImmutable('today');
            $conceptionReference = $due->modify('-280 days');
            $days = (int) $conceptionReference->diff($today)->format('%r%a');
            $week = intdiv(max(0, $days), 7) + 1;

            return ($week >= 1 && $week <= 40) ? $week : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function childAgeMonths(string $birthDate): ?int
    {
        try {
            $birth = new \DateTimeImmutable($birthDate);
            $today = new \DateTimeImmutable('today');
            if ($birth > $today) {
                return null;
            }

            $diff = $birth->diff($today);
            return ($diff->y * 12) + $diff->m;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
