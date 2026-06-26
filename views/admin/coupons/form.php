<div class="admin__head">
    <div>
        <h1 style="margin:0">New coupon</h1>
        <p class="muted" style="margin:0">Create a discount code.</p>
    </div>
    <a class="btn btn--ghost" href="/admin/coupons">Back</a>
</div>

<form method="post" action="/admin/coupons" class="panel panel--pad" style="max-width:640px">
    <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">

    <label class="field">
        <span class="field__label">Code</span>
        <input class="input" type="text" name="code" value="<?= e($_POST['code'] ?? '') ?>" placeholder="SAVE10" required>
        <?php if (!empty($errors['code'])): ?><span class="field__error"><?= e($errors['code']) ?></span><?php endif; ?>
    </label>

    <div class="row" style="gap:16px">
        <label class="field" style="flex:1">
            <span class="field__label">Type</span>
            <select class="select" name="type">
                <option value="percent">Percent</option>
                <option value="fixed">Fixed amount</option>
            </select>
        </label>
        <label class="field" style="flex:1">
            <span class="field__label">Value (percent, or amount)</span>
            <input class="input" type="number" name="value" step="0.01" min="0" value="<?= e($_POST['value'] ?? '') ?>" required>
            <?php if (!empty($errors['value'])): ?><span class="field__error"><?= e($errors['value']) ?></span><?php endif; ?>
        </label>
    </div>

    <div class="row" style="gap:16px">
        <label class="field" style="flex:1">
            <span class="field__label">Minimum cart</span>
            <input class="input" type="number" name="minSubtotal" step="0.01" min="0" value="<?= e($_POST['minSubtotal'] ?? '0') ?>">
        </label>
        <label class="field" style="flex:1">
            <span class="field__label">Max redemptions (blank = unlimited)</span>
            <input class="input" type="number" name="maxRedemptions" min="1" value="<?= e($_POST['maxRedemptions'] ?? '') ?>">
        </label>
    </div>

    <div class="row" style="gap:16px">
        <label class="field" style="flex:1">
            <span class="field__label">Starts at</span>
            <input class="input" type="date" name="startsAt" value="<?= e($_POST['startsAt'] ?? '') ?>">
        </label>
        <label class="field" style="flex:1">
            <span class="field__label">Expires at</span>
            <input class="input" type="date" name="expiresAt" value="<?= e($_POST['expiresAt'] ?? '') ?>">
        </label>
    </div>

    <label class="checkbox">
        <input type="checkbox" name="isActive" checked> Active
    </label>

    <div class="mt-lg">
        <button class="btn btn--primary" type="submit">Create coupon</button>
    </div>
</form>
