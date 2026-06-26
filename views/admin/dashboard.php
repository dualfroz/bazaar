<div class="admin__head">
    <div>
        <h1 style="margin:0">Dashboard</h1>
        <p class="muted" style="margin:0">Store overview at a glance.</p>
    </div>
    <a class="btn btn--primary" href="/admin/products/create">New product</a>
</div>

<div class="stat-grid">
    <div class="stat">
        <div class="stat__label">Revenue</div>
        <div class="stat__value"><?= e(formatMoney($revenueCents, $currency)) ?></div>
        <div class="stat__hint">Paid and fulfilled orders</div>
    </div>
    <div class="stat">
        <div class="stat__label">Orders</div>
        <div class="stat__value"><?= (int) $orderCount ?></div>
        <div class="stat__hint"><?= (int) $pendingCount ?> pending &middot; <?= (int) $paidCount ?> paid &middot; <?= (int) $fulfilledCount ?> fulfilled</div>
    </div>
    <div class="stat">
        <div class="stat__label">Products</div>
        <div class="stat__value"><?= (int) $productCount ?></div>
        <div class="stat__hint"><?= count($lowStock) ?> low on stock</div>
    </div>
    <div class="stat">
        <div class="stat__label">Awaiting fulfilment</div>
        <div class="stat__value"><?= (int) $paidCount ?></div>
        <div class="stat__hint">Paid orders ready to ship</div>
    </div>
</div>

<div class="split mt-lg" style="grid-template-columns:1.5fr 1fr">
    <div>
        <div class="section-head"><h3 style="margin:0">Recent orders</h3><a class="btn btn--ghost btn--sm" href="/admin/orders">All orders</a></div>
        <?php if (empty($recentOrders)): ?>
            <div class="empty"><p class="muted" style="margin:0">No orders yet.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th class="num">Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><a href="/admin/orders/<?= (int) $order['id'] ?>" style="font-weight:600">#<?= (int) $order['id'] ?></a></td>
                                <td class="muted"><?= e($order['userEmail'] ?? 'Guest') ?></td>
                                <td><span class="badge badge--<?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
                                <td class="num mono"><?= e(formatMoney((int) $order['totalCents'], $order['currency'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="section-head"><h3 style="margin:0">Low stock</h3><a class="btn btn--ghost btn--sm" href="/admin/products">Manage</a></div>
        <?php if (empty($lowStock)): ?>
            <div class="empty"><p class="muted" style="margin:0">Every product is well stocked.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Product</th><th class="num">Stock</th></tr></thead>
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
