<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class ArticleRepository
{
    /**
     * لیست صفحه‌بندی‌شده‌ی مقالات فعال، با فیلتر اختیاری روی دسته‌بندی.
     * @return array{items: array, total: int}
     */
    public function paginate(int $page, int $perPage, ?int $categoryId = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        try {
            $where = 'WHERE is_active = 1';
            $params = [];

            if ($categoryId !== null) {
                $where .= ' AND category = :category';
                $params['category'] = $categoryId;
            }

            $countStmt = Database::connection()->prepare(
                "SELECT COUNT(*) AS cnt FROM articles {$where}"
            );
            $countStmt->execute($params);
            $total = (int) ($countStmt->fetch()['cnt'] ?? 0);

            // LIMIT/OFFSET رو چون مستقیم عدد صحیح‌شده (int) هستن، امن می‌شه توی کوئری گذاشت
            $stmt = Database::connection()->prepare(
                "SELECT * FROM articles {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}"
            );
            $stmt->execute($params);
            $items = $stmt->fetchAll();

            return ['items' => $items, 'total' => $total];
        } catch (PDOException $e) {
            return ['items' => [], 'total' => 0];
        }
    }

    public function findBySlug(string $slug): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM articles WHERE slug = :slug AND is_active = 1 LIMIT 1'
            );
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * چند مقاله‌ی مرتبط از همون دسته‌بندی (برای پایین صفحه‌ی مقاله).
     */
    public function findRelated(int $categoryId, int $excludeId, int $limit = 4): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM articles
                 WHERE category = :category AND id != :exclude AND is_active = 1
                 ORDER BY created_at DESC LIMIT ' . max(1, $limit)
            );
            $stmt->execute(['category' => $categoryId, 'exclude' => $excludeId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
