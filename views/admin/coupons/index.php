<div class="admin__head">
    <div>
        <h1 style="margin:0"><?= e($t('admin.nav.coupons')) ?></h1>
        <p class="muted" style="margin:0"><?= e($t('admin.coupons.lead')) ?></p>
    </div>
    <a class="btn btn--primary" href="/admin/coupons/create"><?= e($t('admin.coupons.new')) ?></a>
</div>

<?php if (empty($coupons)): ?>
    <div class="empty"><p class="muted" style="margin:0"><?= e($t('admin.coupons.empty')) ?></p></div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th><?= e($t('admin.coupons.code')) ?></th><th><?= e($t('cart.discount')) ?></th><th class="num"><?= e($t('admin.coupons.minCart')) ?></th><th><?= e($t('admin.coupons.redemptions')) ?></th><th><?= e($t('admin.coupons.window')) ?></th><th><?= e($t('orders.status')) ?></th><th></th></tr>
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
                        <td><span class="badge badge--<?= (int) $coupon['isActive'] === 1 ? 'stock' : 'out' ?>"><?= e((int) $coupon['isActive'] === 1 ? $t('admin.common.active') : $t('admin.common.off')) ?></span></td>
                        <td>
                            <form method="post" action="/admin/coupons/<?= (int) $coupon['id'] ?>/delete" onsubmit="return confirm(<?= e(json_encode($t('admin.coupons.confirmDelete'))) ?>)">
                                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                                <button class="btn btn--danger btn--sm" type="submit"><?= e($t('admin.common.delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
