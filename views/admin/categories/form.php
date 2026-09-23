<?php
$editing = $category !== null && !empty($category['id']);
$action = $editing ? '/admin/categories/' . (int) $category['id'] : '/admin/categories';
$field = static fn (string $key) => $category[$key] ?? '';
?>
<div class="admin__head">
    <div>
        <p class="small muted"><a href="/admin/categories"><?= e($t('admin.nav.categories')) ?></a> / <?= e($editing ? $t('admin.common.edit') : $t('admin.common.new')) ?></p>
        <h1 style="margin:0"><?= e($editing ? $category['name'] : $t('admin.categories.new')) ?></h1>
    </div>
</div>

<form method="post" action="<?= e($action) ?>" style="max-width:600px">
    <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
    <div class="panel panel--pad">
        <div class="field <?= isset($errors['name']) ? 'field--error' : '' ?>">
            <label for="name"><?= e($t('admin.common.name')) ?></label>
            <input class="input" type="text" id="name" name="name" value="<?= e((string) $field('name')) ?>" required>
            <?php if (isset($errors['name'])): ?><span class="field__error"><?= e($errors['name']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="slug"><?= e($t('admin.common.slug')) ?></label>
            <input class="input" type="text" id="slug" name="slug" value="<?= e((string) $field('slug')) ?>" placeholder="<?= e($t('admin.common.slugPlaceholder')) ?>">
        </div>
        <div class="field">
            <label for="description"><?= e($t('admin.common.description')) ?></label>
            <textarea class="textarea" id="description" name="description"><?= e((string) $field('description')) ?></textarea>
        </div>
    </div>
    <div class="row mt-lg" style="gap:12px">
        <button class="btn btn--primary" type="submit"><?= e($editing ? $t('admin.common.saveChanges') : $t('admin.categories.create')) ?></button>
        <a class="btn" href="/admin/categories"><?= e($t('admin.common.cancel')) ?></a>
    </div>
</form>
