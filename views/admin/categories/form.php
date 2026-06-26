<?php
$editing = $category !== null && !empty($category['id']);
$action = $editing ? '/admin/categories/' . (int) $category['id'] : '/admin/categories';
$field = static fn (string $key) => $category[$key] ?? '';
?>
<div class="admin__head">
    <div>
        <p class="small muted"><a href="/admin/categories">Categories</a> / <?= $editing ? 'Edit' : 'New' ?></p>
        <h1 style="margin:0"><?= $editing ? e($category['name']) : 'New category' ?></h1>
    </div>
</div>

<form method="post" action="<?= e($action) ?>" style="max-width:600px">
    <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
    <div class="panel panel--pad">
        <div class="field <?= isset($errors['name']) ? 'field--error' : '' ?>">
            <label for="name">Name</label>
            <input class="input" type="text" id="name" name="name" value="<?= e((string) $field('name')) ?>" required>
            <?php if (isset($errors['name'])): ?><span class="field__error"><?= e($errors['name']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="slug">Slug</label>
            <input class="input" type="text" id="slug" name="slug" value="<?= e((string) $field('slug')) ?>" placeholder="auto-generated from name">
        </div>
        <div class="field">
            <label for="description">Description</label>
            <textarea class="textarea" id="description" name="description"><?= e((string) $field('description')) ?></textarea>
        </div>
    </div>
    <div class="row mt-lg" style="gap:12px">
        <button class="btn btn--primary" type="submit"><?= $editing ? 'Save changes' : 'Create category' ?></button>
        <a class="btn" href="/admin/categories">Cancel</a>
    </div>
</form>
