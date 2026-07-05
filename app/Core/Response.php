<?php

namespace App\Core;

/**
 * Response utilities.
 */
final class Response
{
    /**
     * Redirect to a local URL.
     *
     * @param string $url Target URL.
     * @return never
     */
    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Emit a 404 response.
     *
     * @return never
     */
    public static function notFound(): never
    {
        http_response_code(404);
        echo 'Not found';
        exit;
    }

    /**
     * Emit a 403 response.
     *
     * @return never
     */
    public static function forbidden(): never
    {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}
