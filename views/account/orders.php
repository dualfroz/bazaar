<section class="block">
    <div class="wrap">
        <h1><?= e($t('account.ordersTitle')) ?></h1>

        <?php if (empty($orders)): ?>
            <div class="empty">
                <h3><?= e($t('account.noOrdersTitle')) ?></h3>
                <p class="muted"><?= e($t('account.noOrdersBody')) ?></p>
                <a class="btn btn--primary mt" href="/products"><?= e($t('home.browse')) ?></a>
            </div>
        <?php else: ?>
            <div class="table-wrap mt">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= e($t('orders.order')) ?></th>
                            <th><?= e($t('orders.placed')) ?></th>
                            <th><?= e($t('orders.status')) ?></th>
                            <th class="num"><?= e($t('orders.total')) ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td style="font-weight:600">#<?= (int) $order['id'] ?></td>
                                <td class="muted"><?= e(date($t('format.date'), strtotime($order['createdAt']))) ?></td>
                                <td><span class="badge badge--<?= e($order['status']) ?>"><?= e($t('status.' . $order['status'])) ?></span></td>
                                <td class="num mono"><?= e(formatMoney((int) $order['totalCents'], $order['currency'])) ?></td>
                                <td><a class="btn btn--sm" href="/account/orders/<?= (int) $order['id'] ?>"><?= e($t('orders.view')) ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
