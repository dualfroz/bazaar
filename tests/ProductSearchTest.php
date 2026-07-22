<?php

namespace Bazaar\Tests;

use Bazaar\Repositories\AttributeRepository;
use Bazaar\Repositories\CategoryRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\ReviewRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Repositories\VariantRepository;
use Bazaar\Services\ProductSearch;

final class ProductSearchTest extends DatabaseTestCase
{
    private CategoryRepository $categories;
    private ProductRepository $products;
    private AttributeRepository $attributes;
    private VariantRepository $variants;
    private ReviewRepository $reviews;
    private ProductSearch $search;
    private array $categoryIds = [];
    private int $colorId;
    private array $colorValueIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->categories = new CategoryRepository($this->db);
        $this->products = new ProductRepository($this->db);
        $this->attributes = new AttributeRepository($this->db);
        $this->variants = new VariantRepository($this->db);
        $this->reviews = new ReviewRepository($this->db);
        $this->search = new ProductSearch($this->db, $this->attributes, $this->variants, $this->reviews, $this->categories);

        $this->categoryIds['apparel'] = $this->categories->create('Apparel', 'apparel', '');
        $this->categoryIds['home'] = $this->categories->create('Home', 'home', '');
        $this->colorId = $this->attributes->create('Color', 'color');
        $this->colorValueIds['red'] = $this->attributes->createValue($this->colorId, 'Red', 'red');
        $this->colorValueIds['blue'] = $this->attributes->createValue($this->colorId, 'Blue', 'blue');

        $this->product('apparel', 'Red Shirt', 1000, ['red']);
        $this->product('apparel', 'Blue Shirt', 2000, ['blue']);
        $this->product('apparel', 'Crimson Coat', 3000, ['red']);
        $this->product('home', 'Wool Rug', 1500, []);
    }

    private function product(string $category, string $name, int $priceCents, array $colors): int
    {
        $productId = $this->products->create([
            'categoryId' => $this->categoryIds[$category],
            'name' => $name,
            'slug' => slugify($name),
            'description' => '',
            'priceCents' => $priceCents,
            'stockQuantity' => 10,
            'imageUrl' => '',
            'isActive' => true,
        ]);
        foreach ($colors as $index => $color) {
            $variantId = $this->variants->create($productId, slugify($name) . '-' . $color, $priceCents, 5);
            $this->variants->setOption($variantId, $this->colorId, $this->colorValueIds[$color]);
        }
        return $productId;
    }

    public function testTotalReflectsAllMatchesNotJustThePage(): void
    {
        $result = $this->search->search(['perPage' => 2, 'page' => 1]);

        $this->assertSame(4, $result['total']);
        $this->assertSame(2, $result['pages']);
        $this->assertCount(2, $result['products'], 'page is limited to perPage');
    }

    public function testSecondPageReturnsRemainder(): void
    {
        $page2 = $this->search->search(['perPage' => 3, 'page' => 2]);
        $this->assertSame(4, $page2['total']);
        $this->assertCount(1, $page2['products']);
    }

    public function testCategoryFilterNarrowsResults(): void
    {
        $result = $this->search->search(['categorySlug' => 'apparel']);
        $this->assertSame(3, $result['total']);
    }

    public function testPriceRangeFilter(): void
    {
        $result = $this->search->search(['minPriceCents' => 1000, 'maxPriceCents' => 2000]);
        $names = array_column($result['products'], 'name');

        $this->assertSame(3, $result['total']);
        $this->assertContains('Wool Rug', $names);
        $this->assertNotContains('Crimson Coat', $names);
    }

    public function testAttributeFilterMatchesOnVariantOption(): void
    {
        $result = $this->search->search(['attributeSelections' => ['color' => ['red']]]);
        $names = array_column($result['products'], 'name');

        $this->assertSame(2, $result['total']);
        $this->assertEqualsCanonicalizing(['Red Shirt', 'Crimson Coat'], $names);
    }

    public function testCategoryFacetCounts(): void
    {
        $facet = $this->search->search([])['facets']['categories'];
        $byName = [];
        foreach ($facet as $entry) {
            $byName[$entry['name']] = $entry['count'];
        }

        $this->assertSame(3, $byName['Apparel']);
        $this->assertSame(1, $byName['Home']);
    }

    public function testAttributeFacetCountsIgnoreOwnSelection(): void
    {
        $result = $this->search->search(['attributeSelections' => ['color' => ['red']]]);
        $colorFacet = null;
        foreach ($result['facets']['attributes'] as $attribute) {
            if ($attribute['slug'] === 'color') {
                $colorFacet = $attribute;
            }
        }
        $counts = [];
        foreach ($colorFacet['values'] as $value) {
            $counts[$value['slug']] = ['count' => $value['count'], 'selected' => $value['selected']];
        }

        $this->assertSame(2, $counts['red']['count']);
        $this->assertSame(1, $counts['blue']['count'], 'blue count is not zeroed by the red selection');
        $this->assertTrue($counts['red']['selected']);
        $this->assertFalse($counts['blue']['selected']);
    }

    public function testPriceSortAscending(): void
    {
        $result = $this->search->search(['sort' => 'priceAsc', 'perPage' => 10]);
        $prices = array_map('intval', array_column($result['products'], 'priceCents'));

        $sorted = $prices;
        sort($sorted);
        $this->assertSame($sorted, $prices);
    }

    public function testRatingSortSurfacesTopRated(): void
    {
        $users = new UserRepository($this->db);
        $userId = $users->create('r@bazaar.test', 'hash', 'R');
        $blueShirt = $this->products->findBySlug('blue-shirt');
        $reviewId = $this->reviews->create((int) $blueShirt['id'], $userId, 5, 'Top', 'Best');
        $this->reviews->setStatus($reviewId, 'approved');

        $result = $this->search->search(['sort' => 'rating', 'perPage' => 10]);
        $this->assertSame('Blue Shirt', $result['products'][0]['name']);
        $this->assertSame(5.0, $result['products'][0]['ratingAverage']);
    }

    public function testPriceRangeFacetBounds(): void
    {
        $range = $this->search->search([])['facets']['priceRange'];
        $this->assertSame(1000, $range['minCents']);
        $this->assertSame(3000, $range['maxCents']);
    }
}
