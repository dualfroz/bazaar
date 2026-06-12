<section class="wrap">
    <div class="auth-shell">
        <div class="panel panel--pad">
            <h1 style="margin-bottom:6px">Create your account</h1>
            <p class="muted">Join Bazaar to place orders and keep your history in one place.</p>

            <form method="post" action="/register" class="mt-lg" novalidate>
                <input type="hidden" name="csrfToken" value="<?= e($csrfToken) ?>">

                <div class="field <?= isset($errors['name']) ? 'field--error' : '' ?>">
                    <label for="name">Full name</label>
                    <input class="input" type="text" id="name" name="name" value="<?= e($old['name'] ?? '') ?>" autocomplete="name" autofocus>
                    <?php if (isset($errors['name'])): ?><span class="field__error"><?= e($errors['name']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['email']) ? 'field--error' : '' ?>">
                    <label for="email">Email</label>
                    <input class="input" type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email">
                    <?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['password']) ? 'field--error' : '' ?>">
                    <label for="password">Password</label>
                    <input class="input" type="password" id="password" name="password" autocomplete="new-password">
                    <?php if (isset($errors['password'])): ?>
                        <span class="field__error"><?= e($errors['password']) ?></span>
                    <?php else: ?>
                        <span class="small muted" style="margin-top:5px;display:block">At least 8 characters.</span>
                    <?php endif; ?>
                </div>

                <button class="btn btn--primary btn--block" type="submit">Create account</button>
            </form>

            <p class="small muted mt-lg" style="text-align:center;margin:0">
                Already have an account? <a href="/login">Sign in</a>
            </p>
        </div>
    </div>
</section>
