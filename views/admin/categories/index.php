<div class="admin__head">
    <div>
        <h1 style="margin:0"><?= e($t('admin.nav.categories')) ?></h1>
        <p class="muted" style="margin:0"><?= e($t('admin.categories.count', ['count' => count($categories)])) ?></p>
    </div>
    <a class="btn btn--primary" href="/admin/categories/create"><?= e($t('admin.categories.new')) ?></a>
</div>

<?php if (empty($categories)): ?>
    <div class="empty">
        <h3><?= e($t('admin.categories.empty')) ?></h3>
        <p class="muted"><?= e($t('admin.categories.emptyBody')) ?></p>
        <a class="btn btn--primary mt" href="/admin/categories/create"><?= e($t('admin.categories.new')) ?></a>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?= e($t('admin.common.name')) ?></th><th><?= e($t('admin.common.slug')) ?></th><th class="num"><?= e($t('admin.nav.products')) ?></th><th></th></tr></thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td style="font-weight:600"><?= e($category['name']) ?><div class="small muted"><?= e($category['description']) ?></div></td>
                        <td class="mono muted">/<?= e($category['slug']) ?></td>
                        <td class="num"><?= (int) $category['productCount'] ?></td>
                        <td>
                            <div class="row" style="gap:6px;justify-content:flex-end">
                                <a class="btn btn--sm" href="/admin/categories/<?= (int) $category['id'] ?>/edit"><?= e($t('admin.common.edit')) ?></a>
                                <form method="post" action="/admin/categories/<?= (int) $category['id'] ?>/delete" onsubmit="return confirm(<?= e(json_encode($t('admin.categories.confirmDelete'))) ?>)">
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
