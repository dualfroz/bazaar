<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin';
$isActive = static function (string $prefix) use ($path): string {
    if ($prefix === '/admin') {
        return $path === '/admin' ? 'active' : '';
    }
    return str_starts_with($path, $prefix) ? 'active' : '';
};
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? $appName . ' - ' . $t('admin.title')) ?></title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<?php if (!empty($flashes)): ?>
    <div class="flashes">
        <?php foreach ($flashes as $type => $message): ?>
            <div class="flash flash--<?= e($type === 'error' ? 'error' : 'success') ?>"><?= e($message) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="admin">
    <aside class="admin__side">
        <div class="admin__brand"><span class="brand__mark">B</span> <?= e($appName) ?> <?= e($t('admin.title')) ?></div>
        <nav class="admin__nav">
            <a class="<?= $isActive('/admin') ?>" href="/admin">
                <svg class="icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                <?= e($t('admin.nav.dashboard')) ?>
            </a>
            <a class="<?= $isActive('/admin/analytics') ?>" href="/admin/analytics">
                <svg class="icon" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 14l3-3 3 3 5-6"/></svg>
                <?= e($t('admin.nav.analytics')) ?>
            </a>
            <a class="<?= $isActive('/admin/products') ?>" href="/admin/products">
                <svg class="icon" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                <?= e($t('admin.nav.products')) ?>
            </a>
            <a class="<?= $isActive('/admin/attributes') ?>" href="/admin/attributes">
                <svg class="icon" viewBox="0 0 24 24"><path d="M20.6 13.3 13.3 20.6a2 2 0 0 1-2.8 0l-7-7A2 2 0 0 1 3 12V4h8a2 2 0 0 1 1.4.6l7 7a2 2 0 0 1 .2 1.7z"/><circle cx="7.5" cy="7.5" r="1"/></svg>
                <?= e($t('admin.nav.attributes')) ?>
            </a>
            <a class="<?= $isActive('/admin/categories') ?>" href="/admin/categories">
                <svg class="icon" viewBox="0 0 24 24"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
                <?= e($t('admin.nav.categories')) ?>
            </a>
            <a class="<?= $isActive('/admin/orders') ?>" href="/admin/orders">
                <svg class="icon" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/></svg>
                <?= e($t('admin.nav.orders')) ?>
            </a>
            <a class="<?= $isActive('/admin/reviews') ?>" href="/admin/reviews">
                <svg class="icon" viewBox="0 0 24 24"><path d="M11.5 3 14 8l5.5.8-4 3.9 1 5.5-4.9-2.6L6.5 21l1-5.5-4-3.9L9 8z"/></svg>
                <?= e($t('admin.nav.reviews')) ?>
            </a>
            <?php if ($can('coupons.manage')): ?>
                <a class="<?= $isActive('/admin/coupons') ?>" href="/admin/coupons">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M9 5H4a1 1 0 0 0-1 1v3a2 2 0 0 1 0 4v3a1 1 0 0 0 1 1h5m0-15h11a1 1 0 0 1 1 1v3a2 2 0 0 0 0 4v3a1 1 0 0 1-1 1H9m0-15v15"/></svg>
                    <?= e($t('admin.nav.coupons')) ?>
                </a>
            <?php endif; ?>
            <?php if ($can('users.manage')): ?>
                <a class="<?= $isActive('/admin/users') ?>" href="/admin/users">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/></svg>
                    <?= e($t('admin.nav.users')) ?>
                </a>
            <?php endif; ?>
        </nav>
        <div class="admin__side-foot">
            <div class="stack">
                <span style="color:#fff"><?= e($currentUser['name'] ?? $t('admin.staff')) ?></span>
                <span class="small" style="color:#9a948b"><?= e($t('roles.' . ($currentUser['role'] ?? 'customer'))) ?></span>
                <span class="lang-switch" aria-label="<?= e($t('layout.language')) ?>">
                    <?php foreach ($supportedLocales as $code): ?>
                        <a class="lang-switch__item <?= $locale === $code ? 'lang-switch__item--active' : '' ?>" href="/locale/<?= e($code) ?>"><?= e(strtoupper($code)) ?></a>
                    <?php endforeach; ?>
                </span>
                <div class="row" style="justify-content:space-between;margin-top:6px">
                    <a href="/"><?= e($t('admin.viewShop')) ?></a>
                    <form method="post" action="/logout" style="display:inline">
                        <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">
                        <button type="submit" class="btn btn--ghost btn--sm" style="color:#cfc9bf"><?= e($t('nav.signOut')) ?></button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    <main class="admin__main">
        <?= $content ?>
    </main>
</div>
</body>
</html>
