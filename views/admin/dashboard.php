<div class="admin__head">
    <div>
        <h1 style="margin:0"><?= e($t('admin.nav.dashboard')) ?></h1>
        <p class="muted" style="margin:0"><?= e($t('admin.dashboard.lead')) ?></p>
    </div>
    <a class="btn btn--primary" href="/admin/products/create"><?= e($t('admin.products.new')) ?></a>
</div>

<div class="stat-grid">
    <div class="stat">
        <div class="stat__label"><?= e($t('admin.dashboard.revenue')) ?></div>
        <div class="stat__value"><?= e(formatMoney($revenueCents, $currency)) ?></div>
        <div class="stat__hint"><?= e($t('admin.dashboard.revenueHint')) ?></div>
    </div>
    <div class="stat">
        <div class="stat__label"><?= e($t('admin.nav.orders')) ?></div>
        <div class="stat__value"><?= (int) $orderCount ?></div>
        <div class="stat__hint"><?= e($t('admin.dashboard.ordersHint', ['pending' => (int) $pendingCount, 'paid' => (int) $paidCount, 'fulfilled' => (int) $fulfilledCount])) ?></div>
    </div>
    <div class="stat">
        <div class="stat__label"><?= e($t('admin.nav.products')) ?></div>
        <div class="stat__value"><?= (int) $productCount ?></div>
        <div class="stat__hint"><?= e($t('admin.dashboard.lowStockHint', ['count' => count($lowStock)])) ?></div>
    </div>
    <div class="stat">
        <div class="stat__label"><?= e($t('admin.dashboard.awaiting')) ?></div>
        <div class="stat__value"><?= (int) $paidCount ?></div>
        <div class="stat__hint"><?= e($t('admin.dashboard.awaitingHint')) ?></div>
    </div>
</div>

<div class="split mt-lg" style="grid-template-columns:1.5fr 1fr">
    <div>
        <div class="section-head"><h3 style="margin:0"><?= e($t('admin.dashboard.recentOrders')) ?></h3><a class="btn btn--ghost btn--sm" href="/admin/orders"><?= e($t('admin.dashboard.allOrders')) ?></a></div>
        <?php if (empty($recentOrders)): ?>
            <div class="empty"><p class="muted" style="margin:0"><?= e($t('admin.orders.empty')) ?></p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th><?= e($t('orders.order')) ?></th><th><?= e($t('admin.orders.customer')) ?></th><th><?= e($t('orders.status')) ?></th><th class="num"><?= e($t('orders.total')) ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><a href="/admin/orders/<?= (int) $order['id'] ?>" style="font-weight:600">#<?= (int) $order['id'] ?></a></td>
                                <td class="muted"><?= e($order['userEmail'] ?? $t('admin.orders.guest')) ?></td>
                                <td><span class="badge badge--<?= e($order['status']) ?>"><?= e($t('status.' . $order['status'])) ?></span></td>
                                <td class="num mono"><?= e(formatMoney((int) $order['totalCents'], $order['currency'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="section-head"><h3 style="margin:0"><?= e($t('admin.dashboard.lowStock')) ?></h3><a class="btn btn--ghost btn--sm" href="/admin/products"><?= e($t('admin.dashboard.manage')) ?></a></div>
        <?php if (empty($lowStock)): ?>
            <div class="empty"><p class="muted" style="margin:0"><?= e($t('admin.dashboard.wellStocked')) ?></p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th><?= e($t('admin.products.product')) ?></th><th class="num"><?= e($t('admin.products.stock')) ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($lowStock as $product): ?>
                            <tr>
                                <td><a href="/admin/products/<?= (int) $product['id'] ?>/edit" style="font-weight:600"><?= e($product['name']) ?></a></td>
                                <td class="num">
                                    <?php $stock = (int) $product['stockQuantity']; ?>
                                    <span class="badge badge--<?= $stock <= 0 ? 'out' : 'low' ?>"><?= $stock ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
