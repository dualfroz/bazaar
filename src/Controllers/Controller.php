<?php

namespace Bazaar\Controllers;

use Bazaar\Core\Config;
use Bazaar\Core\Context;
use Bazaar\Core\Response;
use Bazaar\Services\OrderException;
use Bazaar\Services\StripeGateway;

abstract class Controller
{
    public function __construct(protected readonly Context $ctx)
    {
    }

    protected function view(string $template, array $data = [], string $layout = 'layout'): Response
    {
        return $this->ctx->view->render($template, $data, $layout);
    }

    protected function redirect(string $location): Response
    {
        return Response::redirect($location);
    }

    protected function back(string $fallback = '/'): Response
    {
        $referer = $this->ctx->request->header('Referer');
        return Response::redirect($referer ?? $fallback);
    }

    protected function notFound(): Response
    {
        return $this->ctx->view->render('errors.404', [], 'layout', 404);
    }

    protected function t(string $key, array $replacements = []): string
    {
        return $this->ctx->translator->get($key, $replacements);
    }

    protected function orderError(OrderException $error): string
    {
        return $this->t('orderErrors.' . $error->reason);
    }

    protected function cancelOrder(array $order, callable $cancel, string $redirectTo): Response
    {
        $paymentError = $this->closeCheckoutSession($order);
        if ($paymentError !== null) {
            $this->ctx->session->flash('error', $paymentError);
            return $this->redirect($redirectTo);
        }

        try {
            $cancel();
            $this->ctx->session->flash('success', $this->cancelledMessage($order, $this->ctx->orders->find((int) $order['id'])));
        } catch (OrderException $error) {
            $this->ctx->session->flash('error', $this->orderError($error));
        }
        return $this->redirect($redirectTo);
    }

    private function cancelledMessage(array $before, array $cancelled): string
    {
        if (!empty($cancelled['refundedAt'])) {
            return $this->t('orders.cancelledAndRefunded', [
                'amount' => formatMoney((int) $cancelled['refundedCents'], $cancelled['currency']),
            ]);
        }
        if ($before['status'] === 'paid' && (int) $before['totalCents'] > 0) {
            return $this->t('orders.cancelledRefundManually');
        }
        return $this->t('orders.cancelled');
    }

    private function closeCheckoutSession(array $order): ?string
    {
        if ($order['status'] !== 'pending' || empty($order['stripeSessionId']) || !Config::stripeConfigured()) {
            return null;
        }

        try {
            $closed = StripeGateway::fromConfig()->closeCheckoutSession($order['stripeSessionId']);
        } catch (\Throwable) {
            return $this->t('payment.unreachable');
        }
        return $closed ? null : $this->t('orderErrors.paymentStarted');
    }
}
