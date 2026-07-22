<?php

namespace Bazaar\Tests;

use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ReviewRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Services\ReviewService;

final class ReviewServiceTest extends DatabaseTestCase
{
    private ReviewRepository $reviews;
    private OrderRepository $orders;
    private ReviewService $service;
    private int $buyerId;
    private int $strangerId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reviews = new ReviewRepository($this->db);
        $this->orders = new OrderRepository($this->db);
        $this->service = new ReviewService($this->db, $this->reviews);
        $users = new UserRepository($this->db);
        $this->buyerId = $users->create('buyer@bazaar.test', 'hash', 'Buyer');
        $this->strangerId = $users->create('stranger@bazaar.test', 'hash', 'Stranger');
    }

    private function purchase(int $userId, int $productId, string $status = 'paid'): void
    {
        $orderId = $this->orders->create($userId, 5000, 'usd');
        $this->orders->addItem($orderId, $productId, 'Item', 5000, 1);
        $this->orders->setStatus($orderId, $status, $status === 'paid' ? 'paidAt' : 'fulfilledAt');
    }

    public function testStrangerCannotReview(): void
    {
        $productId = $this->seedProduct('Lamp', 5000, 5);
        $this->assertSame(ReviewService::notPurchased, $this->service->eligibility($this->strangerId, $productId));
        $this->assertFalse($this->service->canReview($this->strangerId, $productId));
    }

    public function testBuyerCanReviewAfterPaidOrder(): void
    {
        $productId = $this->seedProduct('Lamp', 5000, 5);
        $this->purchase($this->buyerId, $productId, 'paid');

        $this->assertSame(ReviewService::eligible, $this->service->eligibility($this->buyerId, $productId));
    }

    public function testPendingOrderDoesNotUnlockReview(): void
    {
        $productId = $this->seedProduct('Lamp', 5000, 5);
        $orderId = $this->orders->create($this->buyerId, 5000, 'usd');
        $this->orders->addItem($orderId, $productId, 'Item', 5000, 1);

        $this->assertFalse($this->service->canReview($this->buyerId, $productId));
    }

    public function testSubmitThrowsForNonBuyer(): void
    {
        $productId = $this->seedProduct('Lamp', 5000, 5);

        $this->expectException(\RuntimeException::class);
        $this->service->submit($this->strangerId, $productId, 5, 'Nice', 'Great lamp');
    }

    public function testCannotReviewTwice(): void
    {
        $productId = $this->seedProduct('Lamp', 5000, 5);
        $this->purchase($this->buyerId, $productId);
        $this->service->submit($this->buyerId, $productId, 4, 'Good', 'Solid');

        $this->assertSame(ReviewService::alreadyReviewed, $this->service->eligibility($this->buyerId, $productId));
    }

    public function testReviewIsPendingUntilModeratedAndSummaryCountsApprovedOnly(): void
    {
        $productId = $this->seedProduct('Lamp', 5000, 5);
        $this->purchase($this->buyerId, $productId);
        $reviewId = $this->service->submit($this->buyerId, $productId, 5, 'Great', 'Love it');

        $this->assertSame('pending', $this->reviews->find($reviewId)['status']);
        $this->assertSame(0, $this->reviews->summary($productId)['count']);
        $this->assertCount(0, $this->reviews->approvedForProduct($productId));

        $this->service->approve($reviewId);

        $summary = $this->reviews->summary($productId);
        $this->assertSame(1, $summary['count']);
        $this->assertSame(5.0, $summary['average']);
        $this->assertCount(1, $this->reviews->approvedForProduct($productId));
    }

    public function testRatingIsClampedIntoRange(): void
    {
        $productId = $this->seedProduct('Lamp', 5000, 5);
        $this->purchase($this->buyerId, $productId);
        $reviewId = $this->service->submit($this->buyerId, $productId, 9, 'Too high', 'x');

        $this->assertSame(5, (int) $this->reviews->find($reviewId)['rating']);
    }
}
