<div class="admin__head">
    <div>
        <h1 style="margin:0"><?= e($t('admin.variants.title')) ?></h1>
        <p class="muted" style="margin:0"><?= e($product['name']) ?></p>
    </div>
    <a class="btn btn--ghost" href="/admin/products/<?= (int) $product['id'] ?>/edit"><?= e($t('admin.variants.editProduct')) ?></a>
</div>

<?php if (empty($variants)): ?>
    <div class="empty"><p class="muted" style="margin:0"><?= e($t('admin.variants.empty')) ?></p></div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?= e($t('admin.variants.sku')) ?></th><th><?= e($t('admin.variants.options')) ?></th><th class="num"><?= e($t('admin.products.price')) ?></th><th class="num"><?= e($t('admin.products.stock')) ?></th><th><?= e($t('orders.status')) ?></th><th></th></tr></thead>
            <tbody>
                <?php foreach ($variants as $variant): ?>
                    <tr>
                        <td class="mono"><?= e($variant['sku']) ?></td>
                        <td><?= e($variant['label'] !== '' ? $variant['label'] : '-') ?></td>
                        <td class="num mono"><?= e(formatMoney((int) $variant['priceCents'], $currency)) ?></td>
                        <td class="num"><span class="badge badge--<?= (int) $variant['stockQuantity'] <= 0 ? 'out' : ((int) $variant['stockQuantity'] <= 5 ? 'low' : 'stock') ?>"><?= (int) $variant['stockQuantity'] ?></span></td>
                        <td><span class="badge badge--<?= (int) $variant['isActive'] === 1 ? 'stock' : 'out' ?>"><?= e((int) $variant['isActive'] === 1 ? $t('admin.common.active') : $t('admin.common.off')) ?></span></td>
                        <td>
                            <form method="post" action="/admin/variants/<?= (int) $variant['id'] ?>/delete" onsubmit="return confirm(<?= e(json_encode($t('admin.variants.confirmDelete'))) ?>)">
                                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                                <button class="btn btn--danger btn--sm" type="submit"><?= e($t('admin.common.delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="panel panel--pad mt-lg" style="max-width:720px">
    <h3 style="margin-top:0"><?= e($t('admin.variants.add')) ?></h3>
    <?php if (empty($attributes)): ?>
        <p class="muted"><?= e($t('admin.variants.needAttributes')) ?> <a href="/admin/attributes"><?= e($t('admin.nav.attributes')) ?></a></p>
    <?php else: ?>
        <form method="post" action="/admin/products/<?= (int) $product['id'] ?>/variants">
            <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
            <div class="row" style="gap:16px">
                <label class="field" style="flex:1">
                    <span class="field__label"><?= e($t('admin.variants.skuAuto')) ?></span>
                    <input class="input" type="text" name="sku" placeholder="TEE-RED-M">
                </label>
                <label class="field" style="flex:1">
                    <span class="field__label"><?= e($t('admin.products.price')) ?></span>
                    <input class="input" type="number" name="price" step="0.01" min="0" value="<?= number_format((int) $product['priceCents'] / 100, 2, '.', '') ?>" required>
                </label>
                <label class="field" style="flex:1">
                    <span class="field__label"><?= e($t('admin.products.stock')) ?></span>
                    <input class="input" type="number" name="stockQuantity" min="0" value="0" required>
                </label>
            </div>
            <div class="row" style="gap:16px">
                <?php foreach ($attributes as $attribute): ?>
                    <?php if ($attribute['values'] === []) { continue; } ?>
                    <label class="field" style="flex:1">
                        <span class="field__label"><?= e($attribute['name']) ?></span>
                        <select class="select" name="option[<?= (int) $attribute['id'] ?>]">
                            <option value="">-</option>
                            <?php foreach ($attribute['values'] as $value): ?>
                                <option value="<?= (int) $value['id'] ?>"><?= e($value['value']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="mt-lg">
                <button class="btn btn--primary" type="submit"><?= e($t('admin.variants.addButton')) ?></button>
            </div>
        </form>
    <?php endif; ?>
</div>
