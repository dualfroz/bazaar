<?php

namespace Bazaar\Repositories;

use Bazaar\Core\Database;

final class UserRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM users WHERE email = ?',
            [strtolower(trim($email))]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function emailExists(string $email): bool
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM users WHERE email = ?',
            [strtolower(trim($email))]
        ) > 0;
    }

    public function create(string $email, string $passwordHash, string $name, bool $isAdmin = false, string $role = 'customer'): int
    {
        if ($isAdmin) {
            $role = 'admin';
        }
        return $this->db->insert(
            'INSERT INTO users (email, passwordHash, name, isAdmin, role, createdAt)
             VALUES (?, ?, ?, ?, ?, ?)',
            [strtolower(trim($email)), $passwordHash, $name, $isAdmin ? 1 : 0, $role, gmdate('c')]
        );
    }

    public function setRole(int $id, string $role): void
    {
        $this->db->statement(
            'UPDATE users SET role = ?, isAdmin = ? WHERE id = ?',
            [$role, $role === 'admin' ? 1 : 0, $id]
        );
    }

    public function all(): array
    {
        return $this->db->select('SELECT id, email, name, role, isAdmin, createdAt FROM users ORDER BY createdAt DESC, id DESC');
    }

    public function count(): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM users');
    }
}
