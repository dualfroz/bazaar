<section class="block">
    <div class="wrap" style="max-width:760px">
        <p class="small muted"><a href="/account/orders"><?= e($t('nav.orders')) ?></a> / #<?= (int) $order['id'] ?></p>
        <div class="row row--between" style="align-items:flex-end">
            <h1 style="margin:6px 0"><?= e($t('orders.title', ['id' => (int) $order['id']])) ?></h1>
            <span class="badge badge--<?= e($order['status']) ?>"><?= e($t('status.' . $order['status'])) ?></span>
        </div>
        <div class="row row--between">
            <p class="muted"><?= e($t('orders.placedOn', ['date' => date($t('format.dateTime'), strtotime($order['createdAt']))])) ?></p>
            <?php if ($order['status'] === 'pending'): ?>
                <form method="post" action="/account/orders/<?= (int) $order['id'] ?>/cancel" onsubmit="return confirm(<?= e(json_encode($t('orders.confirmCancelPending'))) ?>)">
                    <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                    <button class="btn btn--danger btn--sm" type="submit"><?= e($t('orders.cancel')) ?></button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!empty($order['refundedAt'])): ?>
            <div class="notice mt"><?= e($t('orders.refundedOn', [
                'amount' => formatMoney((int) $order['refundedCents'], $order['currency']),
                'date' => date($t('format.dateTime'), strtotime($order['refundedAt'])),
            ])) ?></div>
        <?php endif; ?>

        <div class="panel mt-lg">
            <div class="table-wrap" style="border:none">
                <table class="table">
                    <thead>
                        <tr><th><?= e($t('orders.item')) ?></th><th class="num"><?= e($t('orders.unitPrice')) ?></th><th class="num"><?= e($t('orders.quantity')) ?></th><th class="num"><?= e($t('orders.total')) ?></th></tr>
                    </thead>
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
                    <div class="summary-row summary-row--discount" style="border:none;margin:0 0 6px;padding:0">
                        <span><?= e($t('cart.discount')) ?> (<?= e((string) $order['couponCode']) ?>)</span><span class="mono">-<?= e(formatMoney((int) $order['discountCents'], $order['currency'])) ?></span>
                    </div>
                <?php endif; ?>
                <div class="summary-row summary-row--total" style="border:none;margin:0;padding:0">
                    <span><?= e($t('orders.total')) ?></span><span class="mono"><?= e(formatMoney((int) $order['totalCents'], $order['currency'])) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>
