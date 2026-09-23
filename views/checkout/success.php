<section class="block">
    <div class="wrap" style="max-width:560px;text-align:center">
        <?php if ($order !== null && $paymentStatus === 'paid'): ?>
            <div class="empty" style="border-style:solid">
                <svg class="icon empty__icon" viewBox="0 0 24 24" style="color:var(--positive)"><path d="M20 6 9 17l-5-5"/></svg>
                <h1><?= e($t('checkout.paidTitle')) ?></h1>
                <p class="muted"><?= e($t('checkout.paidBody', ['id' => (int) $order['id']])) ?></p>
                <div class="row gap-lg" style="justify-content:center" >
                    <a class="btn btn--primary mt" href="/account/orders/<?= (int) $order['id'] ?>"><?= e($t('checkout.viewOrder')) ?></a>
                    <a class="btn mt" href="/products"><?= e($t('checkout.keepShopping')) ?></a>
                </div>
            </div>
        <?php else: ?>
            <div class="empty">
                <h1><?= e($t('checkout.pendingTitle')) ?></h1>
                <p class="muted"><?= e($t('checkout.pendingBody')) ?></p>
                <a class="btn mt" href="/account/orders"><?= e($t('checkout.goToOrders')) ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>
