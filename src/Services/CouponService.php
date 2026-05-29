<?php

namespace Bazaar\Services;

use Bazaar\Repositories\CouponRepository;

final class CouponService
{
    public function __construct(private readonly CouponRepository $coupons)
    {
    }

    public function evaluate(string $code, int $subtotalCents, ?string $now = null): CouponResult
    {
        $coupon = $this->coupons->findByCode($code);
        if ($coupon === null) {
            return CouponResult::fail(CouponResult::notFound);
        }

        $now ??= gmdate('c');

        if ((int) $coupon['isActive'] !== 1) {
            return CouponResult::fail(CouponResult::inactive, $coupon);
        }
        if ($coupon['startsAt'] !== null && $coupon['startsAt'] !== '' && $now < $coupon['startsAt']) {
            return CouponResult::fail(CouponResult::notStarted, $coupon);
        }
        if ($coupon['expiresAt'] !== null && $coupon['expiresAt'] !== '' && $now > $coupon['expiresAt']) {
            return CouponResult::fail(CouponResult::expired, $coupon);
        }
        if ($coupon['maxRedemptions'] !== null && (int) $coupon['redeemedCount'] >= (int) $coupon['maxRedemptions']) {
            return CouponResult::fail(CouponResult::exhausted, $coupon);
        }
        if ($subtotalCents < (int) $coupon['minSubtotalCents']) {
            return CouponResult::fail(CouponResult::belowMinimum, $coupon);
        }

        return CouponResult::ok($coupon, $this->discountFor($coupon, $subtotalCents));
    }

    public function discountFor(array $coupon, int $subtotalCents): int
    {
        $discount = $coupon['type'] === 'percent'
            ? (int) floor($subtotalCents * (int) $coupon['value'] / 100)
            : (int) $coupon['value'];

        return max(0, min($discount, $subtotalCents));
    }

    public function redeem(int $couponId): void
    {
        $this->coupons->recordRedemption($couponId);
    }
}
