<?php

namespace Bazaar\Tests;

use Bazaar\Repositories\CouponRepository;
use Bazaar\Services\CouponResult;
use Bazaar\Services\CouponService;

final class CouponServiceTest extends DatabaseTestCase
{
    private CouponRepository $coupons;
    private CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coupons = new CouponRepository($this->db);
        $this->service = new CouponService($this->coupons);
    }

    private function coupon(array $overrides = []): int
    {
        return $this->coupons->create(array_merge([
            'code' => 'SAVE10',
            'type' => 'percent',
            'value' => 10,
            'minSubtotalCents' => 0,
            'maxRedemptions' => null,
            'startsAt' => null,
            'expiresAt' => null,
            'isActive' => true,
        ], $overrides));
    }

    public function testPercentDiscountIsFloored(): void
    {
        $this->coupon(['code' => 'SAVE15', 'value' => 15]);
        $result = $this->service->evaluate('SAVE15', 999);

        $this->assertTrue($result->isValid());
        $this->assertSame((int) floor(999 * 0.15), $result->discountCents);
    }

    public function testFixedDiscountNeverExceedsSubtotal(): void
    {
        $this->coupon(['code' => 'FLAT50', 'type' => 'fixed', 'value' => 5000]);
        $result = $this->service->evaluate('FLAT50', 3000);

        $this->assertTrue($result->isValid());
        $this->assertSame(3000, $result->discountCents);
    }

    public function testCodeLookupIsCaseInsensitive(): void
    {
        $this->coupon();
        $this->assertTrue($this->service->evaluate('save10', 5000)->isValid());
    }

    public function testUnknownCodeIsRejected(): void
    {
        $this->assertSame(CouponResult::notFound, $this->service->evaluate('NOPE', 5000)->status);
    }

    public function testInactiveCouponIsRejected(): void
    {
        $this->coupon(['code' => 'OFF', 'isActive' => false]);
        $this->assertSame(CouponResult::inactive, $this->service->evaluate('OFF', 5000)->status);
    }

    public function testBelowMinimumIsRejected(): void
    {
        $this->coupon(['code' => 'BIG', 'minSubtotalCents' => 10000]);
        $this->assertSame(CouponResult::belowMinimum, $this->service->evaluate('BIG', 5000)->status);
    }

    public function testExpiredAndNotStartedWindows(): void
    {
        $this->coupon(['code' => 'PAST', 'expiresAt' => '2020-01-01T00:00:00+00:00']);
        $this->coupon(['code' => 'FUTURE', 'startsAt' => '2999-01-01T00:00:00+00:00']);

        $this->assertSame(CouponResult::expired, $this->service->evaluate('PAST', 5000)->status);
        $this->assertSame(CouponResult::notStarted, $this->service->evaluate('FUTURE', 5000)->status);
    }

    public function testExhaustedWhenRedemptionLimitReached(): void
    {
        $id = $this->coupon(['code' => 'ONCE', 'maxRedemptions' => 1]);
        $this->assertTrue($this->service->evaluate('ONCE', 5000)->isValid());

        $this->service->redeem($id);
        $this->assertSame(CouponResult::exhausted, $this->service->evaluate('ONCE', 5000)->status);
    }
}
