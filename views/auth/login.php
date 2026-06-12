<section class="wrap">
    <div class="auth-shell">
        <div class="panel panel--pad">
            <h1 style="margin-bottom:6px">Welcome back</h1>
            <p class="muted">Sign in to track orders and check out faster.</p>

            <form method="post" action="/login" class="mt-lg" novalidate>
                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">

                <div class="field <?= isset($errors['email']) ? 'field--error' : '' ?>">
                    <label for="email">Email</label>
                    <input class="input" type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" autofocus>
                    <?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input class="input" type="password" id="password" name="password" autocomplete="current-password">
                </div>

                <button class="btn btn--primary btn--block" type="submit">Sign in</button>
            </form>

            <p class="small muted mt-lg" style="text-align:center;margin:0">
                No account yet? <a href="/register">Create one</a>
            </p>
        </div>
    </div>
</section>
