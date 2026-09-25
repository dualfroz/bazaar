<?php

namespace Bazaar\Tests;

use Bazaar\Core\Session;
use Bazaar\Repositories\AttributeRepository;
use Bazaar\Repositories\CouponRepository;
use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Repositories\VariantRepository;
use Bazaar\Services\Cart;
use Bazaar\Services\CouponService;
use Bazaar\Services\OrderService;
use Bazaar\Services\StripeGateway;

final class StripeGatewayTest extends DatabaseTestCase
{
    private ProductRepository $products;
    private VariantRepository $variants;
    private AttributeRepository $attributes;
    private CouponRepository $coupons;
    private OrderRepository $orders;
    private OrderService $service;
    private StripeGateway $gateway;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->products = new ProductRepository($this->db);
        $this->variants = new VariantRepository($this->db);
        $this->attributes = new AttributeRepository($this->db);
        $this->coupons = new CouponRepository($this->db);
        $this->orders = new OrderRepository($this->db);
        $this->service = new OrderService($this->db, $this->orders, $this->products, $this->variants, $this->coupons);
        $this->gateway = new StripeGateway('sk_test_placeholder', 'whsec_placeholder');
        $this->userId = (new UserRepository($this->db))->create('stripe@bazaar.test', 'hash', 'Stripe');
    }

    private function makeVariant(int $productId, string $sku, int $priceCents, string $sizeValue): int
    {
        $size = $this->attributes->findBySlug('size');
        $attributeId = $size !== null ? (int) $size['id'] : $this->attributes->create('Size', 'size');
        $valueId = $this->attributes->createValue($attributeId, $sizeValue, slugify($sizeValue));
        $variantId = $this->variants->create($productId, $sku, $priceCents, 20);
        $this->variants->setOption($variantId, $attributeId, $valueId);
        return $variantId;
    }

    private function makeCoupon(string $code, string $type, int $value): void
    {
        $this->coupons->create([
            'code' => $code,
            'type' => $type,
            'value' => $value,
            'minSubtotalCents' => 0,
            'maxRedemptions' => null,
            'startsAt' => null,
            'expiresAt' => null,
            'isActive' => true,
        ]);
    }

    private function placeOrder(Cart $cart, ?string $couponCode): int
    {
        $discount = null;
        if ($couponCode !== null) {
            $evaluation = (new CouponService($this->coupons))->evaluate($couponCode, $cart->totalCents());
            $this->assertTrue($evaluation->isValid());
            $discount = ['discountCents' => $evaluation->discountCents, 'couponCode' => $couponCode];
        }
        return $this->service->createFromCart($this->userId, $cart, 'usd', $discount);
    }

    private function payload(int $orderId): array
    {
        return $this->gateway->checkoutPayload(
            $this->orders->find($orderId),
            $this->orders->items($orderId),
            'https://shop.test/success',
            'https://shop.test/cancel'
        );
    }

    private function payloadTotalCents(array $payload): int
    {
        $totalCents = 0;
        foreach ($payload['line_items'] as $lineItem) {
            $totalCents += $lineItem['price_data']['unit_amount'] * $lineItem['quantity'];
        }
        return $totalCents;
    }

    private function payloadQuantity(array $payload): int
    {
        return array_sum(array_column($payload['line_items'], 'quantity'));
    }

    public function testPayloadTotalEqualsOrderTotalWithVariantsAndPercentCoupon(): void
    {
        $teeId = $this->seedProduct('Tee', 1000, 0);
        $smallId = $this->makeVariant($teeId, 'TEE-S', 2499, 'Small');
        $largeId = $this->makeVariant($teeId, 'TEE-L', 3333, 'Large');
        $mugId = $this->seedProduct('Mug', 999, 10);
        $this->makeCoupon('SAVE15', 'percent', 15);

        $cart = new Cart(new Session(), $this->products, $this->variants);
        $cart->add($teeId, 3, $smallId);
        $cart->add($teeId, 2, $largeId);
        $cart->add($mugId, 1);

        $orderId = $this->placeOrder($cart, 'SAVE15');
        $order = $this->orders->find($orderId);
        $payload = $this->payload($orderId);

        $this->assertSame(2499 * 3 + 3333 * 2 + 999, $cart->totalCents());
        $this->assertGreaterThan(0, (int) $order['discountCents']);
        $this->assertSame((int) $order['totalCents'], $this->payloadTotalCents($payload));
        $this->assertSame(6, $this->payloadQuantity($payload));
        foreach ($payload['line_items'] as $lineItem) {
            $this->assertIsInt($lineItem['price_data']['unit_amount']);
            $this->assertGreaterThanOrEqual(0, $lineItem['price_data']['unit_amount']);
            $this->assertSame('usd', $lineItem['price_data']['currency']);
            $this->assertSame('Price includes coupon SAVE15', $lineItem['price_data']['product_data']['description']);
        }
        $this->assertSame((string) $orderId, $payload['metadata']['orderId']);
        $this->assertSame((string) $orderId, $payload['client_reference_id']);
    }

    public function testPayloadTotalEqualsOrderTotalWithFixedCouponOnOddQuantities(): void
    {
        $tee = $this->seedProduct('Tee', 1000, 0);
        $variantId = $this->makeVariant($tee, 'TEE-M', 1001, 'Medium');
        $this->makeCoupon('SHIP5', 'fixed', 500);

        $cart = new Cart(new Session(), $this->products, $this->variants);
        $cart->add($tee, 7, $variantId);

        $orderId = $this->placeOrder($cart, 'SHIP5');
        $payload = $this->payload($orderId);

        $this->assertSame(1001 * 7 - 500, (int) $this->orders->find($orderId)['totalCents']);
        $this->assertSame(1001 * 7 - 500, $this->payloadTotalCents($payload));
        $this->assertSame(7, $this->payloadQuantity($payload));
    }

    public function testLinesWithoutCouponUseVariantPriceAndLabel(): void
    {
        $tee = $this->seedProduct('Tee', 1000, 0);
        $variantId = $this->makeVariant($tee, 'TEE-XL', 2750, 'XL');

        $cart = new Cart(new Session(), $this->products, $this->variants);
        $cart->add($tee, 2, $variantId);

        $payload = $this->payload($this->placeOrder($cart, null));

        $this->assertCount(1, $payload['line_items']);
        $lineItem = $payload['line_items'][0];
        $this->assertSame(2, $lineItem['quantity']);
        $this->assertSame(2750, $lineItem['price_data']['unit_amount']);
        $this->assertSame('Tee (XL)', $lineItem['price_data']['product_data']['name']);
        $this->assertArrayNotHasKey('description', $lineItem['price_data']['product_data']);
    }
}
