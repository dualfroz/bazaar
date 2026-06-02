<?php

namespace Bazaar\Controllers\Admin;

use Bazaar\Controllers\Controller;
use Bazaar\Core\Response;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin.dashboard', [
            'productCount' => $this->ctx->products->count(),
            'pendingCount' => $this->ctx->orders->countByStatus('pending'),
            'paidCount' => $this->ctx->orders->countByStatus('paid'),
            'fulfilledCount' => $this->ctx->orders->countByStatus('fulfilled'),
            'orderCount' => $this->ctx->orders->count(),
            'revenueCents' => $this->ctx->orders->revenueCents(),
            'lowStock' => $this->ctx->products->lowStock(5),
            'recentOrders' => $this->ctx->orders->recent(6),
        ], 'admin.layout');
    }
}
