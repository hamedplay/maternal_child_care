<?php

namespace App\Repositories;

use App\Core\Database;
use PDOException;

class CategoryRepository
{
    public function all(string $type = 'article'): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM categories WHERE type = :type ORDER BY display_order ASC, name ASC'
            );
            $stmt->execute(['type' => $type]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function findBySlug(string $slug): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM categories WHERE slug = :slug LIMIT 1'
            );
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM categories WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
