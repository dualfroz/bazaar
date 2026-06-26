<section class="block">
    <div class="wrap">
        <h1><?= e($t('cart.title')) ?></h1>

        <?php if (empty($lines)): ?>
            <div class="empty">
                <svg class="icon empty__icon" viewBox="0 0 24 24"><path d="M3 4h2l2.4 12.3a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.8L21 8H6"/><circle cx="9" cy="20" r="1.3"/><circle cx="18" cy="20" r="1.3"/></svg>
                <h3><?= e($t('cart.empty')) ?></h3>
                <p class="muted">Browse the catalog and add something you like.</p>
                <a class="btn btn--primary mt" href="/products"><?= e($t('nav.shop')) ?></a>
            </div>
        <?php else: ?>
            <div class="split" style="grid-template-columns:1.6fr 1fr">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th colspan="2">Product</th>
                                <th class="num">Price</th>
                                <th>Quantity</th>
                                <th class="num">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lines as $line): $item = $line['product']; ?>
                                <tr>
                                    <td style="width:72px">
                                        <?php if (!empty($item['imageUrl'])): ?>
                                            <img class="cart-thumb" src="<?= e($item['imageUrl']) ?>" alt="<?= e($line['name']) ?>">
                                        <?php else: ?>
                                            <span class="cart-thumb"><?= e(strtoupper(substr($line['name'], 0, 1))) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/products/<?= e($item['slug']) ?>" style="font-weight:600;color:var(--ink)"><?= e($line['name']) ?></a>
                                        <?php if ($line['variantLabel'] !== ''): ?>
                                            <div class="small muted"><?= e($line['variantLabel']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="num mono"><?= e(formatMoney((int) $line['unitPriceCents'], $currency)) ?></td>
                                    <td>
                                        <form method="post" action="/cart/update" class="row" style="gap:8px">
                                            <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="productId" value="<?= (int) $line['productId'] ?>">
                                            <?php if ($line['variantId'] !== null): ?><input type="hidden" name="variantId" value="<?= (int) $line['variantId'] ?>"><?php endif; ?>
                                            <div class="qty">
                                                <input type="number" name="quantity" value="<?= (int) $line['quantity'] ?>" min="1" max="<?= (int) $line['availableStock'] ?>">
                                            </div>
                                            <button class="btn btn--sm" type="submit">Update</button>
                                        </form>
                                    </td>
                                    <td class="num mono" style="font-weight:600"><?= e(formatMoney((int) $line['lineTotalCents'], $currency)) ?></td>
                                    <td>
                                        <form method="post" action="/cart/remove">
                                            <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="productId" value="<?= (int) $line['productId'] ?>">
                                            <?php if ($line['variantId'] !== null): ?><input type="hidden" name="variantId" value="<?= (int) $line['variantId'] ?>"><?php endif; ?>
                                            <button class="btn btn--danger btn--sm" type="submit"><?= e($t('cart.remove')) ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="panel sticky panel--pad">
                    <h3>Order summary</h3>

                    <form method="post" action="/cart/coupon" class="coupon-form">
                        <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                        <input class="input input--sm" type="text" name="code" value="<?= e($couponCode) ?>" placeholder="<?= e($t('cart.coupon')) ?>">
                        <button class="btn btn--sm" type="submit"><?= e($t('cart.apply')) ?></button>
                    </form>
                    <?php if ($couponCode !== ''): ?>
                        <form method="post" action="/cart/coupon/remove" class="mt-sm">
                            <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                            <button class="btn btn--ghost btn--sm" type="submit">Remove coupon <?= e($couponCode) ?></button>
                        </form>
                    <?php endif; ?>

                    <div class="summary-row mt-lg"><span><?= e($t('cart.subtotal')) ?></span><span class="mono"><?= e(formatMoney($subtotalCents, $currency)) ?></span></div>
                    <?php if ($discountCents > 0): ?>
                        <div class="summary-row summary-row--discount"><span><?= e($t('cart.discount')) ?> (<?= e($couponCode) ?>)</span><span class="mono">-<?= e(formatMoney($discountCents, $currency)) ?></span></div>
                    <?php endif; ?>
                    <div class="summary-row"><span>Shipping</span><span>Calculated at checkout</span></div>
                    <div class="summary-row summary-row--total"><span><?= e($t('cart.total')) ?></span><span class="mono"><?= e(formatMoney($totalCents, $currency)) ?></span></div>
                    <a class="btn btn--primary btn--block mt-lg" href="/checkout"><?= e($t('cart.checkout')) ?></a>
                    <a class="btn btn--ghost btn--block mt" href="/products">Continue shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
