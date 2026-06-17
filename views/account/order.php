<section class="block">
    <div class="wrap" style="max-width:760px">
        <p class="small muted"><a href="/account/orders">Orders</a> / #<?= (int) $order['id'] ?></p>
        <div class="row row--between" style="align-items:flex-end">
            <h1 style="margin:6px 0">Order #<?= (int) $order['id'] ?></h1>
            <span class="badge badge--<?= e($order['status']) ?>"><?= e($order['status']) ?></span>
        </div>
        <p class="muted">Placed <?= e(date('F j, Y \a\t H:i', strtotime($order['createdAt']))) ?></p>

        <div class="panel mt-lg">
            <div class="table-wrap" style="border:none">
                <table class="table">
                    <thead>
                        <tr><th>Item</th><th class="num">Unit price</th><th class="num">Qty</th><th class="num">Total</th></tr>
                    </thead>
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
                <div class="summary-row summary-row--total" style="border:none;margin:0;padding:0">
                    <span>Total</span><span class="mono"><?= e(formatMoney((int) $order['totalCents'], $order['currency'])) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>
