<?php

namespace Bazaar\Tests;

use Bazaar\Core\Session;
use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Services\Cart;
use Bazaar\Services\InsufficientStockException;
use Bazaar\Services\OrderService;

final class OrderServiceTest extends DatabaseTestCase
{
    private OrderRepository $orders;
    private ProductRepository $products;
    private OrderService $service;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orders = new OrderRepository($this->db);
        $this->products = new ProductRepository($this->db);
        $this->service = new OrderService($this->db, $this->orders, $this->products);
        $this->userId = (new UserRepository($this->db))->create(
            'buyer@bazaar.test',
            password_hash('correcthorse', PASSWORD_DEFAULT),
            'Buyer'
        );
    }

    private function cartWith(int $productId, int $quantity): Cart
    {
        $cart = new Cart(new Session(), $this->products);
        $cart->add($productId, $quantity);
        return $cart;
    }

    public function testCreateFromCartSnapshotsItemsAndTotal(): void
    {
        $productId = $this->seedProduct('Brass Pen', 6400, 10);
        $orderId = $this->service->createFromCart($this->userId, $this->cartWith($productId, 2), 'usd');

        $order = $this->orders->find($orderId);
        $items = $this->orders->items($orderId);

        $this->assertSame('pending', $order['status']);
        $this->assertSame(6400 * 2, (int) $order['totalCents']);
        $this->assertCount(1, $items);
        $this->assertSame('Brass Pen', $items[0]['productName']);
        $this->assertSame(6400, (int) $items[0]['unitPriceCents']);
        $this->assertSame(2, (int) $items[0]['quantity']);
    }

    public function testCreateFromEmptyCartThrows(): void
    {
        $emptyCart = new Cart(new Session(), $this->products);

        $this->expectException(\RuntimeException::class);
        $this->service->createFromCart($this->userId, $emptyCart, 'usd');
    }

    public function testMarkPaidTransitionsAndDecrementsStock(): void
    {
        $productId = $this->seedProduct('Soy Candle', 2200, 8);
        $orderId = $this->service->createFromCart($this->userId, $this->cartWith($productId, 3), 'usd');

        $this->service->markPaid($orderId, 'cs_test_123');

        $order = $this->orders->find($orderId);
        $this->assertSame('paid', $order['status']);
        $this->assertNotEmpty($order['paidAt']);
        $this->assertSame('cs_test_123', $order['stripeSessionId']);
        $this->assertSame(5, (int) $this->products->find($productId)['stockQuantity']);
    }

    public function testMarkPaidIsIdempotent(): void
    {
        $productId = $this->seedProduct('Planter', 2900, 6);
        $orderId = $this->service->createFromCart($this->userId, $this->cartWith($productId, 2), 'usd');

        $this->service->markPaid($orderId);
        $this->service->markPaid($orderId);

        $this->assertSame(4, (int) $this->products->find($productId)['stockQuantity']);
    }

    public function testMarkPaidFailsWhenStockDisappeared(): void
    {
        $productId = $this->seedProduct('Kettle', 5900, 4);
        $orderId = $this->service->createFromCart($this->userId, $this->cartWith($productId, 4), 'usd');
        $this->products->decrementStock($productId, 3);

        $this->expectException(InsufficientStockException::class);
        $this->service->markPaid($orderId);
    }

    public function testMarkFulfilledOnlyFromPaid(): void
    {
        $productId = $this->seedProduct('Organizer', 2600, 10);
        $orderId = $this->service->createFromCart($this->userId, $this->cartWith($productId, 1), 'usd');

        $pendingFailed = false;
        try {
            $this->service->markFulfilled($orderId);
        } catch (\RuntimeException) {
            $pendingFailed = true;
        }
        $this->assertTrue($pendingFailed, 'A pending order must not be fulfillable');

        $this->service->markPaid($orderId);
        $this->service->markFulfilled($orderId);

        $order = $this->orders->find($orderId);
        $this->assertSame('fulfilled', $order['status']);
        $this->assertNotEmpty($order['fulfilledAt']);
    }
}
