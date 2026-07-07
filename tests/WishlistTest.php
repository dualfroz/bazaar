<?php

namespace Bazaar\Tests;

use Bazaar\Repositories\UserRepository;
use Bazaar\Repositories\WishlistRepository;

final class WishlistTest extends DatabaseTestCase
{
    private WishlistRepository $wishlist;
    private int $userId;
    private int $otherUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wishlist = new WishlistRepository($this->db);
        $users = new UserRepository($this->db);
        $this->userId = $users->create('a@bazaar.test', 'hash', 'A');
        $this->otherUserId = $users->create('b@bazaar.test', 'hash', 'B');
    }

    public function testToggleAddsThenRemoves(): void
    {
        $productId = $this->seedProduct('Mug', 1200, 5);

        $this->assertTrue($this->wishlist->toggle($this->userId, $productId));
        $this->assertTrue($this->wishlist->has($this->userId, $productId));

        $this->assertFalse($this->wishlist->toggle($this->userId, $productId));
        $this->assertFalse($this->wishlist->has($this->userId, $productId));
    }

    public function testAddIsIdempotent(): void
    {
        $productId = $this->seedProduct('Mug', 1200, 5);

        $this->wishlist->add($this->userId, $productId);
        $this->wishlist->add($this->userId, $productId);

        $this->assertSame(1, $this->wishlist->count($this->userId));
    }

    public function testWishlistIsScopedPerUser(): void
    {
        $productId = $this->seedProduct('Mug', 1200, 5);
        $this->wishlist->add($this->userId, $productId);

        $this->assertTrue($this->wishlist->has($this->userId, $productId));
        $this->assertFalse($this->wishlist->has($this->otherUserId, $productId));
        $this->assertSame(0, $this->wishlist->count($this->otherUserId));
    }

    public function testForUserReturnsProductRows(): void
    {
        $first = $this->seedProduct('Mug', 1200, 5);
        $second = $this->seedProduct('Plate', 1800, 5);
        $this->wishlist->add($this->userId, $first);
        $this->wishlist->add($this->userId, $second);

        $rows = $this->wishlist->forUser($this->userId);
        $this->assertCount(2, $rows);
        $this->assertSame([$first, $second], array_values(array_reverse(array_map('intval', array_column($rows, 'id')))));
    }
}
