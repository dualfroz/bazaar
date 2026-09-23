<?php

namespace Bazaar\Controllers;

use Bazaar\Core\Config;
use Bazaar\Core\Response;
use Bazaar\Services\InsufficientStockException;
use Bazaar\Services\StripeGateway;

final class CheckoutController extends Controller
{
    public function index(): Response
    {
        $subtotalCents = $this->ctx->cart->totalCents();
        $discount = $this->resolveDiscount($subtotalCents);

        return $this->view('checkout.index', [
            'lines' => $this->ctx->cart->lines(),
            'subtotalCents' => $subtotalCents,
            'discountCents' => $discount['discountCents'],
            'couponCode' => $discount['couponCode'],
            'totalCents' => $subtotalCents - $discount['discountCents'],
        ]);
    }

    public function create(): Response
    {
        $cart = $this->ctx->cart;
        if ($cart->isEmpty()) {
            return $this->redirect('/cart');
        }

        $subtotalCents = $cart->totalCents();
        $discount = $this->resolveDiscount($subtotalCents);
        $isFree = $subtotalCents - $discount['discountCents'] === 0;

        if (!$isFree && !Config::stripeConfigured()) {
            $this->ctx->session->flash('error', $this->t('checkout.stripeMissing'));
            return $this->redirect('/checkout');
        }

        try {
            $orderId = $this->ctx->orderService->createFromCart(
                $this->ctx->auth->id(),
                $cart,
                (string) Config::get('CURRENCY', 'usd'),
                $discount['discountCents'] > 0 ? $discount : null,
                $this->ctx->translator->locale()
            );
        } catch (InsufficientStockException $error) {
            $this->ctx->session->flash('error', $this->t('checkout.insufficientStock', ['product' => $error->productName]));
            return $this->redirect('/cart');
        }

        return $isFree ? $this->completeFreeOrder($orderId) : $this->startStripeCheckout($orderId);
    }

    private function completeFreeOrder(int $orderId): Response
    {
        try {
            $this->ctx->orderService->markPaid($orderId);
        } catch (InsufficientStockException $error) {
            $this->ctx->orderService->markFailed($orderId);
            $this->ctx->session->flash('error', $this->t('checkout.insufficientStock', ['product' => $error->productName]));
            return $this->redirect('/cart');
        }

        $this->ctx->cart->clear();
        $this->ctx->session->flash('success', $this->t('checkout.freeOrderPlaced', ['id' => $orderId]));
        return $this->redirect('/account/orders/' . $orderId);
    }

    private function startStripeCheckout(int $orderId): Response
    {
        $appUrl = rtrim((string) Config::get('APP_URL'), '/');
        try {
            $session = StripeGateway::fromConfig()->createCheckoutSession(
                $this->ctx->orders->find($orderId),
                $this->ctx->orders->items($orderId),
                $appUrl . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
                $appUrl . '/checkout/cancel'
            );
        } catch (\Throwable) {
            $this->ctx->orderService->markFailed($orderId);
            $this->ctx->session->flash('error', $this->t('checkout.paymentStartFailed'));
            return $this->redirect('/checkout');
        }

        $this->ctx->orderService->attachStripeSession($orderId, $session['id']);
        return $this->redirect($session['url']);
    }

    public function success(): Response
    {
        $sessionId = (string) $this->ctx->request->query('session_id', '');
        if ($sessionId === '' || !Config::stripeConfigured()) {
            return $this->redirect('/account/orders');
        }

        try {
            $details = StripeGateway::fromConfig()->retrieveSession($sessionId);
        } catch (\Throwable) {
            return $this->redirect('/account/orders');
        }

        $orderId = $details['orderId'];
        $order = $orderId !== null
            ? $this->ctx->orders->findForUser($orderId, $this->ctx->auth->id())
            : null;

        if ($order !== null && $details['paymentStatus'] === 'paid') {
            $this->ctx->orderService->markPaid($orderId, $sessionId, $details['paymentIntentId']);
            $this->ctx->cart->clear();
            $order = $this->ctx->orders->find($orderId);
        }

        return $this->view('checkout.success', [
            'order' => $order,
            'paymentStatus' => $details['paymentStatus'],
        ]);
    }

    public function cancel(): Response
    {
        return $this->view('checkout.cancel', []);
    }

    public function webhook(): Response
    {
        if (!Config::stripeConfigured()) {
            return Response::json(['ignored' => true], 200);
        }

        $payload = $this->ctx->request->rawBody();
        $signature = (string) $this->ctx->request->header('Stripe-Signature');

        try {
            $event = StripeGateway::fromConfig()->parseWebhook($payload, $signature);
        } catch (\Throwable) {
            return Response::json(['error' => 'invalid signature'], 400);
        }

        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['session'];
            $orderId = isset($session->metadata->orderId)
                ? (int) $session->metadata->orderId
                : (int) $session->client_reference_id;
            if ($orderId > 0) {
                $this->ctx->orderService->markPaid($orderId, $session->id, $session->payment_intent);
            }
        }

        return Response::json(['received' => true], 200);
    }

    private function resolveDiscount(int $subtotalCents): array
    {
        $couponCode = (string) $this->ctx->session->get('couponCode', '');
        if ($couponCode !== '') {
            $result = $this->ctx->couponService->evaluate($couponCode, $subtotalCents);
            if ($result->isValid()) {
                return ['couponCode' => $result->coupon['code'], 'discountCents' => $result->discountCents];
            }
            $this->ctx->session->forget('couponCode');
        }
        return ['couponCode' => '', 'discountCents' => 0];
    }
}
