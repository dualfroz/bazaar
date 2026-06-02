<?php

namespace Bazaar\Services;

use Bazaar\Core\Database;
use Bazaar\Repositories\AttributeRepository;
use Bazaar\Repositories\CategoryRepository;
use Bazaar\Repositories\ReviewRepository;
use Bazaar\Repositories\VariantRepository;

final class ProductSearch
{
    private const defaultPerPage = 9;
    private const maxPerPage = 48;

    public function __construct(
        private readonly Database $db,
        private readonly AttributeRepository $attributes,
        private readonly VariantRepository $variants,
        private readonly ReviewRepository $reviews,
        private readonly CategoryRepository $categories
    ) {
    }

    public function search(array $criteria): array
    {
        $categorySlug = $this->stringOrNull($criteria['categorySlug'] ?? null);
        $term = $this->stringOrNull($criteria['term'] ?? null);
        $minPriceCents = isset($criteria['minPriceCents']) ? (int) $criteria['minPriceCents'] : null;
        $maxPriceCents = isset($criteria['maxPriceCents']) ? (int) $criteria['maxPriceCents'] : null;
        $sort = (string) ($criteria['sort'] ?? 'newest');
        $perPage = $this->clamp((int) ($criteria['perPage'] ?? self::defaultPerPage), 1, self::maxPerPage);
        $page = max(1, (int) ($criteria['page'] ?? 1));

        $selectedGroups = $this->resolveSelections($criteria['attributeSelections'] ?? []);

        $category = $categorySlug !== null
            ? ['sql' => 'c.slug = ?', 'bindings' => [$categorySlug]]
            : null;
        $termFragment = $term !== null
            ? ['sql' => '(p.name LIKE ? OR p.description LIKE ?)', 'bindings' => ['%' . $term . '%', '%' . $term . '%']]
            : null;
        $price = $this->priceFragment($minPriceCents, $maxPriceCents);
        $attributeFragments = [];
        foreach ($selectedGroups as $attributeId => $group) {
            $attributeFragments[$attributeId] = $this->attributeFragment($group['valueIds']);
        }

        $full = $this->compose(array_merge(
            array_filter([$category, $termFragment, $price]),
            array_values($attributeFragments)
        ));

        $total = (int) $this->db->scalar(
            "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.categoryId {$full['where']}",
            $full['bindings']
        );

        $pages = $total === 0 ? 0 : (int) ceil($total / $perPage);
        $page = $pages === 0 ? 1 : min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $products = $this->page($full, $sort, $perPage, $offset);

        return [
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => $pages,
            'sort' => $sort,
            'facets' => [
                'categories' => $this->categoryFacet($termFragment, $price, $attributeFragments),
                'attributes' => $this->attributeFacets($category, $termFragment, $price, $attributeFragments, $selectedGroups),
                'priceRange' => $this->priceRange($category, $termFragment, $attributeFragments),
            ],
            'selected' => $this->selectedSummary($selectedGroups),
        ];
    }

    private function page(array $full, string $sort, int $perPage, int $offset): array
    {
        $order = match ($sort) {
            'priceAsc' => 'p.priceCents ASC, p.id DESC',
            'priceDesc' => 'p.priceCents DESC, p.id DESC',
            'name' => 'p.name ASC',
            'rating' => 'COALESCE(rr.avgRating, 0) DESC, rr.reviewCount DESC, p.id DESC',
            default => 'p.createdAt DESC, p.id DESC',
        };

        $rows = $this->db->select(
            "SELECT p.*, c.name AS categoryName, c.slug AS categorySlug,
                    COALESCE(rr.avgRating, 0) AS ratingAverage,
                    COALESCE(rr.reviewCount, 0) AS ratingCount
             FROM products p
             LEFT JOIN categories c ON c.id = p.categoryId
             LEFT JOIN (
                SELECT productId, AVG(rating) AS avgRating, COUNT(*) AS reviewCount
                FROM reviews WHERE status = 'approved' GROUP BY productId
             ) rr ON rr.productId = p.id
             {$full['where']}
             ORDER BY {$order}
             LIMIT ? OFFSET ?",
            array_merge($full['bindings'], [$perPage, $offset])
        );

        foreach ($rows as &$product) {
            $productId = (int) $product['id'];
            $range = $this->variants->priceRangeCents($productId);
            $variantStock = $this->variants->stockForProduct($productId);
            $variantCount = $this->variants->countForProduct($productId);
            $product['ratingAverage'] = round((float) $product['ratingAverage'], 2);
            $product['ratingCount'] = (int) $product['ratingCount'];
            $product['variantCount'] = $variantCount;
            $product['priceFromCents'] = $range['minCents'] ?? (int) $product['priceCents'];
            $product['availableStock'] = $variantCount > 0 ? $variantStock : (int) $product['stockQuantity'];
        }
        return $rows;
    }

    private function categoryFacet(?array $term, ?array $price, array $attributeFragments): array
    {
        $composite = $this->compose(array_merge(
            array_filter([$term, $price]),
            array_values($attributeFragments)
        ));

        $counts = $this->db->select(
            "SELECT p.categoryId, COUNT(*) AS cnt
             FROM products p {$composite['where']}
             GROUP BY p.categoryId",
            $composite['bindings']
        );
        $byCategory = [];
        foreach ($counts as $row) {
            if ($row['categoryId'] !== null) {
                $byCategory[(int) $row['categoryId']] = (int) $row['cnt'];
            }
        }

        $facet = [];
        foreach ($this->categories->all() as $category) {
            $facet[] = [
                'slug' => $category['slug'],
                'name' => $category['name'],
                'count' => $byCategory[(int) $category['id']] ?? 0,
            ];
        }
        return $facet;
    }

