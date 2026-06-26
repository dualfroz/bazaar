<section class="block">
    <div class="wrap">
        <h1>Checkout</h1>

        <?php if (empty($lines)): ?>
            <div class="empty">
                <h3>There is nothing to check out</h3>
                <p class="muted">Your cart is empty.</p>
                <a class="btn btn--primary mt" href="/products">Browse the catalog</a>
            </div>
        <?php else: ?>
            <div class="split">
                <div>
                    <div class="panel panel--pad">
                        <h3>Review your order</h3>
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

                    <?php if (!$stripeConfigured): ?>
                        <div class="notice mt-lg">
                            <svg class="icon" viewBox="0 0 24 24" style="flex:none"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                            <span>Stripe test keys are not set yet, so payment is disabled. Add <strong>STRIPE_SECRET_KEY</strong> and <strong>STRIPE_PUBLISHABLE_KEY</strong> to your <code>.env</code> to enable Stripe Checkout.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel sticky panel--pad">
                    <h3>Summary</h3>
                    <div class="summary-row"><span>Subtotal</span><span class="mono"><?= e(formatMoney($subtotalCents, $currency)) ?></span></div>
                    <?php if ($discountCents > 0): ?>
                        <div class="summary-row summary-row--discount"><span>Discount (<?= e($couponCode) ?>)</span><span class="mono">-<?= e(formatMoney($discountCents, $currency)) ?></span></div>
                    <?php endif; ?>
                    <div class="summary-row summary-row--total"><span>Total due</span><span class="mono"><?= e(formatMoney($totalCents, $currency)) ?></span></div>

                    <form method="post" action="/checkout" class="mt-lg">
                        <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                        <button class="btn btn--primary btn--block" type="submit" <?= $stripeConfigured ? '' : 'disabled' ?>>
                            Pay with Stripe
                        </button>
                    </form>
                    <p class="small muted mt" style="text-align:center;margin-top:12px">
                        You will be redirected to Stripe's secure test checkout.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
