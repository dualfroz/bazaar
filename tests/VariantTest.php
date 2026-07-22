<?php

namespace Bazaar\Tests;

use Bazaar\Core\Session;
use Bazaar\Repositories\AttributeRepository;
use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Repositories\VariantRepository;
use Bazaar\Services\Cart;
use Bazaar\Services\OrderService;

final class VariantTest extends DatabaseTestCase
{
    private VariantRepository $variants;
    private AttributeRepository $attributes;
    private ProductRepository $products;

    protected function setUp(): void
    {
        parent::setUp();
        $this->variants = new VariantRepository($this->db);
        $this->attributes = new AttributeRepository($this->db);
        $this->products = new ProductRepository($this->db);
    }

    private function makeVariant(int $productId, string $sku, int $priceCents, int $stock, string $sizeValue): int
    {
        $attributeId = $this->attributes->findBySlug('size')
            ? (int) $this->attributes->findBySlug('size')['id']
            : $this->attributes->create('Size', 'size');
        $valueRow = $this->attributes->findValueBySlug($attributeId, slugify($sizeValue));
        $valueId = $valueRow ? (int) $valueRow['id'] : $this->attributes->createValue($attributeId, $sizeValue, slugify($sizeValue));

        $variantId = $this->variants->create($productId, $sku, $priceCents, $stock);
        $this->variants->setOption($variantId, $attributeId, $valueId);
        return $variantId;
    }

    public function testVariantCarriesOwnPriceStockAndLabel(): void
    {
        $productId = $this->seedProduct('Tee', 2000, 0);
        $variantId = $this->makeVariant($productId, 'TEE-S', 2500, 7, 'Small');

        $variant = $this->variants->find($variantId);
        $this->assertSame(2500, (int) $variant['priceCents']);
        $this->assertSame(7, (int) $variant['stockQuantity']);

        $forProduct = $this->variants->forProduct($productId);
        $this->assertCount(1, $forProduct);
        $this->assertSame('Small', $forProduct[0]['label']);
    }

    public function testCartUsesVariantPriceStockNotProduct(): void
    {
        $productId = $this->seedProduct('Tee', 2000, 100);
        $variantId = $this->makeVariant($productId, 'TEE-M', 2500, 3, 'Medium');

        $cart = new Cart(new Session(), $this->products, $this->variants);
        $cart->add($productId, 10, $variantId);

        $lines = $cart->lines();
        $this->assertCount(1, $lines);
        $this->assertSame(3, $lines[0]['quantity'], 'clamped to variant stock, not product stock');
        $this->assertSame(2500, $lines[0]['unitPriceCents']);
        $this->assertSame('Medium', $lines[0]['variantLabel']);
        $this->assertSame(2500 * 3, $cart->totalCents());
    }

    public function testProductAndVariantLinesAreSeparate(): void
    {
        $productId = $this->seedProduct('Tee', 2000, 50);
        $variantId = $this->makeVariant($productId, 'TEE-L', 2500, 20, 'Large');

        $cart = new Cart(new Session(), $this->products, $this->variants);
        $cart->add($productId, 2);
        $cart->add($productId, 1, $variantId);

        $this->assertCount(2, $cart->lines());
        $this->assertSame(3, $cart->count());
    }

    public function testOrderDecrementsVariantStockNotProductStock(): void
    {
        $productId = $this->seedProduct('Tee', 2000, 50);
        $variantId = $this->makeVariant($productId, 'TEE-XL', 2500, 8, 'Extra Large');
        $userId = (new UserRepository($this->db))->create('v@bazaar.test', password_hash('correcthorse', PASSWORD_DEFAULT), 'V');

        $cart = new Cart(new Session(), $this->products, $this->variants);
        $cart->add($productId, 2, $variantId);

        $orders = new OrderRepository($this->db);
        $service = new OrderService($this->db, $orders, $this->products, $this->variants);
        $orderId = $service->createFromCart($userId, $cart, 'usd');

        $items = $orders->items($orderId);
        $this->assertSame($variantId, (int) $items[0]['variantId']);
        $this->assertSame('Extra Large', $items[0]['variantLabel']);
        $this->assertSame(2500, (int) $items[0]['unitPriceCents']);

        $service->markPaid($orderId);

        $this->assertSame(6, (int) $this->variants->find($variantId)['stockQuantity']);
        $this->assertSame(50, (int) $this->products->find($productId)['stockQuantity'], 'product stock untouched');
    }
}
