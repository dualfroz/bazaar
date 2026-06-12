<section class="hero">
    <div class="wrap">
        <div class="hero__inner">
            <span class="hero__eyebrow">Independent goods, fairly priced</span>
            <h1>A small shop with a big appetite for good things.</h1>
            <p>Bazaar is a demo storefront running on a hand-rolled PHP microframework - real catalog, session cart, Stripe checkout and an admin back office. Browse the shelves.</p>
            <div class="hero__actions">
                <a class="btn btn--primary" href="/products">Browse the catalog</a>
                <?php if (!empty($categories)): ?>
                    <a class="btn" href="/products?category=<?= e($categories[0]['slug']) ?>">Start with <?= e($categories[0]['name']) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="block">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2>Fresh on the shelves</h2>
                <p class="muted" style="margin:0">The latest additions to the catalog.</p>
            </div>
            <a class="btn btn--ghost" href="/products">View all &rarr;</a>
        </div>

        <?php if (empty($featured)): ?>
            <div class="empty">
                <p class="muted">No products yet. Seed the database or add one from the admin panel.</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($featured as $product): ?>
                    <?= $view->renderPartial('partials.productCard', ['product' => $product, 'currency' => $currency]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($categories)): ?>
<section class="block block--tight" style="background:var(--surface);border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
    <div class="wrap">
        <div class="section-head">
            <h2 style="margin:0">Shop by department</h2>
        </div>
        <div class="chips">
            <?php foreach ($categories as $category): ?>
                <a class="chip" href="/products?category=<?= e($category['slug']) ?>">
                    <?= e($category['name']) ?>
                    <span class="muted">&middot; <?= (int) $category['productCount'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
