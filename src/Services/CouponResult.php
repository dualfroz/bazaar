<?php

namespace Bazaar\Services;

final class CouponResult
{
    public const valid = 'valid';
    public const notFound = 'notFound';
    public const inactive = 'inactive';
    public const notStarted = 'notStarted';
    public const expired = 'expired';
    public const exhausted = 'exhausted';
    public const belowMinimum = 'belowMinimum';

    private function __construct(
        public readonly string $status,
        public readonly int $discountCents,
        public readonly ?array $coupon
    ) {
    }

    public static function ok(array $coupon, int $discountCents): self
    {
        return new self(self::valid, $discountCents, $coupon);
    }

    public static function fail(string $status, ?array $coupon = null): self
    {
        return new self($status, 0, $coupon);
    }

    public function isValid(): bool
    {
        return $this->status === self::valid;
    }

    public function message(): string
    {
        return match ($this->status) {
            self::valid => 'Coupon applied.',
            self::notFound => 'That coupon code was not recognised.',
            self::inactive => 'That coupon is no longer active.',
            self::notStarted => 'That coupon is not valid yet.',
            self::expired => 'That coupon has expired.',
            self::exhausted => 'That coupon has reached its redemption limit.',
            self::belowMinimum => 'Your cart does not meet the minimum for this coupon.',
            default => 'That coupon could not be applied.',
        };
    }
}
