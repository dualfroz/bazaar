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

        if (!Config::stripeConfigured()) {
            $this->ctx->session->flash('error', 'Stripe is not configured yet. Add test keys in .env to accept payments.');
            return $this->redirect('/checkout');
        }

        $currency = (string) Config::get('CURRENCY', 'usd');
        $discount = $this->resolveDiscount($cart->totalCents());
        try {
            $orderId = $this->ctx->orderService->createFromCart(
                $this->ctx->auth->id(),
                $cart,
                $currency,
                $discount['discountCents'] > 0 ? $discount : null
            );
        } catch (InsufficientStockException $error) {
            $this->ctx->session->flash('error', $error->getMessage());
            return $this->redirect('/cart');
        }

        $appUrl = rtrim((string) Config::get('APP_URL'), '/');
        try {
            $session = $this->gateway()->createCheckoutSession(
                $orderId,
                $cart->lines(),
                $appUrl . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
                $appUrl . '/checkout/cancel'
            );
        } catch (\Throwable $error) {
            $this->ctx->session->flash('error', 'Could not reach the payment provider. Please try again.');
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
            $details = $this->gateway()->retrieveSession($sessionId);
        } catch (\Throwable) {
            return $this->redirect('/account/orders');
        }

        $orderId = $details['orderId'];
        $order = $orderId !== null
            ? $this->ctx->orders->findForUser($orderId, $this->ctx->auth->id())
            : null;

        if ($order !== null && $details['paymentStatus'] === 'paid') {
            $this->ctx->orderService->markPaid($orderId, $sessionId);
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
            $event = $this->gateway()->parseWebhook($payload, $signature);
        } catch (\Throwable) {
            return Response::json(['error' => 'invalid signature'], 400);
        }

        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['session'];
            $orderId = isset($session->metadata->orderId)
                ? (int) $session->metadata->orderId
                : (int) $session->client_reference_id;
            if ($orderId > 0) {
                $this->ctx->orderService->markPaid($orderId, $session->id);
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

    private function gateway(): StripeGateway
    {
        return new StripeGateway(
            (string) Config::get('STRIPE_SECRET_KEY'),
            (string) Config::get('STRIPE_WEBHOOK_SECRET'),
            (string) Config::get('CURRENCY', 'usd')
        );
    }
}
