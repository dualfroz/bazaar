<?php
$isFree = $totalCents === 0;
$canPay = $isFree || $stripeConfigured;
?>
<section class="block">
    <div class="wrap">
        <h1><?= e($t('checkout.title')) ?></h1>

        <?php if (empty($lines)): ?>
            <div class="empty">
                <h3><?= e($t('checkout.emptyTitle')) ?></h3>
                <p class="muted"><?= e($t('cart.empty')) ?></p>
                <a class="btn btn--primary mt" href="/products"><?= e($t('home.browse')) ?></a>
            </div>
        <?php else: ?>
            <div class="split">
                <div>
                    <div class="panel panel--pad">
                        <h3><?= e($t('checkout.review')) ?></h3>
                        <div class="table-wrap" style="border:none;margin-top:8px">
                            <table class="table">
                                <tbody>
                                    <?php foreach ($lines as $line): ?>
                                        <tr>
                                            <td style="font-weight:600">
                                                <?= e($line['name']) ?> <span class="muted">x <?= (int) $line['quantity'] ?></span>
                                                <?php if ($line['variantLabel'] !== ''): ?><span class="muted small">(<?= e($line['variantLabel']) ?>)</span><?php endif; ?>
                                            </td>
                                            <td class="num mono"><?= e(formatMoney((int) $line['lineTotalCents'], $currency)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if (!$canPay): ?>
                        <div class="notice mt-lg">
                            <svg class="icon" viewBox="0 0 24 24" style="flex:none"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                            <span><?= strtr(e($t('checkout.stripeNotice')), [
                                ':secret' => '<strong>STRIPE_SECRET_KEY</strong>',
                                ':publishable' => '<strong>STRIPE_PUBLISHABLE_KEY</strong>',
                                ':env' => '<code>.env</code>',
                            ]) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel sticky panel--pad">
                    <h3><?= e($t('checkout.summary')) ?></h3>
                    <div class="summary-row"><span><?= e($t('cart.subtotal')) ?></span><span class="mono"><?= e(formatMoney($subtotalCents, $currency)) ?></span></div>
                    <?php if ($discountCents > 0): ?>
                        <div class="summary-row summary-row--discount"><span><?= e($t('cart.discount')) ?> (<?= e($couponCode) ?>)</span><span class="mono">-<?= e(formatMoney($discountCents, $currency)) ?></span></div>
                    <?php endif; ?>
                    <div class="summary-row summary-row--total"><span><?= e($t('checkout.totalDue')) ?></span><span class="mono"><?= e(formatMoney($totalCents, $currency)) ?></span></div>

                    <form method="post" action="/checkout" class="mt-lg">
                        <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                        <button class="btn btn--primary btn--block" type="submit" <?= $canPay ? '' : 'disabled' ?>>
                            <?= e($isFree ? $t('checkout.placeFreeOrder') : $t('checkout.payWithStripe')) ?>
                        </button>
                    </form>
                    <p class="small muted mt" style="text-align:center;margin-top:12px">
                        <?= e($isFree ? $t('checkout.freeNote') : $t('checkout.redirectNote')) ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
