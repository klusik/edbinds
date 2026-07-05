<?php
use App\Core\Config;
use App\Core\Security;
/** @var string $content */
/** @var string $title */
/** @var array<int,array{type:string,message:string}> $flash */
/** @var App\Models\User|null $user */
$appName = Config::getString('app.name', 'Elite Bindings Vault');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Security::e($title) ?> | <?= Security::e($appName) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script defer src="assets/js/app.js"></script>
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php" aria-label="Home">
        <span class="brand-mark">ED</span>
        <span><?= Security::e($appName) ?></span>
    </a>
    <nav class="site-nav" aria-label="Main navigation">
        <a href="index.php">Public</a>
        <?php if ($user): ?>
            <a href="index.php?page=dashboard">My bindings</a>
            <a href="index.php?page=upload">Upload</a>
            <?php if ($user->isAdmin()): ?>
                <a href="index.php?page=admin">Admin</a>
            <?php endif; ?>
            <a href="index.php?page=logout">Logout <?= Security::e($user->username) ?></a>
        <?php else: ?>
            <a href="index.php?page=login">Login</a>
        <?php endif; ?>
    </nav>
</header>

<main class="page-shell">
    <?php foreach ($flash as $message): ?>
        <div class="flash flash-<?= Security::e($message['type']) ?>"><?= Security::e($message['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
</body>
</html>
