<?php

namespace Bazaar\Tests;

use Bazaar\Core\Session;
use Bazaar\Mail\LogMailer;
use Bazaar\Repositories\CouponRepository;
use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Repositories\VariantRepository;
use Bazaar\Services\Cart;
use Bazaar\Services\OrderException;
use Bazaar\Services\OrderService;
use Bazaar\Services\StripeGateway;
use Stripe\ApiRequestor;

final class StripeRefundTest extends DatabaseTestCase
{
    private FakeStripeHttpClient $stripe;
    private LogMailer $mailer;
    private ProductRepository $products;
    private CouponRepository $coupons;
    private OrderRepository $orders;
    private OrderService $service;
    private string $logPath;
    private int $userId;
    private int $productId;
    private int $couponId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripe = new FakeStripeHttpClient();
        ApiRequestor::setHttpClient($this->stripe);

        $this->logPath = sys_get_temp_dir() . '/bazaar-refund-' . uniqid() . '.log';
        $this->mailer = new LogMailer($this->logPath);
        $this->products = new ProductRepository($this->db);
        $this->coupons = new CouponRepository($this->db);
        $this->orders = new OrderRepository($this->db);
        $this->service = $this->serviceWith(new StripeGateway('sk_test_refunds', 'whsec_refunds'));
        $this->userId = (new UserRepository($this->db))->create('refund@bazaar.test', 'hash', 'Refund');
        $this->productId = $this->seedProduct('Kettle', 3000, 10);
        $this->couponId = $this->coupons->create([
            'code' => 'KETTLE10',
            'type' => 'percent',
            'value' => 10,
            'minSubtotalCents' => 0,
            'maxRedemptions' => null,
            'startsAt' => null,
            'expiresAt' => null,
            'isActive' => true,
        ]);
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        if (is_file($this->logPath)) {
            unlink($this->logPath);
        }
    }

    private function serviceWith(?StripeGateway $gateway): OrderService
    {
        return new OrderService(
            $this->db,
            $this->orders,
            $this->products,
            new VariantRepository($this->db),
            $this->coupons,
            $this->mailer,
            $gateway
        );
    }

    private function paidOrder(?string $paymentIntentId, int $discountCents = 600): int
    {
        $cart = new Cart(new Session(), $this->products);
        $cart->add($this->productId, 2);
        $orderId = $this->service->createFromCart($this->userId, $cart, 'usd', [
            'discountCents' => $discountCents,
            'couponCode' => 'KETTLE10',
        ]);
        $this->service->markPaid($orderId, 'cs_test_' . $orderId, $paymentIntentId);
        return $orderId;
    }

    private function stock(): int
    {
        return (int) $this->products->find($this->productId)['stockQuantity'];
    }

    private function redeemedCount(): int
    {
        return (int) $this->coupons->find($this->couponId)['redeemedCount'];
    }

    public function testRefundRequestTargetsPaymentIntentWithIdempotencyKey(): void
    {
        $gateway = new StripeGateway('sk_test_placeholder', 'whsec_placeholder');

        $request = $gateway->refundRequest(['id' => 42, 'totalCents' => 5400], 'pi_test_abc');

        $this->assertSame([
            'params' => [
                'payment_intent' => 'pi_test_abc',
                'amount' => 5400,
                'metadata' => ['orderId' => '42'],
            ],
            'options' => ['idempotency_key' => 'refund-pi_test_abc'],
        ], $request);
    }

    public function testStaffCancelRefundsPaidOrderThroughStripeAndRecordsIt(): void
    {
        $orderId = $this->paidOrder('pi_test_kettle');
        $this->assertSame(8, $this->stock());
        $this->assertSame(1, $this->redeemedCount());
        $this->stripe->respondWith(200, ['id' => 're_test_kettle', 'object' => 'refund', 'amount' => 5400, 'status' => 'succeeded']);

        $this->service->cancelAsStaff($orderId);

        $this->assertCount(1, $this->stripe->requests);
        $request = $this->stripe->requests[0];
        $this->assertSame('post', $request['method']);
        $this->assertSame('https://api.stripe.com/v1/refunds', $request['url']);
        $this->assertSame('pi_test_kettle', $request['params']['payment_intent']);
        $this->assertSame(5400, $request['params']['amount']);
        $this->assertSame((string) $orderId, $request['params']['metadata']['orderId']);
        $this->assertSame('refund-pi_test_kettle', $this->stripe->header($request, 'Idempotency-Key'));

        $order = $this->orders->find($orderId);
        $this->assertSame('cancelled', $order['status']);
        $this->assertSame('re_test_kettle', $order['stripeRefundId']);
        $this->assertSame(5400, (int) $order['refundedCents']);
        $this->assertNotEmpty($order['refundedAt']);
        $this->assertSame(10, $this->stock());
        $this->assertSame(0, $this->redeemedCount());

        $statusMail = $this->mailer->sent()[1];
        $this->assertStringContainsString('$54.00 was refunded', $statusMail->body);
    }

    public function testRefundUsesPaymentIntentFromCheckoutSessionWhenNotStored(): void
    {
        $orderId = $this->paidOrder(null);
        $this->stripe->respondWith(200, ['id' => 'cs_test_' . $orderId, 'object' => 'checkout.session', 'payment_intent' => 'pi_from_session']);
        $this->stripe->respondWith(200, ['id' => 're_test_session', 'object' => 'refund', 'amount' => 5400, 'status' => 'succeeded']);

        $this->service->cancelAsStaff($orderId);

        $this->assertCount(2, $this->stripe->requests);
        $this->assertSame('get', $this->stripe->requests[0]['method']);
        $this->assertStringContainsString('/v1/checkout/sessions/cs_test_' . $orderId, $this->stripe->requests[0]['url']);
        $this->assertSame('pi_from_session', $this->stripe->requests[1]['params']['payment_intent']);
        $this->assertSame('refund-pi_from_session', $this->stripe->header($this->stripe->requests[1], 'Idempotency-Key'));
        $this->assertSame('re_test_session', $this->orders->find($orderId)['stripeRefundId']);
    }

    public function testRejectedRefundLeavesOrderPaidAndStockUntouched(): void
    {
        $orderId = $this->paidOrder('pi_test_declined');
        $this->stripe->respondWith(400, ['error' => ['type' => 'invalid_request_error', 'message' => 'Charge already refunded']]);

        $reason = null;
        try {
            $this->service->cancelAsStaff($orderId);
        } catch (OrderException $error) {
            $reason = $error->reason;
        }

        $this->assertSame(OrderException::refundFailed, $reason);
        $order = $this->orders->find($orderId);
        $this->assertSame('paid', $order['status']);
        $this->assertEmpty($order['refundedAt']);
        $this->assertSame(8, $this->stock());
        $this->assertSame(1, $this->redeemedCount());
        $this->assertCount(1, $this->mailer->sent());
    }

    public function testWithoutStripeTheOrderIsCancelledWithoutRefund(): void
    {
        $this->service = $this->serviceWith(null);
        $orderId = $this->paidOrder('pi_test_manual');

        $this->assertFalse($this->service->refundsThroughStripe($this->orders->find($orderId)));
        $this->service->cancelAsStaff($orderId);

        $order = $this->orders->find($orderId);
        $this->assertSame('cancelled', $order['status']);
        $this->assertEmpty($order['refundedAt']);
        $this->assertSame(0, (int) $order['refundedCents']);
        $this->assertSame([], $this->stripe->requests);
        $this->assertSame(10, $this->stock());
    }

    public function testFreeOrderIsCancelledWithoutCallingStripe(): void
    {
        $orderId = $this->paidOrder(null, 6000);
        $this->assertSame(0, (int) $this->orders->find($orderId)['totalCents']);

        $this->service->cancelAsStaff($orderId);

        $this->assertSame([], $this->stripe->requests);
        $this->assertSame('cancelled', $this->orders->find($orderId)['status']);
        $this->assertSame(10, $this->stock());
    }
}
