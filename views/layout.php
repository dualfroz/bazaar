<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? $appName . ' - ' . $t('layout.tagline')) ?></title>
    <link rel="stylesheet" href="/assets/app.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='7' fill='%23b4531f'/><text x='16' y='23' font-size='19' text-anchor='middle' fill='white' font-family='serif'>B</text></svg>">
</head>
<body>
<header class="site-header">
    <div class="wrap site-header__bar">
        <a class="brand" href="/">
            <span class="brand__mark">B</span>
            <?= e($appName) ?>
        </a>
        <nav class="nav">
            <a href="/products"><?= e($t('nav.shop')) ?></a>
            <?php foreach (array_slice($navCategories, 0, 3) as $navCategory): ?>
                <a class="hide-sm" href="/products?category=<?= e($navCategory['slug']) ?>"><?= e($navCategory['name']) ?></a>
            <?php endforeach; ?>
        </nav>
        <span class="nav__spacer"></span>
        <nav class="nav">
            <span class="lang-switch" aria-label="<?= e($t('layout.language')) ?>">
                <?php foreach ($supportedLocales as $code): ?>
                    <a class="lang-switch__item <?= $locale === $code ? 'lang-switch__item--active' : '' ?>" href="/locale/<?= e($code) ?>"><?= e(strtoupper($code)) ?></a>
                <?php endforeach; ?>
            </span>
            <?php if ($currentUser !== null): ?>
                <?php if ($isStaff): ?>
                    <a href="/admin"><?= e($t('nav.admin')) ?></a>
                <?php endif; ?>
                <a href="/account/wishlist"><?= e($t('nav.wishlist')) ?><?php if ($wishlistCount > 0): ?> (<?= (int) $wishlistCount ?>)<?php endif; ?></a>
                <a href="/account/orders"><?= e($t('nav.orders')) ?></a>
                <form method="post" action="/logout" style="display:inline">
                    <?= $csrfToken ? '<input type="hidden" name="csrfToken" value="' . e($csrfToken) . '">' : '' ?>
                    <button type="submit" class="btn btn--ghost btn--sm"><?= e($t('nav.signOut')) ?></button>
                </form>
            <?php else: ?>
                <a href="/login"><?= e($t('nav.signIn')) ?></a>
                <a class="btn btn--sm" href="/register"><?= e($t('nav.createAccount')) ?></a>
            <?php endif; ?>
            <a class="cart-link" href="/cart" aria-label="<?= e($t('nav.cart')) ?>">
                <svg class="icon" viewBox="0 0 24 24"><path d="M3 4h2l2.4 12.3a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.8L21 8H6"/><circle cx="9" cy="20" r="1.3"/><circle cx="18" cy="20" r="1.3"/></svg>
                <?php if ($cartCount > 0): ?><span class="cart-badge"><?= (int) $cartCount ?></span><?php endif; ?>
            </a>
        </nav>
    </div>
</header>

<?php if (!empty($flashes)): ?>
    <div class="flashes">
        <?php foreach ($flashes as $type => $message): ?>
            <div class="flash flash--<?= e($type === 'error' ? 'error' : 'success') ?>"><?= e($message) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="wrap site-footer__bar">
        <span>&copy; <?= date('Y') ?> <?= e($appName) ?>. <?= e($t('layout.builtWith')) ?></span>
        <span><?= e($t('layout.payments')) ?></span>
    </div>
</footer>
</body>
</html>
