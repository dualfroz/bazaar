<?php

namespace Bazaar\Tests;

use Bazaar\Core\Session;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Services\Cart;

final class CartTest extends DatabaseTestCase
{
    private function cart(): Cart
    {
        return new Cart(new Session(), new ProductRepository($this->db));
    }

    public function testAddAccumulatesQuantity(): void
    {
        $productId = $this->seedProduct('Cold Brew', 1400, 50);
        $cart = $this->cart();

        $cart->add($productId, 2);
        $cart->add($productId, 3);

        $this->assertSame(5, $cart->count());
        $this->assertSame(1400 * 5, $cart->totalCents());
    }

    public function testAddClampsToAvailableStock(): void
    {
        $productId = $this->seedProduct('Sencha Tin', 1600, 3);
        $cart = $this->cart();

        $cart->add($productId, 10);

        $lines = $cart->lines();
        $this->assertCount(1, $lines);
        $this->assertSame(3, $lines[0]['quantity']);
    }

    public function testUpdateToZeroRemovesLine(): void
    {
        $productId = $this->seedProduct('Notebook', 1200, 20);
        $cart = $this->cart();
        $cart->add($productId, 4);

        $cart->update($productId, 0);

        $this->assertTrue($cart->isEmpty());
        $this->assertSame(0, $cart->totalCents());
    }

    public function testRemoveDropsProduct(): void
    {
        $first = $this->seedProduct('Chef Knife', 8900, 5);
        $second = $this->seedProduct('Linen Apron', 3200, 5);
        $cart = $this->cart();
        $cart->add($first, 1);
        $cart->add($second, 2);

        $cart->remove($first);

        $lines = $cart->lines();
        $this->assertCount(1, $lines);
        $this->assertSame($second, (int) $lines[0]['product']['id']);
        $this->assertSame(3200 * 2, $cart->totalCents());
    }

    public function testUnknownProductIsIgnored(): void
    {
        $cart = $this->cart();

        $cart->add(9999, 1);

        $this->assertTrue($cart->isEmpty());
    }

    public function testLineTotalsAreComputed(): void
    {
        $a = $this->seedProduct('Walnut Board', 4800, 10);
        $b = $this->seedProduct('Wool Throw', 7200, 10);
        $cart = $this->cart();
        $cart->add($a, 2);
        $cart->add($b, 1);

        $this->assertSame(4800 * 2 + 7200, $cart->totalCents());
    }
}
