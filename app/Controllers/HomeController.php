<?php

namespace App\Controllers;

use App\Core\View;
use App\Repositories\BindingRepository;
use App\Services\AuthService;

/**
 * Public landing page controller.
 */
final class HomeController
{
    /**
     * Show public bindings.
     *
     * @return void
     */
    public function index(): void
    {
        $auth = new AuthService();
        $repo = new BindingRepository();
        View::render('home', [
            'title' => 'Public Elite Dangerous bindings',
            'user' => $auth->user(),
            'sets' => $repo->publicSets(),
        ]);
    }
}
