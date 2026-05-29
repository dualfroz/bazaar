<?php

namespace Bazaar\Repositories;

use Bazaar\Core\Database;

final class ReviewRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function create(int $productId, int $userId, int $rating, string $title, string $body): int
    {
        return $this->db->insert(
            'INSERT INTO reviews (productId, userId, rating, title, body, status, createdAt)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$productId, $userId, $rating, $title, $body, 'pending', gmdate('c')]
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM reviews WHERE id = ?', [$id]);
    }

    public function existsForUser(int $productId, int $userId): bool
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM reviews WHERE productId = ? AND userId = ?',
            [$productId, $userId]
        ) > 0;
    }

    public function approvedForProduct(int $productId): array
    {
        return $this->db->select(
            'SELECT r.*, u.name AS authorName
             FROM reviews r
             JOIN users u ON u.id = r.userId
             WHERE r.productId = ? AND r.status = ?
             ORDER BY r.createdAt DESC, r.id DESC',
            [$productId, 'approved']
        );
    }

    public function summary(int $productId): array
    {
        $row = $this->db->selectOne(
            'SELECT COUNT(*) AS reviewCount, COALESCE(AVG(rating), 0) AS average
             FROM reviews WHERE productId = ? AND status = ?',
            [$productId, 'approved']
        );
        return [
            'count' => (int) $row['reviewCount'],
            'average' => round((float) $row['average'], 2),
        ];
    }

    public function summaries(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }
        $placeholders = implode(', ', array_fill(0, count($productIds), '?'));
        $rows = $this->db->select(
            "SELECT productId, COUNT(*) AS reviewCount, COALESCE(AVG(rating), 0) AS average
             FROM reviews
             WHERE status = 'approved' AND productId IN ({$placeholders})
             GROUP BY productId",
            $productIds
        );
        $byProduct = [];
        foreach ($rows as $row) {
            $byProduct[(int) $row['productId']] = [
                'count' => (int) $row['reviewCount'],
                'average' => round((float) $row['average'], 2),
            ];
        }
        return $byProduct;
    }

    public function moderationQueue(string $status = 'pending'): array
    {
        return $this->db->select(
            'SELECT r.*, u.name AS authorName, u.email AS authorEmail, p.name AS productName, p.slug AS productSlug
             FROM reviews r
             JOIN users u ON u.id = r.userId
             JOIN products p ON p.id = r.productId
             WHERE r.status = ?
             ORDER BY r.createdAt ASC, r.id ASC',
            [$status]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->statement(
            'UPDATE reviews SET status = ?, moderatedAt = ? WHERE id = ?',
            [$status, gmdate('c'), $id]
        );
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM reviews WHERE status = ?', [$status]);
    }
}
