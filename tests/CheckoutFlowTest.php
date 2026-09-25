<?php

namespace Bazaar\Tests;

use Bazaar\Core\App;
use Bazaar\Core\Config;
use Bazaar\Core\Request;
use Bazaar\Core\Response;
use Bazaar\Core\Session;
use Bazaar\Mail\LogMailer;
use Bazaar\Repositories\CouponRepository;
use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Services\Cart;
use Stripe\ApiRequestor;

final class CheckoutFlowTest extends DatabaseTestCase
{
    private FakeStripeHttpClient $stripe;
    private LogMailer $mailer;
    private ProductRepository $products;
    private CouponRepository $coupons;
    private OrderRepository $orders;
    private string $logPath;
    private int $userId;
    private int $productId;
    private int $couponId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripe = new FakeStripeHttpClient();
        ApiRequestor::setHttpClient($this->stripe);
        $this->logPath = sys_get_temp_dir() . '/bazaar-checkout-' . uniqid() . '.log';
        $this->mailer = new LogMailer($this->logPath);
        $this->products = new ProductRepository($this->db);
        $this->coupons = new CouponRepository($this->db);
        $this->orders = new OrderRepository($this->db);

        $users = new UserRepository($this->db);
        $this->userId = $users->create('buyer@bazaar.test', 'hash', 'Buyer');
        $this->productId = $this->seedProduct('Candle', 2500, 5);
        $this->couponId = $this->coupons->create([
            'code' => 'FREE100',
            'type' => 'percent',
            'value' => 100,
            'minSubtotalCents' => 0,
            'maxRedemptions' => 10,
            'startsAt' => null,
            'expiresAt' => null,
            'isActive' => true,
        ]);

