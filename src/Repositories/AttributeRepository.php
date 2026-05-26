<?php

namespace Bazaar\Repositories;

use Bazaar\Core\Database;

final class AttributeRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->select('SELECT * FROM attributes ORDER BY name ASC');
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM attributes WHERE id = ?', [$id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->selectOne('SELECT * FROM attributes WHERE slug = ?', [$slug]);
    }

    public function create(string $name, string $slug): int
    {
        return $this->db->insert(
            'INSERT INTO attributes (name, slug) VALUES (?, ?)',
            [$name, $slug]
        );
    }

    public function values(int $attributeId): array
    {
        return $this->db->select(
            'SELECT * FROM attributeValues WHERE attributeId = ? ORDER BY position ASC, value ASC',
            [$attributeId]
        );
    }

    public function findValueBySlug(int $attributeId, string $slug): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM attributeValues WHERE attributeId = ? AND slug = ?',
            [$attributeId, $slug]
        );
    }

    public function findValueBySlugAcrossAttributes(string $slug): ?array
    {
        return $this->db->selectOne('SELECT * FROM attributeValues WHERE slug = ? LIMIT 1', [$slug]);
    }

    public function createValue(int $attributeId, string $value, string $slug, int $position = 0): int
    {
        return $this->db->insert(
            'INSERT INTO attributeValues (attributeId, value, slug, position) VALUES (?, ?, ?, ?)',
            [$attributeId, $value, $slug, $position]
        );
    }

    public function withValues(): array
    {
        $attributes = $this->all();
        foreach ($attributes as &$attribute) {
            $attribute['values'] = $this->values((int) $attribute['id']);
        }
        return $attributes;
    }
}
