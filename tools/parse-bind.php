<?php

/**
 * CLI helper for parser checks without a database.
 *
 * Usage:
 * php tools/parse-bind.php /path/to/Custom.4.2.binds
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

$root = dirname(__DIR__);
define('APP_ROOT', $root);
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

$file = $argv[1] ?? '';
if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Pass a .binds file path.\n");
    exit(1);
}

$xml = file_get_contents($file);
$parser = new App\Services\BindParser();
$parsed = $parser->parseString((string)$xml, basename($file));
echo json_encode($parsed['stats'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
