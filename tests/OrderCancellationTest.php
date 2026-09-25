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
use Bazaar\Services\OrderService;

final class OrderCancellationTest extends DatabaseTestCase
{
    private ProductRepository $products;
    private VariantRepository $variants;
    private CouponRepository $coupons;
    private OrderRepository $orders;
    private OrderService $service;
    private int $userId;
    private int $productId;
    private int $variantId;
    private int $couponId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->products = new ProductRepository($this->db);
        $this->variants = new VariantRepository($this->db);
        $this->coupons = new CouponRepository($this->db);
        $this->orders = new OrderRepository($this->db);
        $this->service = new OrderService($this->db, $this->orders, $this->products, $this->variants, $this->coupons);
        $this->userId = (new UserRepository($this->db))->create('cancel@bazaar.test', 'hash', 'Cancel');

        $this->productId = $this->seedProduct('Lamp', 4000, 10);
        $attributes = new AttributeRepository($this->db);
        $attributeId = $attributes->create('Color', 'color');
        $valueId = $attributes->createValue($attributeId, 'Sand', 'sand');
        $this->variantId = $this->variants->create($this->productId, 'LAMP-SAND', 4500, 6);
        $this->variants->setOption($this->variantId, $attributeId, $valueId);

        $this->couponId = $this->coupons->create([
            'code' => 'LAMP10',
            'type' => 'percent',
            'value' => 10,
            'minSubtotalCents' => 0,
            'maxRedemptions' => 3,
            'startsAt' => null,
            'expiresAt' => null,
            'isActive' => true,
        ]);
    }

    private function placeOrder(int $userId): int
    {
        $cart = new Cart(new Session(), $this->products, $this->variants);
        $cart->add($this->productId, 2);
        $cart->add($this->productId, 3, $this->variantId);
        return $this->service->createFromCart($userId, $cart, 'usd', [
            'discountCents' => 1750,
            'couponCode' => 'LAMP10',
        ]);
    }

    private function productStock(): int
    {
        return (int) $this->products->find($this->productId)['stockQuantity'];
    }

    private function variantStock(): int
    {
        return (int) $this->variants->find($this->variantId)['stockQuantity'];
    }

    private function redeemedCount(): int
    {
        return (int) $this->coupons->find($this->couponId)['redeemedCount'];
    }

    private function assertCancelFails(callable $cancel): void
    {
        $failed = false;
        try {
            $cancel();
        } catch (\RuntimeException) {
            $failed = true;
        }
        $this->assertTrue($failed, 'Cancelling must be refused');
    }

    public function testCustomerCancelsPendingOrderWithoutTouchingStockOrCoupon(): void
    {
        $orderId = $this->placeOrder($this->userId);

        $this->service->cancelForCustomer($orderId, $this->userId);

        $order = $this->orders->find($orderId);
        $this->assertSame('cancelled', $order['status']);
        $this->assertNotEmpty($order['cancelledAt']);
        $this->assertSame(10, $this->productStock());
        $this->assertSame(6, $this->variantStock());
        $this->assertSame(0, $this->redeemedCount());
    }

    public function testCustomerCannotCancelPaidOrder(): void
    {
        $orderId = $this->placeOrder($this->userId);
        $this->service->markPaid($orderId);

        $this->assertCancelFails(fn () => $this->service->cancelForCustomer($orderId, $this->userId));
        $this->assertSame('paid', $this->orders->find($orderId)['status']);
        $this->assertSame(8, $this->productStock());
        $this->assertSame(3, $this->variantStock());
    }

    public function testCustomerCannotCancelSomeoneElsesOrder(): void
    {
        $otherUserId = (new UserRepository($this->db))->create('other@bazaar.test', 'hash', 'Other');
        $orderId = $this->placeOrder($otherUserId);

        $this->assertCancelFails(fn () => $this->service->cancelForCustomer($orderId, $this->userId));
        $this->assertSame('pending', $this->orders->find($orderId)['status']);
    }

    public function testStaffCancelsPaidOrderRestoringStockAndCoupon(): void
    {
        $orderId = $this->placeOrder($this->userId);
        $this->service->markPaid($orderId);
        $this->assertSame(8, $this->productStock());
        $this->assertSame(3, $this->variantStock());
        $this->assertSame(1, $this->redeemedCount());

        $this->service->cancelAsStaff($orderId);

        $order = $this->orders->find($orderId);
        $this->assertSame('cancelled', $order['status']);
        $this->assertNotEmpty($order['cancelledAt']);
        $this->assertSame(10, $this->productStock());
        $this->assertSame(6, $this->variantStock());
        $this->assertSame(0, $this->redeemedCount());
    }

    public function testStaffCancelsPendingOrder(): void
    {
        $orderId = $this->placeOrder($this->userId);

        $this->service->cancelAsStaff($orderId);

        $this->assertSame('cancelled', $this->orders->find($orderId)['status']);
        $this->assertSame(10, $this->productStock());
        $this->assertSame(0, $this->redeemedCount());
    }

    public function testFulfilledOrderCannotBeCancelled(): void
    {
        $orderId = $this->placeOrder($this->userId);
        $this->service->markPaid($orderId);
        $this->service->markFulfilled($orderId);

        $this->assertCancelFails(fn () => $this->service->cancelAsStaff($orderId));
        $this->assertSame('fulfilled', $this->orders->find($orderId)['status']);
        $this->assertSame(8, $this->productStock());
        $this->assertSame(1, $this->redeemedCount());
    }

    public function testSecondCancelIsRefusedAndRestoresNothingTwice(): void
    {
        $orderId = $this->placeOrder($this->userId);
        $this->service->markPaid($orderId);
        $this->service->cancelAsStaff($orderId);

        $this->assertCancelFails(fn () => $this->service->cancelAsStaff($orderId));
        $this->assertSame(10, $this->productStock());
        $this->assertSame(6, $this->variantStock());
        $this->assertSame(0, $this->redeemedCount());
    }

    public function testLatePaymentDoesNotReviveCancelledOrder(): void
    {
        $orderId = $this->placeOrder($this->userId);
        $this->service->cancelForCustomer($orderId, $this->userId);

        $this->service->markPaid($orderId, 'cs_test_late');

        $this->assertSame('cancelled', $this->orders->find($orderId)['status']);
        $this->assertSame(10, $this->productStock());
        $this->assertSame(0, $this->redeemedCount());
    }
}
