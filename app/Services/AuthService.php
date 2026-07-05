<?php

namespace App\Services;

use App\Core\Response;
use App\Core\Security;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Authentication and authorization facade.
 */
final class AuthService
{
    private UserRepository $users;

    /**
     * Create the service.
     */
    public function __construct()
    {
        $this->users = new UserRepository();
    }

    /**
     * Return the current authenticated user.
     *
     * @return User|null Current user or null.
     */
    public function user(): ?User
    {
        $id = (int)($_SESSION['user_id'] ?? 0);
        return $id > 0 ? $this->users->findById($id) : null;
    }

    /**
     * Require an authenticated user.
     *
     * @return User Current user.
     */
    public function requireUser(): User
    {
        $user = $this->user();
        if (!$user instanceof User) {
            Response::redirect('index.php?page=login');
        }

        return $user;
    }

    /**
     * Require an administrator.
     *
     * @return User Current admin user.
     */
    public function requireAdmin(): User
    {
        $user = $this->requireUser();
        if (!$user->isAdmin()) {
            Response::forbidden();
        }

        return $user;
    }

    /**
     * Attempt a login.
     *
     * @param string $login Username or e-mail.
     * @param string $password Plain password.
     * @return bool True when login succeeded.
     */
    public function login(string $login, string $password): bool
    {
        $row = $this->users->findLoginRow($login);
        if (!$row || !Security::verifyPassword($password, (string)$row['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$row['id'];
        $this->users->touchLogin((int)$row['id']);
        return true;
    }

    /**
     * End the current session.
     *
     * @return void
     */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }
}
