<?php

namespace App\Core;

/**
 * Security helpers for escaping, CSRF and password hashing.
 */
final class Security
{
    /**
     * Escape HTML output.
     *
     * @param mixed $value Value to escape.
     * @return string Escaped value.
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Return or create the CSRF token.
     *
     * @return string CSRF token.
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = hash_hmac('sha256', bin2hex(random_bytes(32)), Config::getString('security.csrf_key'));
        }

        return (string)$_SESSION['csrf_token'];
    }

    /**
     * Validate a submitted CSRF token.
     *
     * @param string|null $token Submitted token.
     * @return bool True when the token is valid.
     */
    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token) && hash_equals(self::csrfToken(), $token);
    }

    /**
     * Hash a password with the configured pepper.
     *
     * @param string $password Plain password.
     * @return string Password hash.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password . Config::getString('security.password_pepper'), PASSWORD_DEFAULT);
    }

    /**
     * Verify a password with the configured pepper.
     *
     * @param string $password Plain password.
     * @param string $hash Stored password hash.
     * @return bool True when valid.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password . Config::getString('security.password_pepper'), $hash);
    }

    /**
     * Abort unless the current request is a valid POST request.
     *
     * @return void
     */
    public static function requireValidPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !self::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            exit('Invalid CSRF token.');
        }
    }
}
