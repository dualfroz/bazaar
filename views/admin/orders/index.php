<div class="admin__head">
    <div>
        <h1 style="margin:0">Orders</h1>
        <p class="muted" style="margin:0"><?= count($orders) ?> orders total.</p>
    </div>
</div>

<?php if (empty($orders)): ?>
    <div class="empty">
        <h3>No orders yet</h3>
        <p class="muted">Orders placed by customers will appear here.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Order</th><th>Customer</th><th>Placed</th><th>Status</th><th class="num">Total</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td style="font-weight:600">#<?= (int) $order['id'] ?></td>
                        <td class="muted"><?= e($order['userEmail'] ?? 'Guest') ?></td>
                        <td class="muted"><?= e(date('M j, Y H:i', strtotime($order['createdAt']))) ?></td>
                        <td><span class="badge badge--<?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
                        <td class="num mono"><?= e(formatMoney((int) $order['totalCents'], $order['currency'])) ?></td>
                        <td class="num"><a class="btn btn--sm" href="/admin/orders/<?= (int) $order['id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