    private function attributeFacets(?array $category, ?array $term, ?array $price, array $attributeFragments, array $selectedGroups): array
    {
        $facets = [];
        foreach ($this->attributes->withValues() as $attribute) {
            $attributeId = (int) $attribute['id'];
            $others = $attributeFragments;
            unset($others[$attributeId]);

            $composite = $this->compose(array_merge(
                array_filter([$category, $term, $price]),
                array_values($others)
            ));

            $counts = $this->db->select(
                "SELECT vo.attributeValueId AS valueId, COUNT(DISTINCT p.id) AS cnt
                 FROM products p
                 LEFT JOIN categories c ON c.id = p.categoryId
                 JOIN productVariants v ON v.productId = p.id AND v.isActive = 1
                 JOIN variantOptions vo ON vo.variantId = v.id
                 JOIN attributeValues av ON av.id = vo.attributeValueId AND av.attributeId = ?
                 {$composite['where']}
                 GROUP BY vo.attributeValueId",
                array_merge([$attributeId], $composite['bindings'])
            );
            $byValue = [];
            foreach ($counts as $row) {
                $byValue[(int) $row['valueId']] = (int) $row['cnt'];
            }

            $values = [];
            foreach ($attribute['values'] as $value) {
                $values[] = [
                    'slug' => $value['slug'],
                    'name' => $value['value'],
                    'count' => $byValue[(int) $value['id']] ?? 0,
                    'selected' => in_array($value['slug'], $selectedGroups[$attributeId]['valueSlugs'] ?? [], true),
                ];
            }

            $facets[] = [
                'slug' => $attribute['slug'],
                'name' => $attribute['name'],
                'values' => $values,
            ];
        }
        return $facets;
    }

    private function priceRange(?array $category, ?array $term, array $attributeFragments): array
    {
        $composite = $this->compose(array_merge(
            array_filter([$category, $term]),
            array_values($attributeFragments)
        ));
        $row = $this->db->selectOne(
            "SELECT MIN(p.priceCents) AS minCents, MAX(p.priceCents) AS maxCents
             FROM products p LEFT JOIN categories c ON c.id = p.categoryId {$composite['where']}",
            $composite['bindings']
        );
        return [
            'minCents' => $row['minCents'] !== null ? (int) $row['minCents'] : 0,
            'maxCents' => $row['maxCents'] !== null ? (int) $row['maxCents'] : 0,
        ];
    }

    private function resolveSelections(array $selections): array
    {
        $groups = [];
        foreach ($selections as $attributeSlug => $valueSlugs) {
            $attribute = $this->attributes->findBySlug((string) $attributeSlug);
            if ($attribute === null) {
                continue;
            }
            $valueIds = [];
            $keptSlugs = [];
            foreach ((array) $valueSlugs as $valueSlug) {
                $value = $this->attributes->findValueBySlug((int) $attribute['id'], (string) $valueSlug);
                if ($value !== null) {
                    $valueIds[] = (int) $value['id'];
                    $keptSlugs[] = $value['slug'];
                }
            }
            if ($valueIds !== []) {
                $groups[(int) $attribute['id']] = [
                    'attributeSlug' => $attribute['slug'],
                    'attributeName' => $attribute['name'],
                    'valueIds' => $valueIds,
                    'valueSlugs' => $keptSlugs,
                ];
            }
        }
        return $groups;
    }

    private function attributeFragment(array $valueIds): array
    {
        $placeholders = implode(', ', array_fill(0, count($valueIds), '?'));
        return [
            'sql' => "EXISTS (SELECT 1 FROM productVariants v JOIN variantOptions vo ON vo.variantId = v.id
                      WHERE v.productId = p.id AND v.isActive = 1 AND vo.attributeValueId IN ({$placeholders}))",
            'bindings' => $valueIds,
        ];
    }

    private function priceFragment(?int $minPriceCents, ?int $maxPriceCents): ?array
    {
        if ($minPriceCents !== null && $maxPriceCents !== null) {
            return ['sql' => 'p.priceCents BETWEEN ? AND ?', 'bindings' => [$minPriceCents, $maxPriceCents]];
        }
        if ($minPriceCents !== null) {
            return ['sql' => 'p.priceCents >= ?', 'bindings' => [$minPriceCents]];
        }
        if ($maxPriceCents !== null) {
            return ['sql' => 'p.priceCents <= ?', 'bindings' => [$maxPriceCents]];
        }
        return null;
    }

    private function compose(array $fragments): array
    {
        $clauses = ['p.isActive = 1'];
        $bindings = [];
        foreach ($fragments as $fragment) {
            $clauses[] = $fragment['sql'];
            $bindings = array_merge($bindings, $fragment['bindings']);
        }
        return [
            'where' => 'WHERE ' . implode(' AND ', $clauses),
            'bindings' => $bindings,
        ];
    }

    private function selectedSummary(array $selectedGroups): array
    {
        $summary = [];
        foreach ($selectedGroups as $group) {
            $summary[$group['attributeSlug']] = $group['valueSlugs'];
        }
        return $summary;
    }

    private function stringOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        return $value === '' ? null : $value;
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($value, $max));
    }
}
