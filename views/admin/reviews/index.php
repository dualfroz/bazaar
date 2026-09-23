<div class="admin__head">
    <div>
        <h1 style="margin:0"><?= e($t('admin.nav.reviews')) ?></h1>
        <p class="muted" style="margin:0"><?= e($t('admin.reviews.awaiting', ['count' => count($pending)])) ?></p>
    </div>
</div>

<div class="section-head"><h3 style="margin:0"><?= e($t('admin.reviews.pending')) ?></h3></div>
<?php if (empty($pending)): ?>
    <div class="empty"><p class="muted" style="margin:0"><?= e($t('admin.reviews.nothingPending')) ?></p></div>
<?php else: ?>
    <div class="stack" style="gap:12px">
        <?php foreach ($pending as $review): ?>
            <div class="panel panel--pad review-card">
                <div class="row" style="justify-content:space-between;align-items:flex-start">
                    <div>
                        <span class="rating__stars"><?= str_repeat('*', (int) $review['rating']) ?><span class="rating__off"><?= str_repeat('*', 5 - (int) $review['rating']) ?></span></span>
                        <strong style="margin-left:8px"><?= e($review['title'] !== '' ? $review['title'] : $t('product.reviewFallbackTitle')) ?></strong>
                        <div class="small muted">
                            <a href="/products/<?= e($review['productSlug']) ?>"><?= e($review['productName']) ?></a>
                            &middot; <?= e($review['authorName']) ?> (<?= e($review['authorEmail']) ?>)
                            &middot; <?= e(substr((string) $review['createdAt'], 0, 10)) ?>
                        </div>
                    </div>
                    <div class="row" style="gap:8px">
                        <form method="post" action="/admin/reviews/<?= (int) $review['id'] ?>/approve">
                            <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                            <button class="btn btn--primary btn--sm" type="submit"><?= e($t('admin.reviews.approve')) ?></button>
                        </form>
                        <form method="post" action="/admin/reviews/<?= (int) $review['id'] ?>/reject">
                            <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                            <button class="btn btn--danger btn--sm" type="submit"><?= e($t('admin.reviews.reject')) ?></button>
                        </form>
                    </div>
                </div>
                <?php if ($review['body'] !== ''): ?>
                    <p style="margin:10px 0 0"><?= nl2br(e($review['body'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="section-head mt-lg"><h3 style="margin:0"><?= e($t('admin.reviews.approved', ['count' => count($approved)])) ?></h3></div>
<?php if (empty($approved)): ?>
    <div class="empty"><p class="muted" style="margin:0"><?= e($t('admin.reviews.noneApproved')) ?></p></div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?= e($t('admin.products.product')) ?></th><th><?= e($t('product.rating')) ?></th><th><?= e($t('product.reviewTitle')) ?></th><th><?= e($t('admin.reviews.author')) ?></th></tr></thead>
            <tbody>
                <?php foreach ($approved as $review): ?>
                    <tr>
                        <td><a href="/products/<?= e($review['productSlug']) ?>"><?= e($review['productName']) ?></a></td>
                        <td><?= str_repeat('*', (int) $review['rating']) ?></td>
                        <td><?= e($review['title']) ?></td>
                        <td class="muted"><?= e($review['authorName']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
