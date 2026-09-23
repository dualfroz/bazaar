<?php
$stock = (int) ($product['availableStock'] ?? $product['stockQuantity']);
$initial = strtoupper(substr($product['name'], 0, 1));
$ratingAverage = (float) ($product['ratingAverage'] ?? 0);
$ratingCount = (int) ($product['ratingCount'] ?? 0);
$variantCount = (int) ($product['variantCount'] ?? 0);
$priceFromCents = (int) ($product['priceFromCents'] ?? $product['priceCents']);
$wishlisted = $wishlisted ?? [];
$isSaved = in_array((int) $product['id'], array_map('intval', $wishlisted), true);
?>
<article class="product-card">
    <a href="/products/<?= e($product['slug']) ?>" aria-label="<?= e($product['name']) ?>">
        <div class="product-card__media">
            <?php if (!empty($product['imageUrl'])): ?>
                <img src="<?= e($product['imageUrl']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
            <?php else: ?>
                <span class="product-card__placeholder"><?= e($initial) ?></span>
            <?php endif; ?>
        </div>
    </a>
    <div class="product-card__body">
        <?php if (!empty($product['categoryName'])): ?>
            <span class="product-card__cat"><?= e($product['categoryName']) ?></span>
        <?php endif; ?>
        <a href="/products/<?= e($product['slug']) ?>"><span class="product-card__name"><?= e($product['name']) ?></span></a>

        <?php if ($ratingCount > 0): ?>
            <div class="rating rating--sm" title="<?= number_format($ratingAverage, 1) ?> / 5">
                <span class="rating__stars"><?= str_repeat('*', (int) round($ratingAverage)) ?><span class="rating__off"><?= str_repeat('*', 5 - (int) round($ratingAverage)) ?></span></span>
                <span class="rating__count"><?= number_format($ratingAverage, 1) ?> (<?= $ratingCount ?>)</span>
            </div>
        <?php endif; ?>

        <div class="product-card__foot">
            <span class="price">
                <?php if ($variantCount > 0 && $priceFromCents !== (int) $product['priceCents']): ?><span class="price__from"><?= e($t('catalog.from')) ?></span> <?php endif; ?>
                <?= e(formatMoney($priceFromCents, $currency)) ?>
            </span>
            <?php if ($stock <= 0): ?>
                <span class="badge badge--out"><?= e($t('product.soldOut')) ?></span>
            <?php elseif ($stock <= 5): ?>
                <span class="badge badge--low"><?= e($t('product.fewLeft', ['count' => $stock])) ?></span>
            <?php else: ?>
                <span class="badge badge--stock"><?= e($t('product.available')) ?></span>
            <?php endif; ?>
        </div>

        <?php if (($currentUser ?? null) !== null): ?>
            <form method="post" action="/wishlist/toggle" class="product-card__wish">
                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="productId" value="<?= (int) $product['id'] ?>">
                <button type="submit" class="btn btn--ghost btn--sm<?= $isSaved ? ' is-saved' : '' ?>">
                    <?= e($isSaved ? $t('product.savedToWishlist') : $t('product.saveToWishlist')) ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</article>
