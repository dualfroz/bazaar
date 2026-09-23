<?php
$maxRevenue = 0;
foreach ($salesByDay as $day) {
    $maxRevenue = max($maxRevenue, (int) $day['revenueCents']);
}
?>
<div class="admin__head">
    <div>
        <h1 style="margin:0"><?= e($t('admin.nav.analytics')) ?></h1>
        <p class="muted" style="margin:0"><?= e($t('admin.analytics.lead')) ?></p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat">
        <div class="stat__label"><?= e($t('admin.dashboard.revenue')) ?></div>
        <div class="stat__value"><?= e(formatMoney((int) $revenueCents, $currency)) ?></div>
        <div class="stat__hint"><?= e($t('admin.analytics.revenueHint')) ?></div>
    </div>
    <div class="stat">
        <div class="stat__label"><?= e($t('admin.analytics.unitsSold')) ?></div>
        <div class="stat__value"><?= (int) $unitsSold ?></div>
        <div class="stat__hint"><?= e($t('admin.analytics.unitsHint')) ?></div>
    </div>
    <div class="stat">
        <div class="stat__label"><?= e($t('admin.analytics.averageOrder')) ?></div>
        <div class="stat__value"><?= e(formatMoney((int) $averageOrderCents, $currency)) ?></div>
        <div class="stat__hint"><?= e($t('admin.analytics.averageHint')) ?></div>
    </div>
    <div class="stat">
        <div class="stat__label"><?= e($t('admin.dashboard.lowStock')) ?></div>
        <div class="stat__value"><?= count($lowStock) ?></div>
        <div class="stat__hint"><?= e($t('admin.analytics.lowStockHint')) ?></div>
    </div>
</div>

<div class="panel panel--pad mt-lg">
    <h3 style="margin-top:0"><?= e($t('admin.analytics.revenueChart')) ?></h3>
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
        <div class="section-head"><h3 style="margin:0"><?= e($t('admin.analytics.topProducts')) ?></h3></div>
        <?php if (empty($topProducts)): ?>
            <div class="empty"><p class="muted" style="margin:0"><?= e($t('admin.analytics.noSales')) ?></p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th><?= e($t('admin.products.product')) ?></th><th class="num"><?= e($t('admin.analytics.units')) ?></th><th class="num"><?= e($t('admin.dashboard.revenue')) ?></th></tr></thead>
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
        <div class="section-head"><h3 style="margin:0"><?= e($t('admin.analytics.couponUsage')) ?></h3></div>
        <?php if (empty($couponUsage)): ?>
            <div class="empty"><p class="muted" style="margin:0"><?= e($t('admin.analytics.noCoupons')) ?></p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th><?= e($t('admin.coupons.code')) ?></th><th class="num"><?= e($t('admin.nav.orders')) ?></th><th class="num"><?= e($t('cart.discount')) ?></th></tr></thead>
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
