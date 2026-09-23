<div class="admin__head">
    <div>
        <h1 style="margin:0"><?= e($t('admin.users.title')) ?></h1>
        <p class="muted" style="margin:0"><?= e($t('admin.users.lead')) ?></p>
    </div>
</div>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= e($t('admin.common.name')) ?></th><th><?= e($t('auth.email')) ?></th><th><?= e($t('admin.users.joined')) ?></th><th><?= e($t('admin.users.role')) ?></th></tr></thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td style="font-weight:600"><?= e($user['name']) ?></td>
                    <td class="muted"><?= e($user['email']) ?></td>
                    <td class="small muted"><?= e(substr((string) $user['createdAt'], 0, 10)) ?></td>
                    <td>
                        <?php if ((int) $user['id'] === (int) ($currentUser['id'] ?? 0)): ?>
                            <span class="badge badge--paid"><?= e($t('admin.users.you', ['role' => $t('roles.' . $user['role'])])) ?></span>
                        <?php else: ?>
                            <form method="post" action="/admin/users/<?= (int) $user['id'] ?>/role" class="row" style="gap:8px">
                                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                                <select class="select select--sm" name="role">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= e($role) ?>" <?= $user['role'] === $role ? 'selected' : '' ?>><?= e($t('roles.' . $role)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn--sm" type="submit"><?= e($t('admin.common.save')) ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
