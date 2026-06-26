<div class="admin__head">
    <div>
        <h1 style="margin:0">Coupons</h1>
        <p class="muted" style="margin:0">Percentage or fixed discounts with limits and windows.</p>
    </div>
    <a class="btn btn--primary" href="/admin/coupons/create">New coupon</a>
</div>

<?php if (empty($coupons)): ?>
    <div class="empty"><p class="muted" style="margin:0">No coupons yet.</p></div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Code</th><th>Discount</th><th class="num">Min cart</th><th>Redemptions</th><th>Window</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td class="mono" style="font-weight:600"><?= e($coupon['code']) ?></td>
                        <td>
                            <?php if ($coupon['type'] === 'percent'): ?>
                                <?= (int) $coupon['value'] ?>%
                            <?php else: ?>
                                <?= e(formatMoney((int) $coupon['value'], $currency)) ?>
                            <?php endif; ?>
                        </td>
                        <td class="num mono"><?= e(formatMoney((int) $coupon['minSubtotalCents'], $currency)) ?></td>
                        <td>
                            <?= (int) $coupon['redeemedCount'] ?><?= $coupon['maxRedemptions'] !== null ? ' / ' . (int) $coupon['maxRedemptions'] : '' ?>
                        </td>
                        <td class="small muted">
                            <?= $coupon['startsAt'] ? e(substr($coupon['startsAt'], 0, 10)) : '-' ?>
                            &rarr;
                            <?= $coupon['expiresAt'] ? e(substr($coupon['expiresAt'], 0, 10)) : '-' ?>
                        </td>
                        <td><span class="badge badge--<?= (int) $coupon['isActive'] === 1 ? 'stock' : 'out' ?>"><?= (int) $coupon['isActive'] === 1 ? 'active' : 'off' ?></span></td>
                        <td>
                            <form method="post" action="/admin/coupons/<?= (int) $coupon['id'] ?>/delete" onsubmit="return confirm('Delete this coupon?')">
                                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                                <button class="btn btn--danger btn--sm" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
