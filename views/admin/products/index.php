<div class="admin__head">
    <div>
        <h1 style="margin:0">Products</h1>
        <p class="muted" style="margin:0"><?= count($products) ?> products in the catalog.</p>
    </div>
    <a class="btn btn--primary" href="/admin/products/create">New product</a>
</div>

<?php if (empty($products)): ?>
    <div class="empty">
        <h3>No products yet</h3>
        <p class="muted">Create your first product to start selling.</p>
        <a class="btn btn--primary mt" href="/admin/products/create">New product</a>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th colspan="2">Product</th><th>Category</th><th class="num">Price</th><th class="num">Stock</th><th>Status</th><th></th></tr>
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
                        <td class="muted"><?= e($product['categoryName'] ?? 'Uncategorized') ?></td>
                        <td class="num mono"><?= e(formatMoney((int) $product['priceCents'], $currency)) ?></td>
                        <td class="num">
                            <span class="badge badge--<?= $stock <= 0 ? 'out' : ($stock <= 5 ? 'low' : 'stock') ?>"><?= $stock ?></span>
                        </td>
                        <td>
                            <?php if ((int) $product['isActive'] === 1): ?>
                                <span class="badge badge--fulfilled">Active</span>
                            <?php else: ?>
                                <span class="badge badge--cancelled">Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row" style="gap:6px;justify-content:flex-end">
                                <a class="btn btn--sm" href="/admin/products/<?= (int) $product['id'] ?>/variants">Variants</a>
                                <a class="btn btn--sm" href="/admin/products/<?= (int) $product['id'] ?>/edit">Edit</a>
                                <form method="post" action="/admin/products/<?= (int) $product['id'] ?>/delete" onsubmit="return confirm('Delete this product? This cannot be undone.')">
                                    <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                                    <button class="btn btn--danger btn--sm" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
