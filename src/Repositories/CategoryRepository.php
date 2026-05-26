<?php

namespace Bazaar\Repositories;

use Bazaar\Core\Database;

final class CategoryRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->select('SELECT * FROM categories ORDER BY name ASC');
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM categories WHERE id = ?', [$id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->selectOne('SELECT * FROM categories WHERE slug = ?', [$slug]);
    }

    public function create(string $name, string $slug, string $description): int
    {
        return $this->db->insert(
            'INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)',
            [$name, $slug, $description]
        );
    }

    public function update(int $id, string $name, string $slug, string $description): void
    {
        $this->db->statement(
            'UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?',
            [$name, $slug, $description, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->db->statement('DELETE FROM categories WHERE id = ?', [$id]);
    }

    public function withProductCounts(): array
    {
        return $this->db->select(
            'SELECT c.*, COUNT(p.id) AS productCount
             FROM categories c
             LEFT JOIN products p ON p.categoryId = c.id
             GROUP BY c.id
             ORDER BY c.name ASC'
        );
    }
}
