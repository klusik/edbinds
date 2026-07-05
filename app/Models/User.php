<?php

namespace App\Models;

/**
 * Authenticated user value object.
 */
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $role
    ) {
    }

    /**
     * Create a user object from a database row.
     *
     * @param array<string,mixed> $row User row.
     * @return self User object.
     */
    public static function fromRow(array $row): self
    {
        return new self((int)$row['id'], (string)$row['username'], (string)$row['email'], (string)$row['role']);
    }

    /**
     * Check administrator role.
     *
     * @return bool True for administrators.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
