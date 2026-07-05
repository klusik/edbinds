<?php
use App\Core\Security;
/** @var string|null $error */
?>
<section class="auth-panel">
    <h1>Register</h1>
    <?php if ($error): ?><div class="flash flash-error"><?= Security::e($error) ?></div><?php endif; ?>
    <form method="post" class="form-card">
        <input type="hidden" name="_csrf" value="<?= Security::e(Security::csrfToken()) ?>">
        <label>Username
            <input name="username" required autocomplete="username">
        </label>
        <label>E-mail
            <input name="email" type="email" required autocomplete="email">
        </label>
        <label>Password
            <input name="password" type="password" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit">Create account</button>
    </form>
</section>
