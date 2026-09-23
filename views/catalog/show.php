<?php
$stock = (int) $product['stockQuantity'];
$initial = strtoupper(substr($product['name'], 0, 1));
$hasVariants = $variants !== [];
$variantStock = 0;
$variantMinPrice = null;
foreach ($variants as $variant) {
    $variantStock += (int) $variant['stockQuantity'];
    $price = (int) $variant['priceCents'];
    $variantMinPrice = $variantMinPrice === null ? $price : min($variantMinPrice, $price);
}
$displayStock = $hasVariants ? $variantStock : $stock;
$displayPriceCents = $hasVariants && $variantMinPrice !== null ? $variantMinPrice : (int) $product['priceCents'];
$average = (float) $reviewSummary['average'];
$reviewCount = (int) $reviewSummary['count'];
?>
<section class="block">
    <div class="wrap">
        <p class="small muted" style="margin-bottom:18px">
            <a href="/products"><?= e($t('catalog.title')) ?></a>
            <?php if (!empty($product['categorySlug'])): ?>
                / <a href="/products?category=<?= e($product['categorySlug']) ?>"><?= e($product['categoryName']) ?></a>
            <?php endif; ?>
            / <?= e($product['name']) ?>
        </p>

        <div class="split">
            <div class="detail-media">
                <?php if (!empty($product['imageUrl'])): ?>
                    <img src="<?= e($product['imageUrl']) ?>" alt="<?= e($product['name']) ?>">
                <?php else: ?>
                    <span class="product-card__placeholder" style="font-size:5rem"><?= e($initial) ?></span>
                <?php endif; ?>
            </div>

            <div>
                <?php if (!empty($product['categoryName'])): ?>
                    <span class="product-card__cat"><?= e($product['categoryName']) ?></span>
                <?php endif; ?>
                <h1 style="margin:8px 0 12px"><?= e($product['name']) ?></h1>

                <?php if ($reviewCount > 0): ?>
                    <div class="rating" style="margin-bottom:10px">
                        <span class="rating__stars"><?= str_repeat('*', (int) round($average)) ?><span class="rating__off"><?= str_repeat('*', 5 - (int) round($average)) ?></span></span>
                        <span class="rating__count"><?= e($t('product.ratingOutOf', ['average' => number_format($average, 1)])) ?> &middot; <?= $reviewCount ?> <?= e($t('product.reviews')) ?></span>
                    </div>
                <?php endif; ?>

                <div class="price" style="font-size:1.9rem">
                    <?php if ($hasVariants): ?><span class="price__from"><?= e($t('catalog.from')) ?></span> <?php endif; ?>
                    <?= e(formatMoney($displayPriceCents, $currency)) ?>
                </div>

                <p class="mt" style="color:var(--ink-soft)"><?= nl2br(e($product['description'])) ?></p>

                <div class="row mt-lg" style="align-items:center">
                    <?php if ($displayStock <= 0): ?>
                        <span class="badge badge--out"><?= e($t('product.soldOut')) ?></span>
                    <?php elseif ($displayStock <= 5): ?>
                        <span class="badge badge--low"><?= e($t('product.onlyLeft', ['count' => $displayStock])) ?></span>
                    <?php else: ?>
                        <span class="badge badge--stock"><?= e($t('product.inStock', ['count' => $displayStock])) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($displayStock > 0): ?>
                    <form method="post" action="/cart/add" class="stack mt-lg" style="gap:14px;max-width:420px">
                        <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="productId" value="<?= (int) $product['id'] ?>">

                        <?php if ($hasVariants): ?>
                            <fieldset class="variant-picker">
                                <legend class="small muted"><?= e($t('product.chooseVariant')) ?></legend>
                                <?php foreach ($variants as $index => $variant): ?>
                                    <?php $vStock = (int) $variant['stockQuantity']; ?>
                                    <label class="variant-opt <?= $vStock <= 0 ? 'variant-opt--out' : '' ?>">
                                        <input type="radio" name="variantId" value="<?= (int) $variant['id'] ?>" <?= $index === 0 ? 'checked' : '' ?> <?= $vStock <= 0 ? 'disabled' : '' ?>>
                                        <span class="variant-opt__label"><?= e($variant['label'] !== '' ? $variant['label'] : $variant['sku']) ?></span>
                                        <span class="variant-opt__price"><?= e(formatMoney((int) $variant['priceCents'], $currency)) ?></span>
                                        <span class="variant-opt__stock muted small"><?= e($vStock > 0 ? $t('product.variantLeft', ['count' => $vStock]) : $t('product.soldOut')) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php endif; ?>

                        <div class="row" style="align-items:center;gap:14px">
                            <div class="qty">
                                <input type="number" name="quantity" value="1" min="1" aria-label="<?= e($t('product.quantity')) ?>">
                            </div>
                            <button class="btn btn--primary" type="submit">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M3 4h2l2.4 12.3a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.8L21 8H6"/><circle cx="9" cy="20" r="1.3"/><circle cx="18" cy="20" r="1.3"/></svg>
                                <?= e($t('product.addToCart')) ?>
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="notice mt-lg"><?= e($t('product.soldOut')) ?></div>
                <?php endif; ?>

                <?php if (($currentUser ?? null) !== null): ?>
                    <form method="post" action="/wishlist/toggle" class="mt">
                        <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="productId" value="<?= (int) $product['id'] ?>">
                        <button type="submit" class="btn btn--ghost btn--sm<?= $inWishlist ? ' is-saved' : '' ?>">
                            <?= $inWishlist ? e($t('product.savedToWishlist')) : e($t('product.saveToWishlist')) ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="reviews mt-xl">
            <h2><?= e($t('product.reviews')) ?><?php if ($reviewCount > 0): ?> <span class="muted">(<?= $reviewCount ?>)</span><?php endif; ?></h2>

            <?php if ($reviewEligibility === \Bazaar\Services\ReviewService::eligible): ?>
                <form method="post" action="/reviews" class="review-form">
                    <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="productId" value="<?= (int) $product['id'] ?>">
                    <div class="row" style="gap:12px;align-items:flex-end">
                        <label class="stack">
                            <span class="small muted"><?= e($t('product.rating')) ?></span>
                            <select class="select" name="rating">
                                <?php for ($star = 5; $star >= 1; $star--): ?>
                                    <option value="<?= $star ?>"><?= $star ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <label class="stack" style="flex:1">
                            <span class="small muted"><?= e($t('product.reviewTitle')) ?></span>
                            <input class="input" type="text" name="title" maxlength="120">
                        </label>
                    </div>
                    <textarea class="input mt" name="body" rows="3" placeholder="<?= e($t('product.reviewPlaceholder')) ?>"></textarea>
                    <button class="btn btn--primary mt" type="submit"><?= e($t('product.writeReview')) ?></button>
                </form>
            <?php elseif ($reviewEligibility === \Bazaar\Services\ReviewService::alreadyReviewed): ?>
                <p class="muted"><?= e($t('product.reviewGateDone')) ?></p>
            <?php else: ?>
                <p class="muted"><?= e($t('product.reviewGateBuy')) ?></p>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
                <p class="muted"><?= e($t('product.noReviews')) ?></p>
            <?php else: ?>
                <div class="review-list mt">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review">
                            <div class="review__head">
                                <span class="rating__stars"><?= str_repeat('*', (int) $review['rating']) ?><span class="rating__off"><?= str_repeat('*', 5 - (int) $review['rating']) ?></span></span>
                                <strong><?= e($review['title'] !== '' ? $review['title'] : $t('product.reviewFallbackTitle')) ?></strong>
                            </div>
                            <p class="review__body"><?= nl2br(e($review['body'])) ?></p>
                            <p class="small muted"><?= e($review['authorName']) ?> &middot; <?= e(substr((string) $review['createdAt'], 0, 10)) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
