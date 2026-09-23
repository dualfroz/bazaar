<?php
$editing = $product !== null && !empty($product['id']);
$action = $editing ? '/admin/products/' . (int) $product['id'] : '/admin/products';
$priceValue = $product !== null ? number_format((int) ($product['priceCents'] ?? 0) / 100, 2, '.', '') : '';
$field = static fn (string $key, $fallback = '') => $product[$key] ?? $fallback;
?>
<div class="admin__head">
    <div>
        <p class="small muted"><a href="/admin/products"><?= e($t('admin.nav.products')) ?></a> / <?= e($editing ? $t('admin.common.edit') : $t('admin.common.new')) ?></p>
        <h1 style="margin:0"><?= e($editing ? $product['name'] : $t('admin.products.new')) ?></h1>
    </div>
</div>

<form method="post" action="<?= e($action) ?>" style="max-width:720px">
    <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">

    <div class="panel panel--pad">
        <div class="field <?= isset($errors['name']) ? 'field--error' : '' ?>">
            <label for="name"><?= e($t('admin.common.name')) ?></label>
            <input class="input" type="text" id="name" name="name" value="<?= e((string) $field('name')) ?>" required>
            <?php if (isset($errors['name'])): ?><span class="field__error"><?= e($errors['name']) ?></span><?php endif; ?>
        </div>

        <div class="row gap-lg" style="align-items:flex-start">
            <div class="field" style="flex:1">
                <label for="slug"><?= e($t('admin.common.slug')) ?></label>
                <input class="input" type="text" id="slug" name="slug" value="<?= e((string) $field('slug')) ?>" placeholder="<?= e($t('admin.common.slugPlaceholder')) ?>">
            </div>
            <div class="field <?= isset($errors['categoryId']) ? 'field--error' : '' ?>" style="flex:1">
                <label for="categoryId"><?= e($t('admin.products.category')) ?></label>
                <select class="select" id="categoryId" name="categoryId">
                    <option value=""><?= e($t('admin.products.selectCategory')) ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (int) $field('categoryId') === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['categoryId'])): ?><span class="field__error"><?= e($errors['categoryId']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="description"><?= e($t('admin.common.description')) ?></label>
            <textarea class="textarea" id="description" name="description"><?= e((string) $field('description')) ?></textarea>
        </div>

        <div class="row gap-lg" style="align-items:flex-start">
            <div class="field <?= isset($errors['price']) ? 'field--error' : '' ?>" style="flex:1">
                <label for="price"><?= e($t('admin.products.priceIn', ['currency' => strtoupper($currency)])) ?></label>
                <input class="input mono" type="number" step="0.01" min="0" id="price" name="price" value="<?= e($priceValue) ?>" required>
                <?php if (isset($errors['price'])): ?><span class="field__error"><?= e($errors['price']) ?></span><?php endif; ?>
            </div>
            <div class="field <?= isset($errors['stockQuantity']) ? 'field--error' : '' ?>" style="flex:1">
                <label for="stockQuantity"><?= e($t('admin.products.stockQuantity')) ?></label>
                <input class="input mono" type="number" min="0" id="stockQuantity" name="stockQuantity" value="<?= e((string) $field('stockQuantity', '0')) ?>">
                <?php if (isset($errors['stockQuantity'])): ?><span class="field__error"><?= e($errors['stockQuantity']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="imageUrl"><?= e($t('admin.products.imageUrl')) ?></label>
            <input class="input" type="url" id="imageUrl" name="imageUrl" value="<?= e((string) $field('imageUrl')) ?>" placeholder="https://...">
        </div>

        <label class="checkbox">
            <input type="checkbox" name="isActive" value="1" <?= ($product === null || (int) $field('isActive', 1) === 1) ? 'checked' : '' ?>>
            <?= e($t('admin.products.visible')) ?>
        </label>
    </div>

    <div class="row mt-lg" style="gap:12px">
        <button class="btn btn--primary" type="submit"><?= e($editing ? $t('admin.common.saveChanges') : $t('admin.products.create')) ?></button>
        <a class="btn" href="/admin/products"><?= e($t('admin.common.cancel')) ?></a>
    </div>
</form>
