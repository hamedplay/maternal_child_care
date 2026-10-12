<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

/**
 * ریپازیتوری جدول pregnancy_warning_signs.
 * مسئول واکشی علائم خطر دوران بارداری، مرتب‌شده بر اساس درجه‌ی اورژانسی بودن.
 */
class WarningSignRepository
{
    /**
     * ترتیب و لیبل فارسی سطوح اورژانسی (از شدیدترین به خفیف‌ترین).
     */
    public const URGENCY_LABELS = [
        'emergency'     => 'اورژانسی',
        'urgent'        => 'فوری',
        'important'     => 'مهم',
        'informational' => 'طبیعی / اطلاع‌رسانی',
    ];

    /**
     * لیبل فارسی دسته‌بندی علائم.
     */
    public const CATEGORY_LABELS = [
        'bleeding'         => 'خونریزی',
        'pain'             => 'درد',
        'headache_vision'  => 'سردرد و مشکلات بینایی',
        'swelling'         => 'تورم',
        'fever'            => 'تب',
        'digestive'        => 'گوارشی',
        'movement'         => 'حرکات جنین',
        'urinary'          => 'ادراری',
        'respiratory'      => 'تنفسی',
        'psychological'    => 'روانی',
        'other'            => 'سایر',
    ];

    /**
     * ترتیب مرتب‌سازی سطوح اورژانسی برای استفاده در ORDER BY FIELD().
     */
    private const URGENCY_ORDER = "FIELD(urgency_level, 'emergency', 'urgent', 'important', 'informational')";

    /**
     * همه‌ی علائم، از اورژانسی‌ترین به خفیف‌ترین.
     */
    public function all(): array
    {
        try {
            $stmt = Database::connection()->query(
                'SELECT * FROM pregnancy_warning_signs ORDER BY ' . self::URGENCY_ORDER . ', id ASC'
            );

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * علائم مرتبط با یک سطح اورژانسی خاص.
     * ورودی نامعتبر → همه‌ی رکوردها برگردونده می‌شه.
     */
    public function findByUrgency(string $urgencyLevel): array
    {
        if (!array_key_exists($urgencyLevel, self::URGENCY_LABELS)) {
            return $this->all();
        }

        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_warning_signs
                 WHERE urgency_level = :urgency
                 ORDER BY ' . self::URGENCY_ORDER . ', id ASC'
            );
            $stmt->execute(['urgency' => $urgencyLevel]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * علائم مرتبط با یک هفته‌ی مشخص از بارداری.
     */
    public function findRelevantForWeek(int $week): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_warning_signs
                 WHERE (week_start IS NULL OR week_start <= :week)
                   AND (week_end IS NULL OR week_end >= :week)
                 ORDER BY ' . self::URGENCY_ORDER . ', id ASC'
            );
            $stmt->execute(['week' => $week]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * یک علامت خطر با شناسه‌ی مشخص.
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM pregnancy_warning_signs WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
