<?php

namespace Bazaar\Services;

use Bazaar\Core\Config;
use Bazaar\Core\Database;
use Bazaar\Mail\Mailer;
use Bazaar\Mail\NullMailer;
use Bazaar\Mail\OrderMailer;
use Bazaar\Repositories\CouponRepository;
use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Repositories\VariantRepository;
use Stripe\Exception\ApiErrorException;

final class OrderService
{
    private VariantRepository $variants;
    private CouponRepository $coupons;
    private UserRepository $users;
    private OrderMailer $orderMailer;

    public function __construct(
        private readonly Database $db,
        private readonly OrderRepository $orders,
        private readonly ProductRepository $products,
        ?VariantRepository $variants = null,
        ?CouponRepository $coupons = null,
        ?Mailer $mailer = null,
        private readonly ?StripeGateway $payments = null
    ) {
        $this->variants = $variants ?? new VariantRepository($db);
        $this->coupons = $coupons ?? new CouponRepository($db);
        $this->users = new UserRepository($db);
        $this->orderMailer = new OrderMailer(
            $mailer ?? new NullMailer(),
            (string) Config::get('CURRENCY', 'usd'),
            'Bazaar',
            (string) Config::get('MAIL_FROM', 'orders@bazaar.test')
        );
    }

    public function createFromCart(int $userId, Cart $cart, string $currency, ?array $discount = null, string $locale = 'en'): int
    {
        $lines = $cart->lines();
        if ($lines === []) {
            throw new \RuntimeException('Cannot create an order from an empty cart');
        }

        foreach ($lines as $line) {
            if ($line['quantity'] > $line['availableStock']) {
                throw new InsufficientStockException($line['name']);
            }
        }

        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += $line['lineTotalCents'];
        }
        $discountCents = $discount !== null ? min((int) $discount['discountCents'], $subtotal) : 0;
        $couponCode = $discount !== null ? ($discount['couponCode'] ?? null) : null;
        $total = $subtotal - $discountCents;

