<?php

namespace Bazaar\Core;

use Bazaar\Repositories\AttributeRepository;
use Bazaar\Repositories\CategoryRepository;
use Bazaar\Repositories\CouponRepository;
use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\ReviewRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Repositories\VariantRepository;
use Bazaar\Repositories\WishlistRepository;
use Bazaar\Services\Analytics;
use Bazaar\Services\Auth;
use Bazaar\Services\Cart;
use Bazaar\Services\CouponService;
use Bazaar\Services\OrderService;
use Bazaar\Services\ProductSearch;
use Bazaar\Services\ReviewService;
use Bazaar\Support\Translator;

final class Context
{
    public function __construct(
        public readonly Request $request,
        public readonly Session $session,
        public readonly Csrf $csrf,
        public readonly View $view,
        public readonly Auth $auth,
        public readonly Cart $cart,
        public readonly OrderService $orderService,
        public readonly UserRepository $users,
        public readonly CategoryRepository $categories,
        public readonly ProductRepository $products,
        public readonly OrderRepository $orders,
        public readonly AttributeRepository $attributes,
        public readonly VariantRepository $variants,
        public readonly CouponRepository $coupons,
        public readonly ReviewRepository $reviews,
        public readonly WishlistRepository $wishlist,
        public readonly CouponService $couponService,
        public readonly ReviewService $reviewService,
        public readonly ProductSearch $productSearch,
        public readonly Analytics $analytics,
        public readonly Translator $translator
    ) {
    }
}
