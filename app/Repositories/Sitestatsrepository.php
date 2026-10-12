<?php

namespace App\Repositories;

/**
 * آمار نوار اعتمادسازی صفحه‌ی اصلی.
 * فقط pregnancy_weeks الان واقعاً به دیتابیس وصله (از PregnancyWeekRepository میاد).
 * بقیه‌ی اعداد، از داکیومنت وضعیت جدول‌ها (تعداد رکورد ✅ کامل) گرفته شدن و
 * وقتی repository اون جدول‌ها هم ساخته شد، باید همون‌جا با COUNT(*) واقعی جایگزین بشن.
 */
class SiteStatsRepository
{
    private PregnancyWeekRepository $pregnancyWeekRepository;

    public function __construct(?PregnancyWeekRepository $pregnancyWeekRepository = null)
    {
        $this->pregnancyWeekRepository = $pregnancyWeekRepository ?? new PregnancyWeekRepository();
    }

    /**
     * @return array<int, array{value:int, label:string}>
     */
    public function getHomeStats(): array
    {
        return [
            [
                'value' => $this->pregnancyWeekRepository->totalWeeks(),
                'label' => 'هفته راهنمای کامل بارداری',
            ],
            [
                // TODO: وقتی VaccinationScheduleRepository ساخته شد، از COUNT(*) واقعی بیاد
                'value' => 9,
                'label' => 'واکسن استاندارد کشوری',
            ],
            [
                // TODO: وقتی GrowthStandardRepository ساخته شد، از COUNT(*) واقعی بیاد
                'value' => 26,
                'label' => 'استاندارد رشد WHO',
            ],
            [
                // TODO: وقتی DevelopmentMilestoneRepository ساخته شد، از COUNT(*) واقعی بیاد
                'value' => 37,
                'label' => 'مرحله تکاملی کودک',
            ],
        ];
    }
}