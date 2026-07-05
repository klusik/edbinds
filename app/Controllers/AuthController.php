<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Response;
use App\Core\Security;
use App\Core\Session;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\AuthService;

/**
 * Login, logout and registration controller.
 */
final class AuthController
{
    /**
     * Handle login.
     *
     * @return void
     */
    public function login(): void
    {
        $auth = new AuthService();
        if ($auth->user()) {
            Response::redirect('index.php?page=dashboard');
        }

        $error = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireValidPost();
            if ($auth->login(trim((string)($_POST['login'] ?? '')), (string)($_POST['password'] ?? ''))) {
                Response::redirect('index.php?page=dashboard');
            }
            $error = 'Invalid login or password.';
        }

        if (isset($_GET['installed'])) {
            Session::flash('success', 'Installation finished. Log in with the administrator account.');
        }

        View::render('auth/login', [
            'title' => 'Login',
            'user' => null,
            'error' => $error,
            'allowRegistration' => Config::getBool('app.allow_registration', true),
        ]);
    }

    /**
     * Handle logout.
     *
     * @return void
     */
    public function logout(): void
    {
        (new AuthService())->logout();
        Response::redirect('index.php');
    }

    /**
     * Handle registration.
     *
     * @return void
     */
    public function register(): void
    {
        if (!Config::getBool('app.allow_registration', true)) {
            Response::forbidden();
        }

        $auth = new AuthService();
        if ($auth->user()) {
            Response::redirect('index.php?page=dashboard');
        }

        $error = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireValidPost();
            $username = trim((string)($_POST['username'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
                $error = 'Use a username, a valid e-mail, and a password with at least 8 characters.';
            } else {
                try {
                    (new UserRepository())->createUser($username, $email, $password);
                    Session::flash('success', 'Account created. You can log in now.');
                    Response::redirect('index.php?page=login');
                } catch (\Throwable) {
                    $error = 'The username or e-mail is already used.';
                }
            }
        }

        View::render('auth/register', [
            'title' => 'Register',
            'user' => null,
            'error' => $error,
        ]);
    }
}
