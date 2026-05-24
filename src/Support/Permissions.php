<?php

namespace Bazaar\Support;

final class Permissions
{
    public const catalogManage = 'catalog.manage';
    public const ordersManage = 'orders.manage';
    public const reviewsModerate = 'reviews.moderate';
    public const analyticsView = 'analytics.view';
    public const couponsManage = 'coupons.manage';
    public const usersManage = 'users.manage';

    private const managerGrants = [
        self::catalogManage,
        self::ordersManage,
        self::reviewsModerate,
        self::analyticsView,
    ];

    public static function allows(string $role, string $capability): bool
    {
        if ($role === Role::admin) {
            return true;
        }
        if ($role === Role::manager) {
            return in_array($capability, self::managerGrants, true);
        }
        return false;
    }
}
