<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\Security;
use App\Core\Session;
use App\Core\View;
use App\Repositories\BindingRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

/**
 * Administrator controller.
 */
final class AdminController
{
    /**
     * Show admin overview.
     *
     * @return void
     */
    public function index(): void
    {
        $user = (new AuthService())->requireAdmin();
        View::render('admin/index', [
            'title' => 'Administration',
            'user' => $user,
            'sets' => (new BindingRepository())->allSetsForAdmin(),
            'users' => (new UserRepository())->recentUsers(),
        ]);
    }

    /**
     * Change public/private visibility.
     *
     * @return void
     */
    public function changeVisibility(): void
    {
        (new AuthService())->requireAdmin();
        Security::requireValidPost();
        $setId = (int)($_POST['binding_set_id'] ?? 0);
        $visibility = (string)($_POST['visibility'] ?? 'private') === 'public' ? 'public' : 'private';
        (new BindingRepository())->updateVisibility($setId, $visibility);
        Session::flash('success', 'Visibility updated.');
        Response::redirect('index.php?page=admin');
    }
}
