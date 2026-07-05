<?php

namespace App\Core;

/**
 * Session and flash message helper.
 */
final class Session
{
    /**
     * Start the application session.
     *
     * @param string $name Session cookie name.
     * @return void
     */
    public static function start(string $name): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /**
     * Store a flash message.
     *
     * @param string $type Message type.
     * @param string $message Message text.
     * @return void
     */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Return and clear all flash messages.
     *
     * @return array<int,array{type:string,message:string}> Flash messages.
     */
    public static function consumeFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return is_array($messages) ? $messages : [];
    }
}
