<?php

namespace Bazaar\Controllers\Admin;

use Bazaar\Controllers\Controller;
use Bazaar\Core\Response;

final class AnalyticsController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin.analytics', [
            'salesByDay' => $this->ctx->analytics->salesByDay(14),
            'topProducts' => $this->ctx->analytics->topProducts(5),
            'averageOrderCents' => $this->ctx->analytics->averageOrderValueCents(),
            'unitsSold' => $this->ctx->analytics->unitsSold(),
            'couponUsage' => $this->ctx->analytics->couponUsage(),
            'revenueCents' => $this->ctx->orders->revenueCents(),
            'lowStock' => $this->ctx->products->lowStock(5),
        ], 'admin.layout');
    }
}