        $_SESSION['csrfToken'] = 'checkout-test-token';
        $_SESSION['authUserId'] = $this->userId;
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        Config::set('STRIPE_SECRET_KEY', 'sk_test_placeholder');
        if (is_file($this->logPath)) {
            unlink($this->logPath);
        }
    }

    private function send(string $method, string $path, array $query = []): Response
    {
        $body = $method === 'POST' ? ['csrfToken' => $_SESSION['csrfToken']] : [];
        $app = new App();
        $app->initialize($this->db, new Request($method, $path, $query, $body, []), new Session(), $this->mailer);
        return $app->handle();
    }

    private function fillCart(int $quantity): void
    {
        (new Cart(new Session(), $this->products))->add($this->productId, $quantity);
    }

    private function configureStripe(): void
    {
        Config::set('STRIPE_SECRET_KEY', 'sk_test_checkout');
    }

    private function onlyOrder(): array
    {
        $orders = $this->orders->forUser($this->userId);
        $this->assertCount(1, $orders);
        return $orders[0];
    }

    private function stock(): int
    {
        return (int) $this->products->find($this->productId)['stockQuantity'];
    }

    public function testFullDiscountOrderSkipsStripeAndIsMarkedPaid(): void
    {
        $this->configureStripe();
        $this->fillCart(2);
        $_SESSION['couponCode'] = 'FREE100';

        $response = $this->send('POST', '/checkout');

        $order = $this->onlyOrder();
        $this->assertSame(302, $response->status());
        $this->assertSame('/account/orders/' . $order['id'], $response->header('Location'));
        $this->assertSame([], $this->stripe->requests);
        $this->assertSame('paid', $order['status']);
        $this->assertSame(0, (int) $order['totalCents']);
        $this->assertSame(5000, (int) $order['discountCents']);
        $this->assertNotEmpty($order['paidAt']);
        $this->assertSame(3, $this->stock());
        $this->assertSame(1, (int) $this->coupons->find($this->couponId)['redeemedCount']);
        $this->assertCount(1, $this->mailer->sent());
        $this->assertStringContainsString('order #' . $order['id'] . ' confirmed', $this->mailer->sent()[0]->subject);
        $this->assertSame([], $_SESSION['cart'] ?? []);
        $this->assertSame('Order #' . $order['id'] . ' is confirmed. Nothing to pay.', $_SESSION['flash']['success']);
    }

    public function testFullDiscountOrderWorksWithoutStripeKeys(): void
    {
        $this->fillCart(1);
        $_SESSION['couponCode'] = 'FREE100';
        $_SESSION['locale'] = 'pl';

        $this->send('POST', '/checkout');

        $order = $this->onlyOrder();
        $this->assertSame('paid', $order['status']);
        $this->assertSame('pl', $order['locale']);
        $this->assertSame(4, $this->stock());
        $this->assertStringContainsString('zamowienie #' . $order['id'] . ' potwierdzone', $this->mailer->sent()[0]->subject);
        $this->assertStringContainsString('Dziekujemy za zamowienie.', $this->mailer->sent()[0]->body);
    }

    public function testFailedStripeSessionMarksOrderFailedAndShowsError(): void
    {
        $this->configureStripe();
        $this->fillCart(2);
        $this->stripe->respondWith(500, ['error' => ['type' => 'api_error', 'message' => 'Stripe is down']]);

        $response = $this->send('POST', '/checkout');

        $order = $this->onlyOrder();
        $this->assertSame(302, $response->status());
        $this->assertSame('/checkout', $response->header('Location'));
        $this->assertCount(1, $this->stripe->requests);
        $this->assertSame('https://api.stripe.com/v1/checkout/sessions', $this->stripe->requests[0]['url']);
        $this->assertSame('failed', $order['status']);
        $this->assertNotEmpty($order['failedAt']);
        $this->assertNull($order['stripeSessionId']);
        $this->assertSame(5, $this->stock());
        $this->assertSame([], $this->mailer->sent());

        $page = $this->send('GET', '/checkout');
        $this->assertStringContainsString('We could not start the payment. Your order was cancelled and nothing was charged.', $page->content());
        $this->assertStringContainsString('Candle', $page->content());
    }

    public function testSuccessfulStripeSessionRedirectsToStripe(): void
    {
        $this->configureStripe();
        $this->fillCart(1);
        $this->stripe->respondWith(200, ['id' => 'cs_test_ok', 'object' => 'checkout.session', 'url' => 'https://checkout.stripe.test/pay/cs_test_ok']);

        $response = $this->send('POST', '/checkout');

        $order = $this->onlyOrder();
        $this->assertSame('https://checkout.stripe.test/pay/cs_test_ok', $response->header('Location'));
        $this->assertSame('pending', $order['status']);
        $this->assertSame('cs_test_ok', $order['stripeSessionId']);
        $this->assertSame(2500, $this->stripe->requests[0]['params']['line_items'][0]['price_data']['unit_amount']);
    }

    public function testCatalogRendersInPolish(): void
    {
        $_SESSION['locale'] = 'pl';

        $page = $this->send('GET', '/products')->content();

        $this->assertStringContainsString('<html lang="pl">', $page);
        $this->assertStringContainsString('Katalog', $page);
        $this->assertStringContainsString('Wyczysc filtry', $page);
        $this->assertStringContainsString('Ulubione', $page);
        $this->assertStringContainsString('Zbudowany na wlasnym mikroframeworku PHP.', $page);
        $this->assertStringNotContainsString('The catalog', $page);
        $this->assertStringNotContainsString('Built on a hand-rolled', $page);
    }

    public function testLanguageQueryAndSwitchStoreTheLocale(): void
    {
        $page = $this->send('GET', '/', ['lang' => 'pl'])->content();
        $this->assertSame('pl', $_SESSION['locale']);
        $this->assertStringContainsString('Przegladaj katalog', $page);
        $this->assertStringContainsString('href="/locale/en"', $page);

        $response = $this->send('GET', '/locale/en');
        $this->assertSame(302, $response->status());
        $this->assertSame('en', $_SESSION['locale']);
        $this->assertStringContainsString('Browse the catalog', $this->send('GET', '/')->content());
    }

    public function testCustomerOrderPageAndAdminRenderInPolish(): void
    {
        $this->fillCart(1);
        $_SESSION['couponCode'] = 'FREE100';
        $this->send('POST', '/checkout');
        $orderId = (int) $this->onlyOrder()['id'];
        (new UserRepository($this->db))->setRole($this->userId, 'admin');
        $_SESSION['locale'] = 'pl';

        $customerPage = $this->send('GET', '/account/orders/' . $orderId)->content();
        $this->assertStringContainsString('Zamowienie #' . $orderId, $customerPage);
        $this->assertStringContainsString('oplacone', $customerPage);

        $adminPage = $this->send('GET', '/admin/orders/' . $orderId)->content();
        $this->assertStringContainsString('<html lang="pl">', $adminPage);
        $this->assertStringContainsString('Szczegoly zamowienia', $adminPage);
        $this->assertStringContainsString('Oznacz jako zrealizowane', $adminPage);
        $this->assertStringNotContainsString('Order details', $adminPage);
    }
}
