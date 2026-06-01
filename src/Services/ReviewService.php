<?php

namespace Bazaar\Services;

use Bazaar\Core\Database;
use Bazaar\Repositories\ReviewRepository;

final class ReviewService
{
    public const eligible = 'eligible';
    public const notPurchased = 'notPurchased';
    public const alreadyReviewed = 'alreadyReviewed';

    public function __construct(
        private readonly Database $db,
        private readonly ReviewRepository $reviews
    ) {
    }

    public function hasPurchased(int $userId, int $productId): bool
    {
        return (int) $this->db->scalar(
            "SELECT COUNT(*)
             FROM orders o
             JOIN orderItems oi ON oi.orderId = o.id
             WHERE o.userId = ? AND oi.productId = ? AND o.status IN ('paid', 'fulfilled')",
            [$userId, $productId]
        ) > 0;
    }

    public function eligibility(int $userId, int $productId): string
    {
        if (!$this->hasPurchased($userId, $productId)) {
            return self::notPurchased;
        }
        if ($this->reviews->existsForUser($productId, $userId)) {
            return self::alreadyReviewed;
        }
        return self::eligible;
    }

    public function canReview(int $userId, int $productId): bool
    {
        return $this->eligibility($userId, $productId) === self::eligible;
    }

    public function submit(int $userId, int $productId, int $rating, string $title, string $body): int
    {
        if (!$this->canReview($userId, $productId)) {
            throw new \RuntimeException('This account is not eligible to review this product.');
        }
        $rating = max(1, min(5, $rating));
        return $this->reviews->create($productId, $userId, $rating, trim($title), trim($body));
    }

    public function approve(int $reviewId): void
    {
        $this->reviews->setStatus($reviewId, 'approved');
    }

    public function reject(int $reviewId): void
    {
        $this->reviews->setStatus($reviewId, 'rejected');
    }
}
