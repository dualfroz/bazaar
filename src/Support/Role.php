<?php

namespace Bazaar\Support;

final class Role
{
    public const customer = 'customer';
    public const manager = 'manager';
    public const admin = 'admin';

    public const staffRoles = [self::manager, self::admin];

    public static function label(string $role): string
    {
        return match ($role) {
            self::admin => 'Administrator',
            self::manager => 'Manager',
            default => 'Customer',
        };
    }

    public static function isStaff(string $role): bool
    {
        return in_array($role, self::staffRoles, true);
    }

    public static function all(): array
    {
        return [self::customer, self::manager, self::admin];
    }
}
