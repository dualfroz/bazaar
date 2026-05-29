<?php

namespace Bazaar\Services;

use Bazaar\Core\Session;
use Bazaar\Repositories\UserRepository;
use Bazaar\Support\Permissions;
use Bazaar\Support\Role;

final class Auth
{
    private const KEY = 'authUserId';
    private ?array $cachedUser = null;

    public function __construct(
        private readonly Session $session,
        private readonly UserRepository $users
    ) {
    }

    public function attempt(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return null;
        }
        if (!password_verify($password, $user['passwordHash'])) {
            return null;
        }
        return $user;
    }

    public function register(string $email, string $password, string $name): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        return $this->users->create($email, $hash, $name);
    }

    public function login(array $user): void
    {
        $this->session->regenerate();
        $this->session->put(self::KEY, (int) $user['id']);
        $this->cachedUser = $user;
    }

    public function logout(): void
    {
        $this->session->forget(self::KEY);
        $this->cachedUser = null;
        $this->session->regenerate();
    }

    public function check(): bool
    {
        return $this->session->has(self::KEY);
    }

    public function id(): ?int
    {
        $id = $this->session->get(self::KEY);
        return $id === null ? null : (int) $id;
    }

    public function user(): ?array
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }
        $id = $this->id();
        if ($id === null) {
            return null;
        }
        $this->cachedUser = $this->users->findById($id);
        return $this->cachedUser;
    }

    public function role(): string
    {
        $user = $this->user();
        if ($user === null) {
            return Role::customer;
        }
        if ((int) $user['isAdmin'] === 1) {
            return Role::admin;
        }
        return (string) ($user['role'] ?? Role::customer);
    }

    public function isAdmin(): bool
    {
        return $this->role() === Role::admin;
    }

    public function isStaff(): bool
    {
        return Role::isStaff($this->role());
    }

    public function can(string $capability): bool
    {
        return Permissions::allows($this->role(), $capability);
    }
}
