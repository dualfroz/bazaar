<?php

namespace Bazaar\Repositories;

use Bazaar\Core\Database;

final class VariantRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM productVariants WHERE id = ?', [$id]);
    }

    public function findActive(int $id): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM productVariants WHERE id = ? AND isActive = 1',
            [$id]
        );
    }

    public function forProduct(int $productId): array
    {
        $variants = $this->db->select(
            'SELECT * FROM productVariants WHERE productId = ? ORDER BY id ASC',
            [$productId]
        );
        foreach ($variants as &$variant) {
            $variant['options'] = $this->options((int) $variant['id']);
            $variant['label'] = $this->label($variant['options']);
        }
        return $variants;
    }

    public function activeForProduct(int $productId): array
    {
        return array_values(array_filter(
            $this->forProduct($productId),
            static fn (array $variant): bool => (int) $variant['isActive'] === 1
        ));
    }

    public function options(int $variantId): array
    {
        return $this->db->select(
            'SELECT vo.attributeId, vo.attributeValueId,
                    a.name AS attributeName, a.slug AS attributeSlug,
                    av.value AS valueName, av.slug AS valueSlug
             FROM variantOptions vo
             JOIN attributes a ON a.id = vo.attributeId
             JOIN attributeValues av ON av.id = vo.attributeValueId
             WHERE vo.variantId = ?
             ORDER BY a.name ASC',
            [$variantId]
        );
    }

    public function label(array $options): string
    {
        $parts = array_map(
            static fn (array $option): string => $option['valueName'],
            $options
        );
        return implode(' / ', $parts);
    }

    public function create(int $productId, string $sku, int $priceCents, int $stockQuantity, bool $isActive = true): int
    {
        return $this->db->insert(
            'INSERT INTO productVariants (productId, sku, priceCents, stockQuantity, isActive, createdAt)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$productId, $sku, $priceCents, $stockQuantity, $isActive ? 1 : 0, gmdate('c')]
        );
    }

    public function setOption(int $variantId, int $attributeId, int $attributeValueId): void
    {
        $this->db->statement(
            'INSERT INTO variantOptions (variantId, attributeId, attributeValueId)
             VALUES (?, ?, ?)
             ON CONFLICT(variantId, attributeId)
             DO UPDATE SET attributeValueId = excluded.attributeValueId',
            [$variantId, $attributeId, $attributeValueId]
        );
    }

    public function updateStock(int $id, int $stockQuantity): void
    {
        $this->db->statement(
            'UPDATE productVariants SET stockQuantity = ? WHERE id = ?',
            [$stockQuantity, $id]
        );
    }

    public function decrementStock(int $id, int $quantity): void
    {
        $this->db->statement(
            'UPDATE productVariants SET stockQuantity = stockQuantity - ? WHERE id = ?',
            [$quantity, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->db->statement('DELETE FROM productVariants WHERE id = ?', [$id]);
    }

    public function priceRangeCents(int $productId): array
    {
        $row = $this->db->selectOne(
            'SELECT MIN(priceCents) AS minCents, MAX(priceCents) AS maxCents
             FROM productVariants WHERE productId = ? AND isActive = 1',
            [$productId]
        );
        return [
            'minCents' => $row['minCents'] !== null ? (int) $row['minCents'] : null,
            'maxCents' => $row['maxCents'] !== null ? (int) $row['maxCents'] : null,
        ];
    }

    public function stockForProduct(int $productId): int
    {
        return (int) $this->db->scalar(
            'SELECT COALESCE(SUM(stockQuantity), 0) FROM productVariants WHERE productId = ? AND isActive = 1',
            [$productId]
        );
    }

    public function countForProduct(int $productId): int
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM productVariants WHERE productId = ?',
            [$productId]
        );
    }
}
