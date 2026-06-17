<section class="block">
    <div class="wrap">
        <h1><?= e($t('wishlist.title')) ?></h1>

        <?php if (empty($products)): ?>
            <div class="empty">
                <svg class="icon empty__icon" viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                <h3><?= e($t('wishlist.empty')) ?></h3>
                <a class="btn btn--primary mt" href="/products"><?= e($t('nav.shop')) ?></a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?= $view->renderPartial('partials.productCard', ['product' => $product, 'currency' => $currency, 'wishlisted' => array_map('intval', array_column($products, 'id'))]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
