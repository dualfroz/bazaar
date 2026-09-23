<?php
$formatDate = static fn (string $timestamp): string => date($t('format.dateTime'), strtotime($timestamp));
$totalLabel = formatMoney((int) $order['totalCents'], $order['currency']);
$cancelPrompt = match (true) {
    $order['status'] !== 'paid' => $t('admin.orders.confirmCancelPending'),
    $refundsThroughStripe => $t('admin.orders.confirmCancelRefund', ['amount' => $totalLabel]),
    default => $t('admin.orders.confirmCancelManual'),
};
$needsManualRefund = $order['status'] === 'cancelled' && !empty($order['paidAt']) && empty($order['refundedAt']) && (int) $order['totalCents'] > 0;
?>
<div class="admin__head">
    <div>
        <p class="small muted"><a href="/admin/orders"><?= e($t('admin.nav.orders')) ?></a> / #<?= (int) $order['id'] ?></p>
        <h1 style="margin:0"><?= e($t('orders.title', ['id' => (int) $order['id']])) ?> <span class="badge badge--<?= e($order['status']) ?>" style="vertical-align:middle"><?= e($t('status.' . $order['status'])) ?></span></h1>
    </div>
    <div class="row">
        <?php if (in_array($order['status'], ['pending', 'paid'], true)): ?>
            <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/cancel" onsubmit="return confirm(<?= e(json_encode($cancelPrompt)) ?>)">
                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                <button class="btn btn--danger" type="submit"><?= e($order['status'] === 'paid' && $refundsThroughStripe ? $t('admin.orders.cancelAndRefund') : $t('orders.cancel')) ?></button>
            </form>
        <?php endif; ?>
        <?php if ($order['status'] === 'paid'): ?>
            <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/fulfill">
                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                <button class="btn btn--primary" type="submit"><?= e($t('admin.orders.markFulfilled')) ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($order['status'] === 'paid' && !$refundsThroughStripe && (int) $order['totalCents'] > 0): ?>
    <div class="notice" style="margin-bottom:16px"><?= e($t('admin.orders.manualRefundNote')) ?></div>
<?php elseif ($needsManualRefund): ?>
    <div class="notice" style="margin-bottom:16px"><?= e($t('admin.orders.manualRefundDue', ['amount' => $totalLabel])) ?></div>
<?php endif; ?>

<div class="split" style="grid-template-columns:1.6fr 1fr">
    <div class="panel">
        <div class="table-wrap" style="border:none">
            <table class="table">
                <thead><tr><th><?= e($t('orders.item')) ?></th><th class="num"><?= e($t('orders.unitPrice')) ?></th><th class="num"><?= e($t('orders.quantity')) ?></th><th class="num"><?= e($t('orders.total')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($items as $orderItem): ?>
                        <tr>
                            <td style="font-weight:600">
                                <?= e($orderItem['productName']) ?>
                                <?php if (($orderItem['variantLabel'] ?? '') !== ''): ?><span class="muted small">(<?= e($orderItem['variantLabel']) ?>)</span><?php endif; ?>
                            </td>
                            <td class="num mono"><?= e(formatMoney((int) $orderItem['unitPriceCents'], $order['currency'])) ?></td>
                            <td class="num mono"><?= (int) $orderItem['quantity'] ?></td>
                            <td class="num mono"><?= e(formatMoney((int) $orderItem['unitPriceCents'] * (int) $orderItem['quantity'], $order['currency'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="panel--pad" style="border-top:1px solid var(--line)">
            <?php if ((int) $order['discountCents'] > 0): ?>
                <div class="summary-row summary-row--discount" style="border:none;margin:0 0 6px;padding:0"><span><?= e($t('cart.discount')) ?> (<?= e((string) $order['couponCode']) ?>)</span><span class="mono">-<?= e(formatMoney((int) $order['discountCents'], $order['currency'])) ?></span></div>
            <?php endif; ?>
            <div class="summary-row summary-row--total" style="border:none;margin:0;padding:0"><span><?= e($t('orders.total')) ?></span><span class="mono"><?= e($totalLabel) ?></span></div>
        </div>
    </div>

    <div class="panel panel--pad">
        <h3><?= e($t('admin.orders.details')) ?></h3>
        <div class="summary-row"><span><?= e($t('orders.status')) ?></span><span><span class="badge badge--<?= e($order['status']) ?>"><?= e($t('status.' . $order['status'])) ?></span></span></div>
        <div class="summary-row"><span><?= e($t('orders.placed')) ?></span><span><?= e($formatDate($order['createdAt'])) ?></span></div>
        <?php if (!empty($order['paidAt'])): ?>
            <div class="summary-row"><span><?= e($t('admin.orders.paidAt')) ?></span><span><?= e($formatDate($order['paidAt'])) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($order['fulfilledAt'])): ?>
            <div class="summary-row"><span><?= e($t('admin.orders.fulfilledAt')) ?></span><span><?= e($formatDate($order['fulfilledAt'])) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($order['failedAt'])): ?>
            <div class="summary-row"><span><?= e($t('admin.orders.failedAt')) ?></span><span><?= e($formatDate($order['failedAt'])) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($order['cancelledAt'])): ?>
            <div class="summary-row"><span><?= e($t('admin.orders.cancelledAt')) ?></span><span><?= e($formatDate($order['cancelledAt'])) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($order['refundedAt'])): ?>
            <div class="summary-row"><span><?= e($t('admin.orders.refunded')) ?></span><span class="mono"><?= e(formatMoney((int) $order['refundedCents'], $order['currency'])) ?></span></div>
            <div class="summary-row"><span><?= e($t('admin.orders.refundedAt')) ?></span><span><?= e($formatDate($order['refundedAt'])) ?></span></div>
            <div class="summary-row"><span><?= e($t('admin.orders.stripeRefund')) ?></span><span class="mono small"><?= e((string) $order['stripeRefundId']) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($order['stripeSessionId'])): ?>
            <div class="summary-row"><span><?= e($t('admin.orders.stripeSession')) ?></span><span class="mono small"><?= e(substr($order['stripeSessionId'], 0, 18)) ?>...</span></div>
        <?php endif; ?>
        <?php if (!empty($order['paymentIntentId'])): ?>
            <div class="summary-row"><span><?= e($t('admin.orders.paymentIntent')) ?></span><span class="mono small"><?= e(substr($order['paymentIntentId'], 0, 18)) ?>...</span></div>
        <?php endif; ?>
        <div class="summary-row"><span><?= e($t('admin.orders.locale')) ?></span><span class="mono small"><?= e(strtoupper((string) $order['locale'])) ?></span></div>
    </div>
</div>
