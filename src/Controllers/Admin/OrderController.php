<?php

namespace Bazaar\Controllers\Admin;

use Bazaar\Controllers\Controller;
use Bazaar\Core\Response;
use Bazaar\Services\OrderException;

final class OrderController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin.orders.index', [
            'orders' => $this->ctx->orders->all(),
        ], 'admin.layout');
    }

    public function show(string $id): Response
    {
        $order = $this->ctx->orders->find((int) $id);
        if ($order === null) {
            return $this->notFound();
        }

        return $this->view('admin.orders.show', [
            'order' => $order,
            'items' => $this->ctx->orders->items((int) $id),
            'refundsThroughStripe' => $this->ctx->orderService->refundsThroughStripe($order),
        ], 'admin.layout');
    }

    public function fulfill(string $id): Response
    {
        try {
            $this->ctx->orderService->markFulfilled((int) $id);
            $this->ctx->session->flash('success', $this->t('orders.fulfilled'));
        } catch (OrderException $error) {
            $this->ctx->session->flash('error', $this->orderError($error));
        }
        return $this->redirect('/admin/orders/' . (int) $id);
    }

    public function cancel(string $id): Response
    {
        $order = $this->ctx->orders->find((int) $id);
        if ($order === null) {
            return $this->notFound();
        }

        return $this->cancelOrder(
            $order,
            fn () => $this->ctx->orderService->cancelAsStaff((int) $id),
            '/admin/orders/' . (int) $id
        );
    }
}
