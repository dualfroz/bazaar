<?php
$maxRevenue = 0;
foreach ($salesByDay as $day) {
    $maxRevenue = max($maxRevenue, (int) $day['revenueCents']);
}
?>
<div class="admin__head">
    <div>
        <h1 style="margin:0">Analytics</h1>
        <p class="muted" style="margin:0">Sales, best sellers and stock health.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat">
        <div class="stat__label">Revenue</div>
        <div class="stat__value"><?= e(formatMoney((int) $revenueCents, $currency)) ?></div>
        <div class="stat__hint">Paid and fulfilled</div>
    </div>
    <div class="stat">
        <div class="stat__label">Units sold</div>
        <div class="stat__value"><?= (int) $unitsSold ?></div>
        <div class="stat__hint">Across all orders</div>
    </div>
    <div class="stat">
        <div class="stat__label">Avg order value</div>
        <div class="stat__value"><?= e(formatMoney((int) $averageOrderCents, $currency)) ?></div>
        <div class="stat__hint">Per paid order</div>
    </div>
    <div class="stat">
        <div class="stat__label">Low stock</div>
        <div class="stat__value"><?= count($lowStock) ?></div>
        <div class="stat__hint">At or below 5 units</div>
    </div>
</div>

<div class="panel panel--pad mt-lg">
    <h3 style="margin-top:0">Revenue, last 14 days</h3>
    <div class="chart">
        <?php foreach ($salesByDay as $day): ?>
            <?php $height = $maxRevenue > 0 ? max(2, (int) round((int) $day['revenueCents'] / $maxRevenue * 100)) : 2; ?>
            <div class="chart__col" title="<?= e($day['day']) ?>: <?= e(formatMoney((int) $day['revenueCents'], $currency)) ?>">
                <div class="chart__bar" style="height:<?= $height ?>%"></div>
                <span class="chart__label"><?= e(substr($day['day'], 5)) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="split mt-lg" style="grid-template-columns:1fr 1fr">
    <div>
        <div class="section-head"><h3 style="margin:0">Top products</h3></div>
        <?php if (empty($topProducts)): ?>
            <div class="empty"><p class="muted" style="margin:0">No sales yet.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Product</th><th class="num">Units</th><th class="num">Revenue</th></tr></thead>
                    <tbody>
                        <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td style="font-weight:600"><?= e($product['name']) ?></td>
                                <td class="num"><?= (int) $product['unitsSold'] ?></td>
                                <td class="num mono"><?= e(formatMoney((int) $product['revenueCents'], $currency)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="section-head"><h3 style="margin:0">Coupon usage</h3></div>
        <?php if (empty($couponUsage)): ?>
            <div class="empty"><p class="muted" style="margin:0">No coupons redeemed yet.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Code</th><th class="num">Orders</th><th class="num">Discount</th></tr></thead>
                    <tbody>
                        <?php foreach ($couponUsage as $coupon): ?>
                            <tr>
                                <td class="mono"><?= e($coupon['couponCode']) ?></td>
                                <td class="num"><?= (int) $coupon['orderCount'] ?></td>
                                <td class="num mono"><?= e(formatMoney((int) $coupon['discountCents'], $currency)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
