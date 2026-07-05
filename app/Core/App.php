<?php

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\BindingController;
use App\Controllers\HomeController;

/**
 * Query-parameter router for shared hosting without rewrite requirements.
 */
final class App
{
    /**
     * Dispatch the current request.
     *
     * @return void
     */
    public function run(): void
    {
        $page = (string)($_GET['page'] ?? 'home');

        match ($page) {
            'home' => (new HomeController())->index(),
            'login' => (new AuthController())->login(),
            'logout' => (new AuthController())->logout(),
            'register' => (new AuthController())->register(),
            'dashboard' => (new BindingController())->dashboard(),
            'upload' => (new BindingController())->upload(),
            'view' => (new BindingController())->view(),
            'download' => (new BindingController())->download(),
            'admin' => (new AdminController())->index(),
            'admin-visibility' => (new AdminController())->changeVisibility(),
            default => Response::notFound(),
        };
    }
}
