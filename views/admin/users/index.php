<div class="admin__head">
    <div>
        <h1 style="margin:0">Users and roles</h1>
        <p class="muted" style="margin:0">Grant staff access. Admins manage everything, managers run the catalog, orders and reviews.</p>
    </div>
</div>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Joined</th><th>Role</th></tr></thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td style="font-weight:600"><?= e($user['name']) ?></td>
                    <td class="muted"><?= e($user['email']) ?></td>
                    <td class="small muted"><?= e(substr((string) $user['createdAt'], 0, 10)) ?></td>
                    <td>
                        <?php if ((int) $user['id'] === (int) ($currentUser['id'] ?? 0)): ?>
                            <span class="badge badge--paid"><?= e(\Bazaar\Support\Role::label($user['role'])) ?> (you)</span>
                        <?php else: ?>
                            <form method="post" action="/admin/users/<?= (int) $user['id'] ?>/role" class="row" style="gap:8px">
                                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                                <select class="select select--sm" name="role">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= e($role) ?>" <?= $user['role'] === $role ? 'selected' : '' ?>><?= e(\Bazaar\Support\Role::label($role)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn--sm" type="submit">Save</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
