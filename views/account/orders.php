<section class="block">
    <div class="wrap">
        <h1>Your orders</h1>

        <?php if (empty($orders)): ?>
            <div class="empty">
                <h3>No orders yet</h3>
                <p class="muted">When you place an order it will show up here.</p>
                <a class="btn btn--primary mt" href="/products">Browse the catalog</a>
            </div>
        <?php else: ?>
            <div class="table-wrap mt">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Placed</th>
                            <th>Status</th>
                            <th class="num">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td style="font-weight:600">#<?= (int) $order['id'] ?></td>
                                <td class="muted"><?= e(date('M j, Y', strtotime($order['createdAt']))) ?></td>
                                <td><span class="badge badge--<?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
                                <td class="num mono"><?= e(formatMoney((int) $order['totalCents'], $order['currency'])) ?></td>
                                <td><a class="btn btn--sm" href="/account/orders/<?= (int) $order['id'] ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
