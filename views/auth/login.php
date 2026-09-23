<section class="wrap">
    <div class="auth-shell">
        <div class="panel panel--pad">
            <h1 style="margin-bottom:6px"><?= e($t('auth.loginTitle')) ?></h1>
            <p class="muted"><?= e($t('auth.loginLead')) ?></p>

            <form method="post" action="/login" class="mt-lg" novalidate>
                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">

                <div class="field <?= isset($errors['email']) ? 'field--error' : '' ?>">
                    <label for="email"><?= e($t('auth.email')) ?></label>
                    <input class="input" type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" autofocus>
                    <?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label for="password"><?= e($t('auth.password')) ?></label>
                    <input class="input" type="password" id="password" name="password" autocomplete="current-password">
                </div>

                <button class="btn btn--primary btn--block" type="submit"><?= e($t('nav.signIn')) ?></button>
            </form>

            <p class="small muted mt-lg" style="text-align:center;margin:0">
                <?= e($t('auth.noAccount')) ?> <a href="/register"><?= e($t('auth.createOne')) ?></a>
            </p>
        </div>
    </div>
</section>
