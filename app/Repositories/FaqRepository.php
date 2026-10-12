<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class FaqRepository
{
    /**
     * چند تا از سوالات پرتکرار رو برای تیزر صفحه‌ی اصلی برمی‌گردونه.
     *
     * @return array<int, array{id:int, category:?string, question:string, answer:string}>
     */
    public function getFeatured(int $limit = 4): array
    {
        try {
            // نکته: `order` یه کلمه‌ی رزروشده تو MySQL هست، به همین خاطر
            // تو کوئری داخل بک‌تیک (`order`) گذاشته شده.
            $stmt = Database::connection()->prepare(
                'SELECT id, category, question, answer
                 FROM faqs
                 WHERE is_active = 1
                 ORDER BY `order` ASC, id ASC
                 LIMIT :limit'
            );
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('FaqRepository::getFeatured خطا: ' . $e->getMessage());
            return [];
        }
    }
}
