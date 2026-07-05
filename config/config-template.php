<?php

/**
 * Configuration template used by install.php.
 *
 * Copying this file manually to config/config.php is supported, but the normal
 * path is to open install.php in the browser and let the installer write the
 * final config file after it validates the MariaDB connection.
 */
return [
    'app' => [
        'name' => 'Elite Bindings Vault',
        'base_url' => '',
        'environment' => 'production',
        'debug' => false,
        'session_name' => 'edbindings_session',
        'allow_registration' => true,
        'upload_max_bytes' => 1048576,
        'default_theme' => 'elite',
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => '',
        'user' => '',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'csrf_key' => 'change-this-during-install',
        'password_pepper' => '',
    ],
];
