<div class="admin__head">
    <div>
        <h1 style="margin:0">Attributes</h1>
        <p class="muted" style="margin:0">Options like Size or Color that product variants are built from.</p>
    </div>
</div>

<div class="split" style="grid-template-columns:1fr 1.4fr">
    <div class="panel panel--pad">
        <h3 style="margin-top:0">New attribute</h3>
        <form method="post" action="/admin/attributes" class="row" style="gap:8px">
            <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
            <input class="input" type="text" name="name" placeholder="Color" required>
            <button class="btn btn--primary" type="submit">Add</button>
        </form>
    </div>

    <div>
        <?php if (empty($attributes)): ?>
            <div class="empty"><p class="muted" style="margin:0">No attributes yet. Add one to start building variants.</p></div>
        <?php else: ?>
            <div class="stack" style="gap:12px">
                <?php foreach ($attributes as $attribute): ?>
                    <div class="panel panel--pad">
                        <div class="row" style="justify-content:space-between;align-items:center">
                            <strong><?= e($attribute['name']) ?></strong>
                            <span class="small muted mono"><?= e($attribute['slug']) ?></span>
                        </div>
                        <div class="chips mt">
                            <?php foreach ($attribute['values'] as $value): ?>
                                <span class="chip"><?= e($value['value']) ?></span>
                            <?php endforeach; ?>
                            <?php if ($attribute['values'] === []): ?><span class="small muted">No values yet.</span><?php endif; ?>
                        </div>
                        <form method="post" action="/admin/attributes/<?= (int) $attribute['id'] ?>/values" class="row mt" style="gap:8px">
                            <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                            <input class="input input--sm" type="text" name="value" placeholder="Add a value" required>
                            <button class="btn btn--sm" type="submit">Add value</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
