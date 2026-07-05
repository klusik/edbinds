<?php

/**
 * Application bootstrap.
 *
 * The project intentionally avoids Composer so it can be uploaded unchanged to
 * basic shared hosting. The autoloader maps App\* classes to app/* files.
 */
define('APP_ROOT', __DIR__);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Config;
use App\Core\Session;

$configFile = APP_ROOT . '/config/config.php';

if (!is_file($configFile)) {
    if (PHP_SAPI !== 'cli') {
        http_response_code(503);
        echo '<!doctype html><meta charset="utf-8"><title>Install required</title>';
        echo '<style>body{font-family:system-ui;margin:3rem;max-width:760px}a{color:#f47b20}</style>';
        echo '<h1>Elite Bindings Vault is not installed</h1>';
        echo '<p>Create <code>config/config.php</code> by opening the installer.</p>';
        echo '<p><a href="install.php">Run installer</a></p>';
        exit;
    }

    throw new RuntimeException('Missing config/config.php. Run install.php first.');
}

Config::load($configFile);
Session::start(Config::getString('app.session_name', 'edbindings_session'));
