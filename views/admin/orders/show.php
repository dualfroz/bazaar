<div class="admin__head">
    <div>
        <p class="small muted"><a href="/admin/orders">Orders</a> / #<?= (int) $order['id'] ?></p>
        <h1 style="margin:0">Order #<?= (int) $order['id'] ?> <span class="badge badge--<?= e($order['status']) ?>" style="vertical-align:middle"><?= e($order['status']) ?></span></h1>
    </div>
    <?php if ($order['status'] === 'paid'): ?>
        <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/fulfill">
            <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
            <button class="btn btn--primary" type="submit">Mark as fulfilled</button>
        </form>
    <?php endif; ?>
</div>

<div class="split" style="grid-template-columns:1.6fr 1fr">
    <div class="panel">
        <div class="table-wrap" style="border:none">
            <table class="table">
                <thead><tr><th>Item</th><th class="num">Unit price</th><th class="num">Qty</th><th class="num">Total</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $orderItem): ?>
                        <tr>
                            <td style="font-weight:600"><?= e($orderItem['productName']) ?></td>
                            <td class="num mono"><?= e(formatMoney((int) $orderItem['unitPriceCents'], $order['currency'])) ?></td>
                            <td class="num mono"><?= (int) $orderItem['quantity'] ?></td>
                            <td class="num mono"><?= e(formatMoney((int) $orderItem['unitPriceCents'] * (int) $orderItem['quantity'], $order['currency'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="panel--pad" style="border-top:1px solid var(--line)">
            <div class="summary-row summary-row--total" style="border:none;margin:0;padding:0"><span>Total</span><span class="mono"><?= e(formatMoney((int) $order['totalCents'], $order['currency'])) ?></span></div>
        </div>
    </div>

    <div class="panel panel--pad">
        <h3>Order details</h3>
        <div class="summary-row"><span>Status</span><span><span class="badge badge--<?= e($order['status']) ?>"><?= e($order['status']) ?></span></span></div>
        <div class="summary-row"><span>Placed</span><span><?= e(date('M j, Y H:i', strtotime($order['createdAt']))) ?></span></div>
        <?php if (!empty($order['paidAt'])): ?>
            <div class="summary-row"><span>Paid</span><span><?= e(date('M j, Y H:i', strtotime($order['paidAt']))) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($order['fulfilledAt'])): ?>
            <div class="summary-row"><span>Fulfilled</span><span><?= e(date('M j, Y H:i', strtotime($order['fulfilledAt']))) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($order['stripeSessionId'])): ?>
            <div class="summary-row"><span>Stripe session</span><span class="mono small"><?= e(substr($order['stripeSessionId'], 0, 18)) ?>...</span></div>
        <?php endif; ?>
    </div>
</div>
