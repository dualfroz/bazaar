<?php
$editing = $product !== null && !empty($product['id']);
$action = $editing ? '/admin/products/' . (int) $product['id'] : '/admin/products';
$priceValue = $product !== null ? number_format((int) ($product['priceCents'] ?? 0) / 100, 2, '.', '') : '';
$field = static fn (string $key, $fallback = '') => $product[$key] ?? $fallback;
?>
<div class="admin__head">
    <div>
        <p class="small muted"><a href="/admin/products">Products</a> / <?= $editing ? 'Edit' : 'New' ?></p>
        <h1 style="margin:0"><?= $editing ? e($product['name']) : 'New product' ?></h1>
    </div>
</div>

<form method="post" action="<?= e($action) ?>" style="max-width:720px">
    <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">

    <div class="panel panel--pad">
        <div class="field <?= isset($errors['name']) ? 'field--error' : '' ?>">
            <label for="name">Name</label>
            <input class="input" type="text" id="name" name="name" value="<?= e((string) $field('name')) ?>" required>
            <?php if (isset($errors['name'])): ?><span class="field__error"><?= e($errors['name']) ?></span><?php endif; ?>
        </div>

        <div class="row gap-lg" style="align-items:flex-start">
            <div class="field" style="flex:1">
                <label for="slug">Slug</label>
                <input class="input" type="text" id="slug" name="slug" value="<?= e((string) $field('slug')) ?>" placeholder="auto-generated from name">
            </div>
            <div class="field <?= isset($errors['categoryId']) ? 'field--error' : '' ?>" style="flex:1">
                <label for="categoryId">Category</label>
                <select class="select" id="categoryId" name="categoryId">
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (int) $field('categoryId') === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['categoryId'])): ?><span class="field__error"><?= e($errors['categoryId']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea class="textarea" id="description" name="description"><?= e((string) $field('description')) ?></textarea>
        </div>

        <div class="row gap-lg" style="align-items:flex-start">
            <div class="field <?= isset($errors['price']) ? 'field--error' : '' ?>" style="flex:1">
                <label for="price">Price (<?= e(strtoupper($currency)) ?>)</label>
                <input class="input mono" type="number" step="0.01" min="0" id="price" name="price" value="<?= e($priceValue) ?>" required>
                <?php if (isset($errors['price'])): ?><span class="field__error"><?= e($errors['price']) ?></span><?php endif; ?>
            </div>
            <div class="field <?= isset($errors['stockQuantity']) ? 'field--error' : '' ?>" style="flex:1">
                <label for="stockQuantity">Stock quantity</label>
                <input class="input mono" type="number" min="0" id="stockQuantity" name="stockQuantity" value="<?= e((string) $field('stockQuantity', '0')) ?>">
                <?php if (isset($errors['stockQuantity'])): ?><span class="field__error"><?= e($errors['stockQuantity']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="imageUrl">Image URL</label>
            <input class="input" type="url" id="imageUrl" name="imageUrl" value="<?= e((string) $field('imageUrl')) ?>" placeholder="https://...">
        </div>

        <label class="checkbox">
            <input type="checkbox" name="isActive" value="1" <?= ($product === null || (int) $field('isActive', 1) === 1) ? 'checked' : '' ?>>
            Visible in the storefront
        </label>
    </div>

    <div class="row mt-lg" style="gap:12px">
        <button class="btn btn--primary" type="submit"><?= $editing ? 'Save changes' : 'Create product' ?></button>
        <a class="btn" href="/admin/products">Cancel</a>
    </div>
</form>
