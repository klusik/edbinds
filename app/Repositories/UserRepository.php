<?php

namespace App\Repositories;

use App\Core\Database;
use App\Core\Security;
use App\Models\User;
use PDO;

/**
 * Database access for user records.
 */
final class UserRepository
{
    private PDO $pdo;

    /**
     * Create the repository.
     */
    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /**
     * Find a user by id.
     *
     * @param int $id User id.
     * @return User|null User object or null.
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? User::fromRow($row) : null;
    }

    /**
     * Find a user row for authentication.
     *
     * @param string $login Username or e-mail.
     * @return array<string,mixed>|null User row with password hash.
     */
    public function findLoginRow(string $login): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$login, $login]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Create a normal user.
     *
     * @param string $username Username.
     * @param string $email E-mail address.
     * @param string $password Plain password.
     * @return int New user id.
     */
    public function createUser(string $username, string $email, string $password): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([$username, $email, Security::hashPassword($password), 'user']);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Mark a successful login.
     *
     * @param int $id User id.
     * @return void
     */
    public function touchLogin(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Return recent users for administration.
     *
     * @return array<int,array<string,mixed>> User rows.
     */
    public function recentUsers(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, email, role, created_at, last_login_at FROM users ORDER BY id DESC LIMIT 50');
        return $stmt->fetchAll();
    }
}
