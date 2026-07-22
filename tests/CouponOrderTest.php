<?php

namespace Bazaar\Tests;

use Bazaar\Core\Session;
use Bazaar\Repositories\CouponRepository;
use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Services\Cart;
use Bazaar\Services\CouponService;
use Bazaar\Services\OrderService;

final class CouponOrderTest extends DatabaseTestCase
{
    private CouponRepository $coupons;
    private CouponService $couponService;
    private OrderRepository $orders;
    private ProductRepository $products;
    private OrderService $service;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coupons = new CouponRepository($this->db);
        $this->couponService = new CouponService($this->coupons);
        $this->orders = new OrderRepository($this->db);
        $this->products = new ProductRepository($this->db);
        $this->service = new OrderService($this->db, $this->orders, $this->products, null, $this->coupons);
        $this->userId = (new UserRepository($this->db))->create('coup@bazaar.test', 'hash', 'Coup');
    }

    public function testDiscountReducesStoredTotalAndIsRedeemedOnPaid(): void
    {
        $couponId = $this->coupons->create([
            'code' => 'TENOFF',
            'type' => 'percent',
            'value' => 10,
            'minSubtotalCents' => 0,
            'maxRedemptions' => 5,
            'startsAt' => null,
            'expiresAt' => null,
            'isActive' => true,
        ]);

        $productId = $this->seedProduct('Chair', 10000, 10);
        $cart = new Cart(new Session(), $this->products);
        $cart->add($productId, 1);

        $evaluation = $this->couponService->evaluate('TENOFF', $cart->totalCents());
        $this->assertTrue($evaluation->isValid());
        $this->assertSame(1000, $evaluation->discountCents);

        $orderId = $this->service->createFromCart($this->userId, $cart, 'usd', [
            'discountCents' => $evaluation->discountCents,
            'couponCode' => 'TENOFF',
        ]);

        $order = $this->orders->find($orderId);
        $this->assertSame(9000, (int) $order['totalCents']);
        $this->assertSame(1000, (int) $order['discountCents']);
        $this->assertSame('TENOFF', $order['couponCode']);
        $this->assertSame(0, (int) $this->coupons->find($couponId)['redeemedCount']);

        $this->service->markPaid($orderId);
        $this->assertSame(1, (int) $this->coupons->find($couponId)['redeemedCount']);
    }
}
