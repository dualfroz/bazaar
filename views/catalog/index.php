<?php
$products = $result['products'];
$facets = $result['facets'];
$total = $result['total'];
$page = $result['page'];
$pages = $result['pages'];
$resultLabel = $total === 1
    ? $t('catalog.resultsOne', ['count' => $total])
    : $t('catalog.resultsMany', ['count' => $total]);
$pageQuery = $_GET;
unset($pageQuery['page']);
$baseQuery = http_build_query($pageQuery);
?>
<section class="block">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h1 style="margin-bottom:4px"><?= e($t('catalog.title')) ?></h1>
                <p class="muted" style="margin:0"><?= e($resultLabel) ?><?= $term !== '' ? ' - "' . e($term) . '"' : '' ?></p>
            </div>
        </div>

        <div class="catalog-grid">
            <aside class="facets">
                <form method="get" action="/products" class="facets__form">
                    <input class="input" type="search" name="q" value="<?= e($term) ?>" placeholder="<?= e($t('catalog.searchPlaceholder')) ?>">

                    <div class="facet">
                        <h4 class="facet__title"><?= e($t('catalog.category')) ?></h4>
                        <label class="facet__opt">
                            <input type="radio" name="category" value="" <?= $activeCategory === '' ? 'checked' : '' ?>>
                            <span><?= e($t('catalog.all')) ?></span>
                        </label>
                        <?php foreach ($facets['categories'] as $category): ?>
                            <label class="facet__opt">
                                <input type="radio" name="category" value="<?= e($category['slug']) ?>" <?= $activeCategory === $category['slug'] ? 'checked' : '' ?>>
                                <span><?= e($category['name']) ?></span>
                                <span class="facet__count"><?= (int) $category['count'] ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="facet">
                        <h4 class="facet__title"><?= e($t('catalog.price')) ?></h4>
                        <div class="facet__range">
                            <input class="input input--sm" type="number" name="minPrice" min="0" step="0.01" value="<?= e((string) $minPrice) ?>" placeholder="<?= number_format($facets['priceRange']['minCents'] / 100, 0) ?>">
                            <span class="muted">-</span>
                            <input class="input input--sm" type="number" name="maxPrice" min="0" step="0.01" value="<?= e((string) $maxPrice) ?>" placeholder="<?= number_format($facets['priceRange']['maxCents'] / 100, 0) ?>">
                        </div>
                    </div>

                    <?php foreach ($facets['attributes'] as $attribute): ?>
                        <?php if ($attribute['values'] === []) { continue; } ?>
                        <div class="facet">
                            <h4 class="facet__title"><?= e($attribute['name']) ?></h4>
                            <?php foreach ($attribute['values'] as $value): ?>
                                <label class="facet__opt">
                                    <input type="checkbox" name="attr[<?= e($attribute['slug']) ?>][]" value="<?= e($value['slug']) ?>" <?= $value['selected'] ? 'checked' : '' ?>>
                                    <span><?= e($value['name']) ?></span>
                                    <span class="facet__count"><?= (int) $value['count'] ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <input type="hidden" name="sort" value="<?= e($sort) ?>">
                    <div class="facets__actions">
                        <button class="btn btn--primary" type="submit"><?= e($t('catalog.apply')) ?></button>
                        <a class="btn btn--ghost" href="/products"><?= e($t('catalog.reset')) ?></a>
                    </div>
                </form>
            </aside>

            <div>
                <div class="catalog-toolbar">
                    <span class="muted"><?= e($resultLabel) ?></span>
                    <form method="get" action="/products" class="catalog-toolbar__sort">
                        <?php foreach (['q' => $term, 'category' => $activeCategory, 'minPrice' => $minPrice, 'maxPrice' => $maxPrice] as $name => $carry): ?>
                            <?php if ($carry !== '' && $carry !== null): ?><input type="hidden" name="<?= e($name) ?>" value="<?= e((string) $carry) ?>"><?php endif; ?>
                        <?php endforeach; ?>
                        <?php foreach ($result['selected'] as $attrSlug => $valueSlugs): ?>
                            <?php foreach ($valueSlugs as $valueSlug): ?>
                                <input type="hidden" name="attr[<?= e($attrSlug) ?>][]" value="<?= e($valueSlug) ?>">
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        <label class="muted small"><?= e($t('catalog.sort')) ?></label>
                        <select class="select" name="sort" onchange="this.form.submit()">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= e($t('catalog.sortNewest')) ?></option>
                            <option value="priceAsc" <?= $sort === 'priceAsc' ? 'selected' : '' ?>><?= e($t('catalog.sortPriceAsc')) ?></option>
                            <option value="priceDesc" <?= $sort === 'priceDesc' ? 'selected' : '' ?>><?= e($t('catalog.sortPriceDesc')) ?></option>
                            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>><?= e($t('catalog.sortName')) ?></option>
                            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>><?= e($t('catalog.sortRating')) ?></option>
                        </select>
                    </form>
                </div>

                <?php if (empty($products)): ?>
                    <div class="empty">
                        <svg class="icon empty__icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                        <h3><?= e($t('catalog.emptyTitle')) ?></h3>
                        <p class="muted"><?= e($t('catalog.emptyBody')) ?></p>
                        <a class="btn mt" href="/products"><?= e($t('catalog.reset')) ?></a>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                            <?= $view->renderPartial('partials.productCard', ['product' => $product, 'currency' => $currency, 'wishlisted' => $wishlisted]) ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($pages > 1): ?>
                        <nav class="pagination">
                            <?php for ($number = 1; $number <= $pages; $number++): ?>
                                <a class="pagination__item <?= $number === $page ? 'pagination__item--active' : '' ?>"
                                   href="/products?<?= e($baseQuery !== '' ? $baseQuery . '&' : '') ?>page=<?= $number ?>"><?= $number ?></a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
