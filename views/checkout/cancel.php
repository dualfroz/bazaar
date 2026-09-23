<section class="block">
    <div class="wrap" style="max-width:560px;text-align:center">
        <div class="empty">
            <h1><?= e($t('checkout.cancelledTitle')) ?></h1>
            <p class="muted"><?= e($t('checkout.cancelledBody')) ?></p>
            <div class="row gap-lg" style="justify-content:center">
                <a class="btn btn--primary mt" href="/checkout"><?= e($t('checkout.returnToCheckout')) ?></a>
                <a class="btn mt" href="/cart"><?= e($t('checkout.viewCart')) ?></a>
            </div>
        </div>
    </div>
</section>
