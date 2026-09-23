<section class="hero">
    <div class="wrap">
        <div class="hero__inner">
            <span class="hero__eyebrow"><?= e($t('home.eyebrow')) ?></span>
            <h1><?= e($t('home.headline')) ?></h1>
            <p><?= e($t('home.lead')) ?></p>
            <div class="hero__actions">
                <a class="btn btn--primary" href="/products"><?= e($t('home.browse')) ?></a>
                <?php if (!empty($categories)): ?>
                    <a class="btn" href="/products?category=<?= e($categories[0]['slug']) ?>"><?= e($t('home.startWith', ['category' => $categories[0]['name']])) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="block">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2><?= e($t('home.freshTitle')) ?></h2>
                <p class="muted" style="margin:0"><?= e($t('home.freshLead')) ?></p>
            </div>
            <a class="btn btn--ghost" href="/products"><?= e($t('home.viewAll')) ?> &rarr;</a>
        </div>

        <?php if (empty($featured)): ?>
            <div class="empty">
                <p class="muted"><?= e($t('home.empty')) ?></p>
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
            <h2 style="margin:0"><?= e($t('home.departments')) ?></h2>
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