        return $this->db->transaction(function () use ($userId, $total, $currency, $discountCents, $couponCode, $locale, $lines): int {
            $orderId = $this->orders->create($userId, $total, $currency, $discountCents, $couponCode, $locale);
            foreach ($lines as $line) {
                $this->orders->addItem(
                    $orderId,
                    $line['productId'],
                    $line['name'],
                    $line['unitPriceCents'],
                    $line['quantity'],
                    $line['variantId'],
                    $line['variantLabel']
                );
            }
            return $orderId;
        });
    }

    public function markPaid(int $orderId, ?string $stripeSessionId = null, ?string $paymentIntentId = null): void
    {
        $order = $this->orders->find($orderId);
        if ($order === null || $order['status'] !== 'pending') {
            return;
        }

        $items = $this->orders->items($orderId);
        foreach ($items as $orderItem) {
            $available = $this->availableStock($orderItem);
            if ($available < (int) $orderItem['quantity']) {
                throw new InsufficientStockException($orderItem['productName']);
            }
        }

        $this->db->transaction(function () use ($orderId, $items, $stripeSessionId, $paymentIntentId): void {
            foreach ($items as $orderItem) {
                $this->decrementStock($orderItem);
            }
            $this->orders->setStatus($orderId, 'paid', 'paidAt', $stripeSessionId);
            if ($paymentIntentId !== null) {
                $this->orders->recordPaymentIntent($orderId, $paymentIntentId);
            }
        });

        $this->redeemCoupon($order['couponCode'] ?? null);
        $this->notify($orderId, 'confirmation');
    }

    public function markFailed(int $orderId): void
    {
        $this->orders->transition($orderId, 'pending', 'failed', 'failedAt');
    }

    public function markFulfilled(int $orderId): void
    {
        $order = $this->orders->find($orderId);
        if ($order === null) {
            throw new OrderException(OrderException::notFound);
        }
        if ($order['status'] !== 'paid') {
            throw new OrderException(OrderException::notPaid);
        }
        $this->orders->setStatus($orderId, 'fulfilled', 'fulfilledAt');
        $this->notify($orderId, 'status');
    }

    public function cancelForCustomer(int $orderId, int $userId): void
    {
        $order = $this->orders->findForUser($orderId, $userId);
        if ($order === null) {
            throw new OrderException(OrderException::notFound);
        }
        if ($order['status'] !== 'pending') {
            throw new OrderException(OrderException::notPending);
        }
        $this->cancel($order);
    }

    public function cancelAsStaff(int $orderId): void
    {
        $order = $this->orders->find($orderId);
        if ($order === null) {
            throw new OrderException(OrderException::notFound);
        }
        if (!in_array($order['status'], ['pending', 'paid'], true)) {
            throw new OrderException(OrderException::notCancellable);
        }
        $this->cancel($order);
    }

    public function refundsThroughStripe(array $order): bool
    {
        return $this->payments !== null
            && $order['status'] === 'paid'
            && (int) $order['totalCents'] > 0
            && (!empty($order['paymentIntentId']) || !empty($order['stripeSessionId']));
    }

    public function attachStripeSession(int $orderId, string $sessionId): void
    {
        $this->orders->setStatus($orderId, 'pending', null, $sessionId);
    }

    private function cancel(array $order): void
    {
        $orderId = (int) $order['id'];

        $this->db->transaction(function () use ($order, $orderId): void {
            if (!$this->orders->transition($orderId, $order['status'], 'cancelled', 'cancelledAt')) {
                throw new OrderException(OrderException::changed);
            }
            if ($order['status'] !== 'paid') {
                return;
            }
            foreach ($this->orders->items($orderId) as $orderItem) {
                $this->restoreStock($orderItem);
            }
            $this->releaseCoupon($order['couponCode'] ?? null);
            if ($this->refundsThroughStripe($order)) {
                $this->refund($order);
            }
        });

        $this->notify($orderId, 'status');
    }

    private function refund(array $order): void
    {
        try {
            $refund = $this->payments->refund($order);
        } catch (ApiErrorException $error) {
            throw new OrderException(OrderException::refundFailed, $error);
        }
        $this->orders->recordRefund((int) $order['id'], $refund['id'], $refund['amountCents']);
    }

    private function availableStock(array $orderItem): int
    {
        if ($orderItem['variantId'] !== null) {
            $variant = $this->variants->find((int) $orderItem['variantId']);
            return $variant === null ? 0 : (int) $variant['stockQuantity'];
        }
        $product = $this->products->find((int) $orderItem['productId']);
        return $product === null ? 0 : (int) $product['stockQuantity'];
    }

    private function decrementStock(array $orderItem): void
    {
        if ($orderItem['variantId'] !== null) {
            $this->variants->decrementStock((int) $orderItem['variantId'], (int) $orderItem['quantity']);
            return;
        }
        $this->products->decrementStock((int) $orderItem['productId'], (int) $orderItem['quantity']);
    }

    private function restoreStock(array $orderItem): void
    {
        if ($orderItem['variantId'] !== null) {
            $this->variants->incrementStock((int) $orderItem['variantId'], (int) $orderItem['quantity']);
            return;
        }
        $this->products->incrementStock((int) $orderItem['productId'], (int) $orderItem['quantity']);
    }

    private function redeemCoupon(?string $couponCode): void
    {
        if ($couponCode === null || $couponCode === '') {
            return;
        }
        $coupon = $this->coupons->findByCode($couponCode);
        if ($coupon !== null) {
            $this->coupons->recordRedemption((int) $coupon['id']);
        }
    }

    private function releaseCoupon(?string $couponCode): void
    {
        if ($couponCode === null || $couponCode === '') {
            return;
        }
        $coupon = $this->coupons->findByCode($couponCode);
        if ($coupon !== null) {
            $this->coupons->releaseRedemption((int) $coupon['id']);
        }
    }

    private function notify(int $orderId, string $kind): void
    {
        $order = $this->orders->find($orderId);
        $user = $this->users->findById((int) $order['userId']);
        if ($user === null) {
            return;
        }
        if ($kind === 'confirmation') {
            $this->orderMailer->orderConfirmation($user['email'], $order, $this->orders->items($orderId));
            return;
        }
        $this->orderMailer->statusUpdate($user['email'], $order);
    }
}
