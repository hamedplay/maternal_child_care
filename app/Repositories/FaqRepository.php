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

    /**
     * FAQهای فعال در دسته‌های واقعی دیتابیس برای Personalization.
     * @param string[] $categorySlugs
     */
    public function findRecommendedByCategorySlugs(array $categorySlugs, int $limit = 6): array
    {
        $categorySlugs = array_values(array_unique(array_filter(array_map('strval', $categorySlugs))));
        if ($categorySlugs === []) {
            return $this->getFeatured($limit);
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
                'SELECT f.id, f.category, f.question, f.answer, c.name AS category_name, c.slug AS category_slug
                 FROM faqs f
                 INNER JOIN categories c ON c.id = f.category AND c.type = \'faq\'
                 WHERE f.is_active = 1
                   AND c.slug IN (' . implode(', ', $placeholders) . ')
                 ORDER BY f.helpful_count DESC, f.view_count DESC, f.`order` ASC, f.id ASC
                 LIMIT ' . $limit
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            return $rows !== [] ? $rows : $this->getFeatured($limit);
        } catch (PDOException $e) {
            return $this->getFeatured($limit);
        }
    }
}
