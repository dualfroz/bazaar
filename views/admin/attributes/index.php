<div class="admin__head">
    <div>
        <h1 style="margin:0"><?= e($t('admin.nav.attributes')) ?></h1>
        <p class="muted" style="margin:0"><?= e($t('admin.attributes.lead')) ?></p>
    </div>
</div>

<div class="split" style="grid-template-columns:1fr 1.4fr">
    <div class="panel panel--pad">
        <h3 style="margin-top:0"><?= e($t('admin.attributes.new')) ?></h3>
        <form method="post" action="/admin/attributes" class="row" style="gap:8px">
            <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
            <input class="input" type="text" name="name" placeholder="<?= e($t('admin.attributes.namePlaceholder')) ?>" required>
            <button class="btn btn--primary" type="submit"><?= e($t('admin.common.add')) ?></button>
        </form>
    </div>

    <div>
        <?php if (empty($attributes)): ?>
            <div class="empty"><p class="muted" style="margin:0"><?= e($t('admin.attributes.empty')) ?></p></div>
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
                            <?php if ($attribute['values'] === []): ?><span class="small muted"><?= e($t('admin.attributes.noValues')) ?></span><?php endif; ?>
                        </div>
                        <form method="post" action="/admin/attributes/<?= (int) $attribute['id'] ?>/values" class="row mt" style="gap:8px">
                            <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                            <input class="input input--sm" type="text" name="value" placeholder="<?= e($t('admin.attributes.valuePlaceholder')) ?>" required>
                            <button class="btn btn--sm" type="submit"><?= e($t('admin.attributes.addValue')) ?></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
