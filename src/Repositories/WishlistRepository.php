<?php

namespace Bazaar\Repositories;

use Bazaar\Core\Database;

final class WishlistRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function has(int $userId, int $productId): bool
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM wishlistItems WHERE userId = ? AND productId = ?',
            [$userId, $productId]
        ) > 0;
    }

    public function add(int $userId, int $productId): void
    {
        $this->db->statement(
            'INSERT INTO wishlistItems (userId, productId, createdAt)
             VALUES (?, ?, ?)
             ON CONFLICT(userId, productId) DO NOTHING',
            [$userId, $productId, gmdate('c')]
        );
    }

    public function remove(int $userId, int $productId): void
    {
        $this->db->statement(
            'DELETE FROM wishlistItems WHERE userId = ? AND productId = ?',
            [$userId, $productId]
        );
    }

    public function toggle(int $userId, int $productId): bool
    {
        if ($this->has($userId, $productId)) {
            $this->remove($userId, $productId);
            return false;
        }
        $this->add($userId, $productId);
        return true;
    }

    public function forUser(int $userId): array
    {
        return $this->db->select(
            'SELECT p.*, c.name AS categoryName, c.slug AS categorySlug, w.createdAt AS savedAt
             FROM wishlistItems w
             JOIN products p ON p.id = w.productId
             LEFT JOIN categories c ON c.id = p.categoryId
             WHERE w.userId = ?
             ORDER BY w.createdAt DESC, w.id DESC',
            [$userId]
        );
    }

    public function productIdsForUser(int $userId): array
    {
        return array_map(
            'intval',
            array_column(
                $this->db->select('SELECT productId FROM wishlistItems WHERE userId = ?', [$userId]),
                'productId'
            )
        );
    }

    public function count(int $userId): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM wishlistItems WHERE userId = ?', [$userId]);
    }
}
