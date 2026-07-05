<?php
use App\Core\Security;
/** @var string|null $error */
/** @var bool $allowRegistration */
?>
<section class="auth-panel">
    <h1>Login</h1>
    <?php if ($error): ?><div class="flash flash-error"><?= Security::e($error) ?></div><?php endif; ?>
    <form method="post" class="form-card">
        <input type="hidden" name="_csrf" value="<?= Security::e(Security::csrfToken()) ?>">
        <label>Username or e-mail
            <input name="login" required autofocus autocomplete="username">
        </label>
        <label>Password
            <input name="password" type="password" required autocomplete="current-password">
        </label>
        <button type="submit">Login</button>
    </form>
    <?php if ($allowRegistration): ?>
        <p class="small-link"><a href="index.php?page=register">Create an account</a></p>
    <?php endif; ?>
</section>
