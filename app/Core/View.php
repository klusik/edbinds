<?php

namespace App\Core;

/**
 * Minimal server-side view renderer.
 */
final class View
{
    /**
     * Render a template inside the main layout.
     *
     * @param string $template Template path relative to app/Views without .php.
     * @param array<string,mixed> $data Template variables.
     * @return void
     */
    public static function render(string $template, array $data = []): void
    {
        $templateFile = APP_ROOT . '/app/Views/' . $template . '.php';
        if (!is_file($templateFile)) {
            throw new \RuntimeException('Missing view: ' . $template);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $templateFile;
        $content = ob_get_clean();
        $title = $data['title'] ?? Config::getString('app.name', 'Elite Bindings Vault');
        $flash = Session::consumeFlash();
        require APP_ROOT . '/app/Views/layout.php';
    }
}
