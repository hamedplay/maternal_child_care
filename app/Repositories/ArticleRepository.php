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

    /**
     * مقالات فعال در دسته‌های واقعی دیتابیس برای Recommender.
     * @param string[] $categorySlugs
     */
    public function findRecommendedByCategorySlugs(array $categorySlugs, int $limit = 6): array
    {
        $categorySlugs = array_values(array_unique(array_filter(array_map('strval', $categorySlugs))));
        if ($categorySlugs === []) {
            return [];
        }

        $limit = max(1, min($limit, 20));
        $placeholders = [];
        $params = [];
        foreach ($categorySlugs as $i => $slug) {
            $key = 'slug_' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $slug;
        }

        try {
            $stmt = Database::connection()->prepare(
                'SELECT a.id, a.title, a.slug, a.cover_image, a.content,
                        c.name AS category_name, c.slug AS category_slug
                 FROM articles a
                 INNER JOIN categories c ON c.id = a.category AND c.type = \'article\'
                 WHERE a.is_active = 1
                   AND c.slug IN (' . implode(', ', $placeholders) . ')
                 ORDER BY a.updated_at DESC, a.id DESC
                 LIMIT ' . $limit
            );
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * داده خام لازم برای ابزار پاک‌سازی Article HTML.
     * @return array<int,array{id:int,title:string,content:string}>
     */
    public function allForContentCleanup(): array
    {
        try {
            return Database::connection()->query(
                'SELECT id, title, content FROM articles ORDER BY id'
            )->fetchAll();
        } catch (PDOException $e) {
            error_log('ArticleRepository::allForContentCleanup خطا: ' . $e->getMessage());
            return [];
        }
    }

    public function updateContent(int $id, string $content): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE articles SET content = :content, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'content' => $content]);
    }
}
