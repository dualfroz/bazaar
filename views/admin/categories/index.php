<div class="admin__head">
    <div>
        <h1 style="margin:0">Categories</h1>
        <p class="muted" style="margin:0"><?= count($categories) ?> categories.</p>
    </div>
    <a class="btn btn--primary" href="/admin/categories/create">New category</a>
</div>

<?php if (empty($categories)): ?>
    <div class="empty">
        <h3>No categories yet</h3>
        <p class="muted">Add a category before creating products.</p>
        <a class="btn btn--primary mt" href="/admin/categories/create">New category</a>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Name</th><th>Slug</th><th class="num">Products</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td style="font-weight:600"><?= e($category['name']) ?><div class="small muted"><?= e($category['description']) ?></div></td>
                        <td class="mono muted">/<?= e($category['slug']) ?></td>
                        <td class="num"><?= (int) $category['productCount'] ?></td>
                        <td>
                            <div class="row" style="gap:6px;justify-content:flex-end">
                                <a class="btn btn--sm" href="/admin/categories/<?= (int) $category['id'] ?>/edit">Edit</a>
                                <form method="post" action="/admin/categories/<?= (int) $category['id'] ?>/delete" onsubmit="return confirm('Delete this category?')">
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
