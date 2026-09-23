<div class="admin__head">
    <div>
        <h1 style="margin:0"><?= e($t('admin.nav.orders')) ?></h1>
        <p class="muted" style="margin:0"><?= e($t('admin.orders.count', ['count' => count($orders)])) ?></p>
    </div>
</div>

<?php if (empty($orders)): ?>
    <div class="empty">
        <h3><?= e($t('admin.orders.empty')) ?></h3>
        <p class="muted"><?= e($t('admin.orders.emptyBody')) ?></p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?= e($t('orders.order')) ?></th><th><?= e($t('admin.orders.customer')) ?></th><th><?= e($t('orders.placed')) ?></th><th><?= e($t('orders.status')) ?></th><th class="num"><?= e($t('orders.total')) ?></th><th></th></tr></thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td style="font-weight:600">#<?= (int) $order['id'] ?></td>
                        <td class="muted"><?= e($order['userEmail'] ?? $t('admin.orders.guest')) ?></td>
                        <td class="muted"><?= e(date($t('format.dateTime'), strtotime($order['createdAt']))) ?></td>
                        <td><span class="badge badge--<?= e($order['status']) ?>"><?= e($t('status.' . $order['status'])) ?></span></td>
                        <td class="num mono"><?= e(formatMoney((int) $order['totalCents'], $order['currency'])) ?></td>
                        <td class="num"><a class="btn btn--sm" href="/admin/orders/<?= (int) $order['id'] ?>"><?= e($t('admin.orders.open')) ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
