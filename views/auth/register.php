<section class="wrap">
    <div class="auth-shell">
        <div class="panel panel--pad">
            <h1 style="margin-bottom:6px"><?= e($t('auth.registerTitle')) ?></h1>
            <p class="muted"><?= e($t('auth.registerLead', ['app' => $appName])) ?></p>

            <form method="post" action="/register" class="mt-lg" novalidate>
                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">

                <div class="field <?= isset($errors['name']) ? 'field--error' : '' ?>">
                    <label for="name"><?= e($t('auth.fullName')) ?></label>
                    <input class="input" type="text" id="name" name="name" value="<?= e($old['name'] ?? '') ?>" autocomplete="name" autofocus>
                    <?php if (isset($errors['name'])): ?><span class="field__error"><?= e($errors['name']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['email']) ? 'field--error' : '' ?>">
                    <label for="email"><?= e($t('auth.email')) ?></label>
                    <input class="input" type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email">
                    <?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['password']) ? 'field--error' : '' ?>">
                    <label for="password"><?= e($t('auth.password')) ?></label>
                    <input class="input" type="password" id="password" name="password" autocomplete="new-password">
                    <?php if (isset($errors['password'])): ?>
                        <span class="field__error"><?= e($errors['password']) ?></span>
                    <?php else: ?>
                        <span class="small muted" style="margin-top:5px;display:block"><?= e($t('auth.passwordHint')) ?></span>
                    <?php endif; ?>
                </div>

                <button class="btn btn--primary btn--block" type="submit"><?= e($t('nav.createAccount')) ?></button>
            </form>

            <p class="small muted mt-lg" style="text-align:center;margin:0">
                <?= e($t('auth.haveAccount')) ?> <a href="/login"><?= e($t('nav.signIn')) ?></a>
            </p>
        </div>
    </div>
</section>
