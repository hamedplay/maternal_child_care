<?php

namespace App\Repositories;

/**
 * فعلاً داده‌ها به‌صورت ثابت (hard-coded) برگردونده می‌شن.
 * بعداً که دیتابیس اضافه شد، فقط بدنه‌ی متدهای این کلاس عوض می‌شه
 * و بقیه‌ی پروژه (index.php، templates) دست‌نخورده باقی می‌مونه.
 */
class FeatureCardRepository
{
    /**
     * کارت‌های ویژگی صفحه‌ی خانه
     *
     * @return array<int, array{icon:string, title:string, description:string}>
     */
    public function getHomeCards(): array
    {
        return [
            [
                'icon' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="8" width="16" height="12" rx="2" /><path d="M12 8V4" /><circle cx="12" cy="2.5" r="1.3" /><circle cx="9" cy="13.5" r="1" fill="currentColor" stroke="none" /><circle cx="15" cy="13.5" r="1" fill="currentColor" stroke="none" /><path d="M9 17h6" /><path d="M2 12v2" /><path d="M22 12v2" /></svg>',
                'title' => 'پرسش و پاسخ هوشمند',
                'description' => 'مشاوره با هوش مصنوعی',
            ],
            [
                'icon' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18" /><path d="m19 9-5 5-4-4-4 4" /></svg>',
                'title' => 'رشد و پیشرفت کودک',
                'description' => 'رصد سلامت و رشد',
            ],
            [
                'icon' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m18 2 4 4" /><path d="m17 7 3-3" /><path d="M19 9 8.7 19.3c-1 1-2.5 1-3.4 0l-.6-.6c-1-1-1-2.5 0-3.4L15 5" /><path d="m9 11 4 4" /><path d="m5 19-3 3" /><path d="m14 4 6 6" /></svg>',
                'title' => 'زمان‌بندی واکسن',
                'description' => 'یادآوری و اطلاع‌رسانی',
            ],
            [
                'icon' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4h6a4 4 0 0 1 4 4v12a3 3 0 0 0-3-3H2z" /><path d="M22 4h-6a4 4 0 0 0-4 4v12a3 3 0 0 1 3-3h7z" /></svg>',
                'title' => 'مقالات آموزشی',
                'description' => 'اطلاعات معتبر و کاربردی',
            ],
        ];
    }
}
