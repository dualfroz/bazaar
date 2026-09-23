<?php

namespace Bazaar\Repositories;

use Bazaar\Core\Database;

final class ProductRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM products WHERE id = ?', [$id]);
    }

    public function findActive(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM products WHERE id = ? AND isActive = 1', [$id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->selectOne(
            'SELECT p.*, c.name AS categoryName, c.slug AS categorySlug
             FROM products p
             LEFT JOIN categories c ON c.id = p.categoryId
             WHERE p.slug = ? AND p.isActive = 1',
            [$slug]
        );
    }

    public function featured(int $limit = 6): array
    {
        return $this->db->select(
            'SELECT p.*, c.name AS categoryName, c.slug AS categorySlug
             FROM products p
             LEFT JOIN categories c ON c.id = p.categoryId
             WHERE p.isActive = 1
             ORDER BY p.createdAt DESC, p.id DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function allForAdmin(): array
    {
        return $this->db->select(
            'SELECT p.*, c.name AS categoryName
             FROM products p
             LEFT JOIN categories c ON c.id = p.categoryId
             ORDER BY p.createdAt DESC, p.id DESC'
        );
    }

    public function create(array $fields): int
    {
        return $this->db->insert(
            'INSERT INTO products
                (categoryId, name, slug, description, priceCents, stockQuantity, imageUrl, isActive, createdAt)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $fields['categoryId'],
                $fields['name'],
                $fields['slug'],
                $fields['description'],
                $fields['priceCents'],
                $fields['stockQuantity'],
                $fields['imageUrl'],
                $fields['isActive'] ? 1 : 0,
                gmdate('c'),
            ]
        );
    }

    public function update(int $id, array $fields): void
    {
        $this->db->statement(
            'UPDATE products SET
                categoryId = ?, name = ?, slug = ?, description = ?,
                priceCents = ?, stockQuantity = ?, imageUrl = ?, isActive = ?
             WHERE id = ?',
            [
                $fields['categoryId'],
                $fields['name'],
                $fields['slug'],
                $fields['description'],
                $fields['priceCents'],
                $fields['stockQuantity'],
                $fields['imageUrl'],
                $fields['isActive'] ? 1 : 0,
                $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->db->statement('DELETE FROM products WHERE id = ?', [$id]);
    }

    public function count(): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM products');
    }

    public function lowStock(int $threshold = 5): array
    {
        return $this->db->select(
            'SELECT * FROM products WHERE stockQuantity <= ? ORDER BY stockQuantity ASC',
            [$threshold]
        );
    }

    public function decrementStock(int $id, int $quantity): void
    {
        $this->db->statement(
            'UPDATE products SET stockQuantity = stockQuantity - ? WHERE id = ?',
            [$quantity, $id]
        );
    }

    public function incrementStock(int $id, int $quantity): void
    {
        $this->db->statement(
            'UPDATE products SET stockQuantity = stockQuantity + ? WHERE id = ?',
            [$quantity, $id]
        );
    }
}
