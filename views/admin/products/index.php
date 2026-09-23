<div class="admin__head">
    <div>
        <h1 style="margin:0"><?= e($t('admin.nav.products')) ?></h1>
        <p class="muted" style="margin:0"><?= e($t('admin.products.count', ['count' => count($products)])) ?></p>
    </div>
    <a class="btn btn--primary" href="/admin/products/create"><?= e($t('admin.products.new')) ?></a>
</div>

<?php if (empty($products)): ?>
    <div class="empty">
        <h3><?= e($t('admin.products.empty')) ?></h3>
        <p class="muted"><?= e($t('admin.products.emptyBody')) ?></p>
        <a class="btn btn--primary mt" href="/admin/products/create"><?= e($t('admin.products.new')) ?></a>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th colspan="2"><?= e($t('admin.products.product')) ?></th><th><?= e($t('admin.products.category')) ?></th><th class="num"><?= e($t('admin.products.price')) ?></th><th class="num"><?= e($t('admin.products.stock')) ?></th><th><?= e($t('orders.status')) ?></th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): $stock = (int) $product['stockQuantity']; ?>
                    <tr>
                        <td style="width:60px">
                            <?php if (!empty($product['imageUrl'])): ?>
                                <img class="cart-thumb" src="<?= e($product['imageUrl']) ?>" alt="">
                            <?php else: ?>
                                <span class="cart-thumb"><?= e(strtoupper(substr($product['name'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600"><?= e($product['name']) ?><div class="small muted mono">/<?= e($product['slug']) ?></div></td>
                        <td class="muted"><?= e($product['categoryName'] ?? $t('admin.products.uncategorized')) ?></td>
                        <td class="num mono"><?= e(formatMoney((int) $product['priceCents'], $currency)) ?></td>
                        <td class="num">
                            <span class="badge badge--<?= $stock <= 0 ? 'out' : ($stock <= 5 ? 'low' : 'stock') ?>"><?= $stock ?></span>
                        </td>
                        <td>
                            <?php if ((int) $product['isActive'] === 1): ?>
                                <span class="badge badge--fulfilled"><?= e($t('admin.common.active')) ?></span>
                            <?php else: ?>
                                <span class="badge badge--cancelled"><?= e($t('admin.common.hidden')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row" style="gap:6px;justify-content:flex-end">
                                <a class="btn btn--sm" href="/admin/products/<?= (int) $product['id'] ?>/variants"><?= e($t('admin.variants.title')) ?></a>
                                <a class="btn btn--sm" href="/admin/products/<?= (int) $product['id'] ?>/edit"><?= e($t('admin.common.edit')) ?></a>
                                <form method="post" action="/admin/products/<?= (int) $product['id'] ?>/delete" onsubmit="return confirm(<?= e(json_encode($t('admin.products.confirmDelete'))) ?>)">
                                    <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                                    <button class="btn btn--danger btn--sm" type="submit"><?= e($t('admin.common.delete')) ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
