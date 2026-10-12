<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class TestimonialRepository
{
    /**
     * چند تا از نظرات تاییدشده رو برای نمایش در صفحه‌ی اصلی برمی‌گردونه.
     *
     * @return array<int, array{id:int, full_name:string, role_or_context:?string,
     *                          content:string, rating:?int}>
     */
    public function getFeatured(int $limit = 3): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT id, full_name, role_or_context, content, rating
                 FROM testimonials
                 WHERE is_active = 1
                 ORDER BY display_order ASC, id DESC
                 LIMIT :limit'
            );
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('TestimonialRepository::getFeatured خطا: ' . $e->getMessage());
            return [];
        }
    }
}
